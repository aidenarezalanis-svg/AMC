<?php
/**
 * Procesa todos los formularios (POST) y redirige de vuelta.
 * Usa los procedimientos almacenados de la base cuando existen:
 *   - sp_registrar_paciente  → alta de paciente (crea Usuarios + Pacientes en una transacción)
 *   - sp_agendar_cita        → nueva cita (los triggers validan horario y ocupan el turno)
 *   - sp_cancelar_cita       → cancelación (el trigger libera el horario)
 */
require_once __DIR__ . '/includes/bootstrap.php';

$esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
requerir_personal($esAjax);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/** Redirige a la página de origen (solo rutas internas de index.php). */
function regresar(?string $destino = null): void
{
    $destino = $destino ?? ($_POST['volver'] ?? '');
    $ruta = parse_url($destino, PHP_URL_PATH) ?? '';
    $consulta = parse_url($destino, PHP_URL_QUERY);
    $destino = basename($ruta) === 'index.php' ? 'index.php' . ($consulta ? '?' . $consulta : '') : 'index.php';
    header('Location: ' . $destino);
    exit;
}

function texto(string $campo, int $max = 200): string
{
    return mb_substr(trim((string)($_POST[$campo] ?? '')), 0, $max);
}


// Si el archivo enviado rebasa post_max_size de PHP, llegan $_POST y $_FILES vacíos
if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash('error', 'El archivo es demasiado grande. La foto debe pesar máximo 2 MB.');
    regresar(url('pacientes'));
}

if (!csrf_valido($_POST['csrf'] ?? null)) {
    if ($esAjax) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'Sesión expirada']);
        exit;
    }
    flash('error', 'Tu sesión expiró. Vuelve a intentarlo.');
    regresar();
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    /* --------------------------- Nueva cita --------------------------- */
    case 'guardar_cita':
        $_SESSION['viejo'] = $_POST;
        $idPaciente = (int)($_POST['paciente_id'] ?? 0);
        $volverAgendar = url('agendar', array_filter(['paciente' => $idPaciente]));
        $falla = function (string $msg) use ($volverAgendar) {
            flash('error', $msg);
            regresar($volverAgendar);
        };

        $pac = paciente($idPaciente);
        if (!$pac) {
            $falla('Por favor selecciona un paciente o regístralo a la derecha.');
        }
        if (!$pac['activo']) {
            $falla('El paciente ' . $pac['nombre_completo'] . ' está dado de baja. Reactívalo en Pacientes para agendarle.');
        }
        $idEspecialidad = (int)($_POST['especialidad_id'] ?? 0);
        if (!$idEspecialidad) {
            $falla('Por favor selecciona una especialidad médica.');
        }
        $med = medico((int)($_POST['medico_id'] ?? 0));
        if (!$med) {
            $falla('Por favor asigna un médico para la consulta.');
        }
        if (!in_array($idEspecialidad, array_column($med['especialidades'], 'id'), true)) {
            $falla('El médico seleccionado no atiende esa especialidad.');
        }
        $idConsultorio = (int)($_POST['consultorio_id'] ?? 0);
        if (!in_array($idConsultorio, array_column($med['consultorios'], 'id'), true)) {
            $falla('Selecciona uno de los consultorios donde atiende el médico.');
        }
        $fecha = texto('fecha', 10);
        $hora = texto('hora', 5);
        if (!fecha_valida($fecha)) {
            $falla('La fecha de la cita no es válida.');
        }
        if (!in_array($hora, HORARIOS_CITA, true)) {
            $falla('Selecciona un horario de atención válido.');
        }
        $motivo = texto('motivo', 255) ?: 'Consulta general programada';

        // 1) Buscar el turno en horarios_disponibles o crearlo (uq_horario: médico + fecha + hora)
        $horario = fila('SELECT * FROM horarios_disponibles WHERE id_medico = ? AND fecha = ? AND hora_inicio = ?',
                        [$med['id_medico'], $fecha, $hora . ':00']);
        $horarioNuevo = false;
        try {
            if ($horario) {
                if ($horario['estatus'] !== 'Disponible') {
                    $falla('Ese horario de ' . $med['nombre_completo'] . ' ya está ocupado. Elige otro turno.');
                }
                $idHorario = (int)$horario['id_horario'];
            } else {
                $fin = date('H:i:s', strtotime($hora . ' +30 minutes'));
                $st = db()->prepare("INSERT INTO horarios_disponibles (id_medico, id_consultorio, fecha, hora_inicio, hora_fin, estatus)
                                     VALUES (?, ?, ?, ?, ?, 'Disponible')");
                $st->execute([$med['id_medico'], $idConsultorio, $fecha, $hora . ':00', $fin]);
                $idHorario = (int)db()->lastInsertId();
                $horarioNuevo = true;
            }

            // 2) Agendar con el procedimiento (el trigger valida que no sea pasado y lo marca Ocupado)
            $idCita = (int)llamar_procedimiento('sp_agendar_cita', [$idPaciente, $idHorario, $idEspecialidad, $motivo]);
        } catch (PDOException $e) {
            if ($horarioNuevo) {
                db()->prepare("DELETE FROM horarios_disponibles WHERE id_horario = ? AND estatus = 'Disponible'")->execute([$idHorario]);
            }
            $falla(mensaje_bd($e));
        }

        unset($_SESSION['viejo']);
        flash('cita_ok', '¡Cita #' . $idCita . ' agendada con éxito para ' . $pac['nombre_completo'] . ' el ' . $fecha . ' a las ' . $hora . ' hrs!');
        regresar(url('agendar'));
        break;

    /* ------------------------ Registrar paciente ----------------------- */
    case 'registrar_paciente':
        $_SESSION['viejo'] = $_POST;
        $falla = function (string $msg) {
            flash('error', $msg);
            regresar(url('agendar'));
        };

        // La cuenta de usuario se crea con una contraseña aleatoria cifrada;
        // por ahora el paciente no puede iniciar sesión hasta que se le asigne una (ver README).
        [$nuevoId, $error] = registrar_paciente($_POST, password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT));
        if ($error) {
            $falla($error);
        }
        $nombre = texto('nombre', 50);

        unset($_SESSION['viejo']);
        flash('paciente_ok', '¡Paciente ' . $nombre . ' registrado exitosamente (ID ' . $nuevoId . ') y vinculado a la cita!');
        regresar(url('agendar', ['paciente' => $nuevoId]));
        break;

    /* --------------------- Cambiar estado de una cita ------------------ */
    case 'estado_cita':
        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        try {
            if ($estado === 'Cancelada') {
                $msg = (string)llamar_procedimiento('sp_cancelar_cita', [$id]);
                flash(str_contains($msg, 'correctamente') ? 'estado_ok' : 'error', $msg);
            } elseif (in_array($estado, ['Programada', 'Confirmada', 'Completada'], true)) {
                // Una cita cancelada o completada ya no cambia (su horario pudo haberse liberado)
                $st = db()->prepare("UPDATE citas SET estatus_cita = ? WHERE id_cita = ? AND estatus_cita NOT IN ('Cancelada', 'Completada')");
                $st->execute([$estado, $id]);
                if ($st->rowCount() === 0 && ($c = cita($id)) && in_array($c['estatus_cita'], ['Cancelada', 'Completada'], true)) {
                    flash('error', 'La cita #' . $id . ' ya está ' . mb_strtolower($c['estatus_cita']) . ' y no puede cambiar de estado.');
                }
            }
        } catch (PDOException $e) {
            flash('error', mensaje_bd($e));
        }
        regresar();
        break;

    /* ------------------- Baja lógica / reactivar paciente -------------- */
    case 'estatus_paciente':
        $id = (int)($_POST['id'] ?? 0);
        $nuevo = ($_POST['estatus'] ?? '') === 'Activo' ? 'Activo' : 'Inactivo';
        $st = db()->prepare('UPDATE pacientes SET estatus = ? WHERE id_paciente = ?');
        $st->execute([$nuevo, $id]);
        flash('estado_ok', $nuevo === 'Activo' ? 'Paciente reactivado.' : 'Paciente dado de baja (borrado lógico): su historial se conserva.');
        regresar();
        break;

    /* ---------- AMC-17: el personal sube o cambia la foto de un paciente ---------- */
    case 'subir_foto':
        $id = (int)($_POST['id'] ?? 0);
        $error = guardar_foto_paciente($id, $_FILES['foto'] ?? null);
        flash($error ? 'error' : 'estado_ok', $error ?? 'Foto de perfil actualizada.');
        regresar(url('pacientes', ['id' => $id]));
        break;

    /* --------- Reordenar: registra una compra y suma el inventario --------- */
    case 'reordenar':
        $cantidad = max(1, min(1000, (int)($_POST['cantidad'] ?? CANTIDAD_REORDEN)));
        $med = medicamento((int)($_POST['id'] ?? 0));
        $idProveedor = (int)($_POST['proveedor_id'] ?? 0) ?: null;
        if ($med) {
            $total = round($cantidad * $med['precio_unitario'], 2);
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO compra (id_proveedor, numero_factura, fecha_compra, total, estado) VALUES (?, NULL, ?, ?, 'Completada')")
                    ->execute([$idProveedor, hoy(), $total]);
                $idCompra = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO detalle_compra (id_compra, id_medicamento, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$idCompra, $med['id_medicamento'], $cantidad, $med['precio_unitario'], $total]);
                $pdo->prepare('UPDATE medicamentos SET stock_actual = stock_actual + ? WHERE id_medicamento = ?')
                    ->execute([$cantidad, $med['id_medicamento']]);
                $pdo->commit();
                flash('reorden_ok', 'Compra #' . $idCompra . ': se pidieron ' . $cantidad . ' unidades de ' . $med['nombre_comercial']
                    . ' (' . dinero($total, 2) . '). Existencias: ' . ($med['stock_actual'] + $cantidad) . ' u.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', mensaje_bd($e));
            }
        }
        regresar();
        break;

    /* --------------------------- Cambiar sede --------------------------- */
    case 'cambiar_sede':
        $_SESSION['sede_id'] = (int)($_POST['sede_id'] ?? 0);
        regresar();
        break;

    /* --------------------- Marcar notificación leída -------------------- */
    case 'marcar_notificacion':
        $_SESSION['notificaciones_leidas'][] = (string)($_POST['id'] ?? '');
        $_SESSION['notificaciones_leidas'] = array_slice(array_unique($_SESSION['notificaciones_leidas']), -100);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;

    default:
        regresar();
}

<?php
/**
 * Capa de datos sobre la base real `app_medica_citas`.
 * Todas las consultas viven aquí; las vistas solo muestran la información.
 *
 * Tablas usadas: pacientes, usuarios, medicos, especialidades, medico_especialidad,
 * consultorios, medico_consultorio, horarios_disponibles, citas, pagos,
 * medicamentos, compra, detalle_compra, proveedores, venta, seguimiento_clientes, personal.
 */

/** Días tal como se guardan en medico_consultorio.dias_atencion */
const DIAS_ATENCION = [1 => 'Lun', 2 => 'Mar', 3 => 'Mie', 4 => 'Jue', 5 => 'Vie', 6 => 'Sab', 7 => 'Dom'];

function dia_atencion(string $fecha): string
{
    return DIAS_ATENCION[(int)date('N', strtotime($fecha))];
}

function atiende_el_dia(string $diasAtencion, string $fecha): bool
{
    $dias = array_map('trim', explode(',', $diasAtencion));
    return in_array(dia_atencion($fecha), $dias, true);
}

/** Ejecuta una consulta preparada y regresa todas las filas. */
function filas(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function fila(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetch() ?: null;
}

function valor(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

/* ------------------- Sedes (tabla consultorios) ------------------- */

function consultorios(): array
{
    return filas('SELECT * FROM consultorios ORDER BY id_consultorio');
}

function sede_actual(): array
{
    $todas = consultorios();
    foreach ($todas as $c) {
        if ((int)$c['id_consultorio'] === (int)($_SESSION['sede_id'] ?? 0)) {
            return $c;
        }
    }
    return $todas[0] ?? ['id_consultorio' => 0, 'nombre_consultorio' => 'Sin consultorios', 'direccion' => '', 'ciudad' => '', 'telefono' => ''];
}

/** Usuario que inició sesión, con el formato que usa el encabezado. */
function usuario_sesion(): array
{
    $u = usuario_actual() ?? ['nombre' => 'Invitado', 'correo' => '', 'rol' => '', 'iniciales' => '?', 'id_perfil' => 0];
    return $u + ['id' => $u['id_perfil'] ?? 0];
}

/* --------------------------- Especialidades --------------------------- */

function especialidades(): array
{
    return filas('SELECT * FROM especialidades ORDER BY nombre_especialidad');
}

/* ------------------------------ Médicos ------------------------------- */

/**
 * Médicos con sus especialidades, consultorios (y días) y horarios libres próximos.
 * Las relaciones muchos-a-muchos se arman en PHP para que la consulta sea sencilla.
 */
function medicos(): array
{
    $medicos = filas('SELECT * FROM medicos ORDER BY id_medico');

    $esp = [];
    foreach (filas('SELECT me.id_medico, e.id_especialidad, e.nombre_especialidad
                    FROM medico_especialidad me JOIN especialidades e ON e.id_especialidad = me.id_especialidad
                    ORDER BY e.nombre_especialidad') as $r) {
        $esp[$r['id_medico']][] = ['id' => (int)$r['id_especialidad'], 'nombre' => $r['nombre_especialidad']];
    }

    $cons = [];
    foreach (filas('SELECT mc.id_medico, mc.dias_atencion, c.id_consultorio, c.nombre_consultorio, c.ciudad
                    FROM medico_consultorio mc JOIN consultorios c ON c.id_consultorio = mc.id_consultorio
                    ORDER BY c.id_consultorio') as $r) {
        $cons[$r['id_medico']][] = ['id' => (int)$r['id_consultorio'], 'nombre' => $r['nombre_consultorio'],
                                    'ciudad' => $r['ciudad'], 'dias' => $r['dias_atencion']];
    }

    $libres = [];
    foreach (filas("SELECT id_medico, COUNT(*) AS total FROM horarios_disponibles
                    WHERE estatus = 'Disponible' AND fecha >= ? GROUP BY id_medico", [hoy()]) as $r) {
        $libres[$r['id_medico']] = (int)$r['total'];
    }

    return array_map(function ($m) use ($esp, $cons, $libres) {
        $id = $m['id_medico'];
        $m['nombre_completo'] = trim($m['nombre'] . ' ' . $m['ap_paterno'] . ' ' . ($m['ap_materno'] ?? ''));
        $m['iniciales'] = mb_strtoupper(mb_substr($m['nombre'], 0, 1) . mb_substr($m['ap_paterno'], 0, 1));
        $m['especialidades'] = $esp[$id] ?? [];
        $m['consultorios'] = $cons[$id] ?? [];
        $m['horarios_libres'] = $libres[$id] ?? 0;
        $m['atiende_hoy'] = (bool)array_filter($m['consultorios'], fn($c) => atiende_el_dia($c['dias'], hoy()));
        return $m;
    }, $medicos);
}

function medico(int $id): ?array
{
    foreach (medicos() as $m) {
        if ((int)$m['id_medico'] === $id) {
            return $m;
        }
    }
    return null;
}

/* ----------------------------- Pacientes ------------------------------ */

const GENEROS = ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'];

function edad(?string $fechaNacimiento): ?int
{
    if (!$fechaNacimiento) {
        return null;
    }
    return (new DateTime($fechaNacimiento))->diff(new DateTime(hoy()))->y;
}

function preparar_paciente(array $p): array
{
    $p['nombre_completo'] = nombre_completo($p);
    $p['iniciales'] = iniciales_paciente($p);
    $p['genero_texto'] = GENEROS[$p['genero']] ?? $p['genero'];
    $p['edad'] = edad($p['fecha_nacimiento']);
    $p['activo'] = $p['estatus'] === 'Activo';
    $p['foto'] = url_foto((int)$p['id_paciente'], $p['foto_perfil'] ?? null);   // AMC-16 (null si aún no existe la columna)
    return $p;
}

/** Los registrados más recientemente aparecen primero. */
function pacientes(): array
{
    return array_map('preparar_paciente', filas('SELECT * FROM pacientes ORDER BY fecha_registro DESC, id_paciente DESC'));
}

function paciente(int $id): ?array
{
    $p = fila('SELECT * FROM pacientes WHERE id_paciente = ?', [$id]);
    return $p ? preparar_paciente($p) : null;
}

function total_pacientes_activos(): int
{
    return (int)valor("SELECT COUNT(*) FROM pacientes WHERE estatus = 'Activo'");
}

/** Número de citas por paciente (para la ficha clínica). */
function citas_por_paciente(): array
{
    $r = [];
    foreach (filas('SELECT id_paciente, COUNT(*) AS total FROM citas GROUP BY id_paciente') as $f) {
        $r[$f['id_paciente']] = (int)$f['total'];
    }
    return $r;
}

/**
 * Búsqueda de pacientes ACTIVOS por nombre completo, CURP, teléfono o correo.
 * Cada palabra escrita debe coincidir con alguna columna.
 */
function buscar_pacientes(string $texto, int $limite = 6): array
{
    $palabras = preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY);
    if (!$palabras) {
        return [];
    }
    $columnas = ['LOWER(nombre)', 'LOWER(ap_paterno)', "LOWER(COALESCE(ap_materno, ''))", 'LOWER(curp)', 'telefono', 'LOWER(correo)'];
    $condiciones = [];
    $params = [];
    foreach ($palabras as $p) {
        $condiciones[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $columnas)) . ')';
        array_push($params, ...array_fill(0, count($columnas), '%' . mb_strtolower($p) . '%'));
    }
    $sql = "SELECT * FROM pacientes WHERE estatus = 'Activo' AND " . implode(' AND ', $condiciones)
         . ' ORDER BY nombre, ap_paterno LIMIT ' . (int)$limite;
    return array_map('preparar_paciente', filas($sql, $params));
}

function buscar_medicos(string $texto, int $limite = 3): array
{
    $q = mb_strtolower(trim($texto));
    $encontrados = array_filter(medicos(), function ($m) use ($q) {
        $pajar = mb_strtolower($m['nombre_completo'] . ' ' . implode(' ', array_column($m['especialidades'], 'nombre'))
               . ' ' . implode(' ', array_column($m['consultorios'], 'nombre')));
        return mb_strpos($pajar, $q) !== false;
    });
    return array_slice(array_values($encontrados), 0, $limite);
}

function buscar_medicamentos(string $texto, int $limite = 3): array
{
    $q = '%' . mb_strtolower(trim($texto)) . '%';
    $filas = filas('SELECT * FROM medicamentos WHERE LOWER(nombre_comercial) LIKE ? OR LOWER(principio_activo) LIKE ?
                    ORDER BY nombre_comercial LIMIT ' . (int)$limite, [$q, $q]);
    return array_map('preparar_medicamento', $filas);
}

/* ------------------------------- Citas -------------------------------- */

const SQL_CITAS = 'SELECT c.id_cita, c.id_paciente, c.id_horario, c.id_especialidad, c.motivo_consulta, c.estatus_cita, c.fecha_creacion,
                          h.fecha, h.hora_inicio, h.hora_fin, h.id_medico, h.id_consultorio,
                          p.nombre, p.ap_paterno, p.ap_materno, p.telefono, p.curp,
                          m.nombre AS medico_nombre, m.ap_paterno AS medico_ap_paterno,
                          e.nombre_especialidad, co.nombre_consultorio,
                          pg.monto AS pago_monto, pg.estatus_pago
                   FROM citas c
                   JOIN horarios_disponibles h ON h.id_horario = c.id_horario
                   JOIN pacientes p ON p.id_paciente = c.id_paciente
                   JOIN medicos m ON m.id_medico = h.id_medico
                   JOIN especialidades e ON e.id_especialidad = c.id_especialidad
                   JOIN consultorios co ON co.id_consultorio = h.id_consultorio
                   LEFT JOIN pagos pg ON pg.id_cita = c.id_cita';

function preparar_cita(array $c): array
{
    $c['paciente_nombre'] = nombre_completo($c);
    $c['medico'] = trim($c['medico_nombre'] . ' ' . $c['medico_ap_paterno']);
    $c['hora'] = hora_corta($c['hora_inicio']);
    $c['es_hoy'] = $c['fecha'] === hoy();
    return $c;
}

function citas(): array
{
    return array_map('preparar_cita', filas(SQL_CITAS . ' ORDER BY h.fecha DESC, h.hora_inicio ASC, c.id_cita DESC'));
}

function citas_de_hoy(): array
{
    return array_map('preparar_cita', filas(SQL_CITAS . ' WHERE h.fecha = ? ORDER BY h.hora_inicio, c.id_cita', [hoy()]));
}

/** Citas de hoy en adelante que siguen activas (para el panel del dashboard). */
function proximas_citas(int $limite = 8): array
{
    return array_map('preparar_cita', filas(SQL_CITAS . " WHERE h.fecha >= ? AND c.estatus_cita IN ('Programada', 'Confirmada')
                                                           ORDER BY h.fecha, h.hora_inicio LIMIT " . (int)$limite, [hoy()]));
}

function cita(int $id): ?array
{
    $c = fila(SQL_CITAS . ' WHERE c.id_cita = ?', [$id]);
    return $c ? preparar_cita($c) : null;
}

/* ------------------------ Horarios disponibles ------------------------ */

/** Horarios ya registrados de un médico en una fecha: ['09:00' => 'Ocupado', ...] */
function horarios_del_dia(int $idMedico, string $fecha): array
{
    $r = [];
    foreach (filas('SELECT hora_inicio, estatus FROM horarios_disponibles WHERE id_medico = ? AND fecha = ?', [$idMedico, $fecha]) as $h) {
        $r[hora_corta($h['hora_inicio'])] = $h['estatus'];
    }
    return $r;
}

/** Próximos horarios libres publicados de un médico. */
function proximos_horarios_libres(int $idMedico, int $limite = 6): array
{
    return filas("SELECT h.id_horario, h.fecha, h.hora_inicio, h.id_consultorio, c.nombre_consultorio
                  FROM horarios_disponibles h JOIN consultorios c ON c.id_consultorio = h.id_consultorio
                  WHERE h.id_medico = ? AND h.estatus = 'Disponible' AND h.fecha >= ?
                  ORDER BY h.fecha, h.hora_inicio LIMIT " . (int)$limite, [$idMedico, hoy()]);
}

/* ---------------------------- Medicamentos ---------------------------- */

function estado_medicamento(int $stock): string
{
    if ($stock <= 0) {
        return 'Agotado';
    }
    return $stock > STOCK_MINIMO ? 'Normal' : 'Bajo';
}

function preparar_medicamento(array $m): array
{
    $m['stock_actual'] = (int)$m['stock_actual'];
    $m['precio_unitario'] = (float)$m['precio_unitario'];
    $m['estado'] = estado_medicamento($m['stock_actual']);
    return $m;
}

function medicamentos(): array
{
    return array_map('preparar_medicamento', filas('SELECT * FROM medicamentos ORDER BY nombre_comercial'));
}

function medicamento(int $id): ?array
{
    $m = fila('SELECT * FROM medicamentos WHERE id_medicamento = ?', [$id]);
    return $m ? preparar_medicamento($m) : null;
}

/** Medicamentos agotados o en stock bajo, los más urgentes primero. */
function medicamentos_criticos(): array
{
    $criticos = array_values(array_filter(medicamentos(), fn($m) => $m['estado'] !== 'Normal'));
    usort($criticos, fn($a, $b) => $a['stock_actual'] <=> $b['stock_actual']);
    return $criticos;
}

function proveedores(): array
{
    return filas('SELECT * FROM proveedores ORDER BY nombre');
}

/* ------------------------------- Ventas ------------------------------- */

function ventas_del_dia(string $fecha): float
{
    return (float)valor("SELECT COALESCE(SUM(total), 0) FROM venta WHERE DATE(fecha_venta) = ? AND estatus_venta = 'Completada'", [$fecha]);
}

/* --------------------------- Notificaciones --------------------------- *
 * La base no tiene tabla de notificaciones: se generan a partir de los datos
 * (inventario, citas del día, compras y visitas recientes). Lo "leído" se
 * recuerda en la sesión del navegador.
 */
function notificaciones(): array
{
    $n = [];
    foreach (medicamentos_criticos() as $m) {
        if ($m['estado'] === 'Agotado') {
            $n[] = ['id' => 'agotado-' . $m['id_medicamento'], 'tipo' => 'alerta', 'titulo' => 'Alerta crítica de farmacia',
                    'mensaje' => $m['nombre_comercial'] . ' agotado (0 unidades). Se requiere reorden inmediata.', 'fecha' => null];
        }
    }
    $bajos = count(array_filter(medicamentos_criticos(), fn($m) => $m['estado'] === 'Bajo'));
    if ($bajos) {
        $n[] = ['id' => 'bajos-' . $bajos, 'tipo' => 'alerta', 'titulo' => 'Stock bajo',
                'mensaje' => $bajos . ' medicamento(s) con ' . STOCK_MINIMO . ' unidades o menos.', 'fecha' => null];
    }
    $hoy = citas_de_hoy();
    if ($hoy) {
        $n[] = ['id' => 'citas-hoy-' . hoy() . '-' . count($hoy), 'tipo' => 'info', 'titulo' => 'Citas de la jornada',
                'mensaje' => count($hoy) . ' cita(s) programadas para hoy. La primera es a las ' . $hoy[0]['hora'] . ' hrs.', 'fecha' => null];
    }
    foreach (filas('SELECT c.id_compra, c.total, c.fecha_registro, pr.nombre AS proveedor
                    FROM compra c LEFT JOIN proveedores pr ON pr.id_proveedor = c.id_proveedor
                    ORDER BY c.fecha_registro DESC LIMIT 2') as $c) {
        $n[] = ['id' => 'compra-' . $c['id_compra'], 'tipo' => 'exito', 'titulo' => 'Compra de inventario registrada',
                'mensaje' => 'Compra #' . $c['id_compra'] . ' por ' . dinero($c['total'], 2) . ($c['proveedor'] ? ' a ' . $c['proveedor'] : '') . '.',
                'fecha' => $c['fecha_registro']];
    }
    foreach (filas('SELECT id_seguimiento, nombre_cliente, fecha_hora FROM seguimiento_clientes ORDER BY fecha_hora DESC LIMIT 2') as $s) {
        $n[] = ['id' => 'visita-' . $s['id_seguimiento'], 'tipo' => 'info', 'titulo' => 'Visita registrada',
                'mensaje' => trim($s['nombre_cliente']) . ' realizó una compra en farmacia.', 'fecha' => $s['fecha_hora']];
    }
    $leidas = $_SESSION['notificaciones_leidas'] ?? [];
    foreach ($n as &$x) {
        $x['leido'] = in_array($x['id'], $leidas, true);
    }
    return $n;
}

/* ----------------------- Errores de la base ----------------------- */

/**
 * Convierte una excepción de PDO en un mensaje para el usuario.
 * Los SIGNAL de los procedimientos y triggers ya traen un texto en español.
 */
function mensaje_bd(PDOException $e): string
{
    $info = $e->errorInfo ?? [];
    $codigo = (int)($info[1] ?? 0);
    $texto = (string)($info[2] ?? $e->getMessage());
    if ($codigo === 1644 || ($info[0] ?? '') === '45000') {
        return $texto;                                   // mensaje de SIGNAL
    }
    if ($codigo === 1062) {
        if (stripos($texto, 'curp') !== false) {
            return 'Ya existe un paciente registrado con esa CURP.';
        }
        if (stripos($texto, 'correo') !== false) {
            return 'Ya existe un registro con ese correo electrónico.';
        }
        return 'Ese registro ya existe en la base de datos.';
    }
    return 'Error de base de datos: ' . $texto;
}

/* ------------------ Procedimientos y alta de pacientes ------------------ */

function fecha_valida(string $fecha): bool
{
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f && $f->format('Y-m-d') === $fecha;
}

/** Ejecuta un CALL con parámetro OUT al final y regresa su valor. */
function llamar_procedimiento(string $nombre, array $entradas)
{
    $marcas = implode(', ', array_fill(0, count($entradas), '?'));
    $st = db()->prepare("CALL $nombre($marcas, @salida)");
    $st->execute($entradas);
    $st->closeCursor();
    return db()->query('SELECT @salida')->fetchColumn();
}

/**
 * Valida los datos de un paciente nuevo y lo da de alta con sp_registrar_paciente
 * (crea usuarios + pacientes en una transacción). Se usa en Agendar y en el registro público.
 * Regresa [id_paciente, null] o [null, mensaje de error].
 */
function registrar_paciente(array $in, string $hashContrasena): array
{
    $t = fn(string $campo, int $max) => mb_substr(trim((string)($in[$campo] ?? '')), 0, $max);

    $nombre = $t('nombre', 50);
    $apPat = $t('ap_paterno', 50);
    $apMat = $t('ap_materno', 50);
    if ($nombre === '' || $apPat === '') {
        return [null, 'Por favor ingresa al menos nombre y apellido paterno.'];
    }
    $nac = $t('fecha_nacimiento', 10);
    if (!fecha_valida($nac) || $nac > hoy()) {
        return [null, 'Indica una fecha de nacimiento válida.'];
    }
    $genero = (string)($in['genero'] ?? '');
    if (!isset(GENEROS[$genero])) {
        return [null, 'Selecciona el género.'];
    }
    $curp = strtoupper(preg_replace('/\s+/', '', $t('curp', 18)));
    if (!preg_match('/^[A-Z0-9]{18}$/', $curp)) {
        return [null, 'La CURP debe tener exactamente 18 letras o números.'];
    }
    $telefono = preg_replace('/\D/', '', $t('telefono', 20));
    if (strlen($telefono) !== 10) {
        return [null, 'El teléfono debe tener 10 dígitos.'];
    }
    $correo = mb_strtolower($t('correo', 100));
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return [null, 'Escribe un correo electrónico válido (no puede repetirse).'];
    }
    try {
        $id = (int)llamar_procedimiento('sp_registrar_paciente',
            [$nombre, $apPat, $apMat === '' ? null : $apMat, $nac, $genero, $curp, $telefono, $correo, $hashContrasena]);
    } catch (PDOException $e) {
        return [null, mensaje_bd($e)];
    }
    return [$id, null];
}

<?php
/**
 * AMC-4  Editar datos personales (paciente).
 *
 * Criterios de aceptación:
 *  - Permite modificar nombre, teléfono, correo y fecha de nacimiento.
 *  - Los campos obligatorios no pueden quedar vacíos.
 *  - Los cambios se reflejan al recargar la página (se guardan en la tabla pacientes).
 *  - Muestra un mensaje claro de confirmación o de error.
 */

/** Campos editables: nombre del input => [columna, etiqueta, obligatorio] */
const CAMPOS_PERFIL = [
    'nombre'           => ['nombre', 'Nombre(s)', true],
    'ap_paterno'       => ['ap_paterno', 'Apellido paterno', true],
    'ap_materno'       => ['ap_materno', 'Apellido materno', false],
    'fecha_nacimiento' => ['fecha_nacimiento', 'Fecha de nacimiento', true],
    'telefono'         => ['telefono', 'Teléfono', true],
    'correo'           => ['correo', 'Correo electrónico', true],
];

/** ¿El correo ya lo usa otro paciente, un médico o alguien del personal? (el login busca en las tres tablas) */
function correo_ocupado(string $correo, int $idPaciente): bool
{
    $c = mb_strtolower($correo);
    return (bool)valor('SELECT COUNT(*) FROM pacientes WHERE LOWER(correo) = ? AND id_paciente <> ?', [$c, $idPaciente])
        || (bool)valor('SELECT COUNT(*) FROM medicos WHERE LOWER(correo) = ?', [$c])
        || (bool)valor('SELECT COUNT(*) FROM personal WHERE LOWER(correo) = ?', [$c]);
}

/**
 * Revisa los datos enviados. Regresa [datos limpios, errores por campo].
 */
function validar_datos_perfil(array $in, int $idPaciente): array
{
    $d = [];
    $err = [];
    foreach (CAMPOS_PERFIL as $campo => [, $etiqueta, $obligatorio]) {
        $d[$campo] = trim(preg_replace('/\s+/u', ' ', (string)($in[$campo] ?? '')));
        if ($obligatorio && $d[$campo] === '') {
            $err[$campo] = 'El campo «' . $etiqueta . '» es obligatorio.';
        }
    }

    foreach (['nombre', 'ap_paterno', 'ap_materno'] as $campo) {
        if (isset($err[$campo]) || $d[$campo] === '') {
            continue;
        }
        if (mb_strlen($d[$campo]) > 50) {
            $err[$campo] = CAMPOS_PERFIL[$campo][1] . ' admite máximo 50 caracteres.';
        } elseif (!preg_match("/^\\p{L}[\\p{L} .'-]*$/u", $d[$campo])) {
            $err[$campo] = CAMPOS_PERFIL[$campo][1] . ' solo puede llevar letras, espacios, puntos, guiones o apóstrofos.';
        }
    }

    if (!isset($err['telefono'])) {
        $d['telefono'] = preg_replace('/[\s()-]/', '', $d['telefono']);
        if (!preg_match('/^\d{10}$/', $d['telefono'])) {
            $err['telefono'] = 'El teléfono debe tener exactamente 10 dígitos.';
        }
    }

    if (!isset($err['correo'])) {
        $d['correo'] = mb_strtolower($d['correo']);
        if (mb_strlen($d['correo']) > 100 || !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            $err['correo'] = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
        } elseif (correo_ocupado($d['correo'], $idPaciente)) {
            $err['correo'] = 'Ese correo ya está registrado en otra cuenta. Usa uno diferente.';
        }
    }

    if (!isset($err['fecha_nacimiento'])) {
        $f = $d['fecha_nacimiento'];
        if (!fecha_valida($f)) {
            $err['fecha_nacimiento'] = 'La fecha de nacimiento no es válida.';
        } elseif ($f > hoy()) {
            $err['fecha_nacimiento'] = 'La fecha de nacimiento no puede ser futura.';
        } elseif ($f < date('Y-m-d', strtotime('-120 years'))) {
            $err['fecha_nacimiento'] = 'Revisa el año de nacimiento.';
        }
    }

    return [$d, $err];
}

/**
 * Valida y guarda los datos del paciente.
 * Regresa ['ok' => bool, 'mensaje' => string, 'errores' => [campo => mensaje]].
 */
function actualizar_datos_paciente(int $idPaciente, array $in): array
{
    $actual = fila('SELECT * FROM pacientes WHERE id_paciente = ?', [$idPaciente]);
    if (!$actual) {
        return ['ok' => false, 'mensaje' => 'No se encontró tu registro de paciente.', 'errores' => []];
    }

    [$d, $err] = validar_datos_perfil($in, $idPaciente);
    if ($err) {
        $n = count($err);
        return ['ok' => false, 'errores' => $err,
                'mensaje' => 'No se guardaron los cambios: revisa ' . ($n === 1 ? 'el campo marcado' : "los $n campos marcados") . ' en rojo.'];
    }

    // Solo se actualiza lo que cambió, y cada cambio se describe para el historial
    $cambios = [];
    $descripcion = [];
    foreach (CAMPOS_PERFIL as $campo => [$columna, $etiqueta]) {
        $antes = (string)($actual[$columna] ?? '');
        $despues = $d[$campo];
        if ($antes !== $despues) {
            $cambios[$columna] = $despues === '' ? null : $despues;
            $descripcion[] = "$etiqueta: '$antes' a '$despues'";
        }
    }
    if (!$cambios) {
        return ['ok' => true, 'mensaje' => 'No hiciste cambios: tus datos siguen igual.', 'errores' => []];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $sets = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cambios)));
        $pdo->prepare("UPDATE pacientes SET $sets WHERE id_paciente = ?")
            ->execute([...array_values($cambios), $idPaciente]);
        $pdo->prepare('INSERT INTO historial_cambios_pacientes (id_paciente, cambios_realizados, fecha_modificacion) VALUES (?, ?, ?)')
            ->execute([$idPaciente, mb_substr(implode('; ', $descripcion), 0, 1000), date('Y-m-d H:i:s')]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'mensaje' => 'No se pudieron guardar los cambios. ' . mensaje_bd($e), 'errores' => []];
    }

    // El encabezado y el saludo usan los datos de la sesión: se actualizan también
    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['id_perfil'] === $idPaciente) {
        $_SESSION['usuario']['nombre'] = trim($d['nombre'] . ' ' . $d['ap_paterno'] . ' ' . $d['ap_materno']);
        $_SESSION['usuario']['nombre_corto'] = $d['nombre'];
        $_SESSION['usuario']['correo'] = $d['correo'];
        $_SESSION['usuario']['iniciales'] = mb_strtoupper(mb_substr($d['nombre'], 0, 1) . mb_substr($d['ap_paterno'], 0, 1));
    }

    $n = count($cambios);
    return ['ok' => true, 'errores' => [],
            'mensaje' => 'Tus datos se actualizaron correctamente (' . $n . ($n === 1 ? ' campo modificado' : ' campos modificados') . ').'];
}

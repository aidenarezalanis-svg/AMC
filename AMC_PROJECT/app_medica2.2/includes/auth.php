<?php
/**
 * Autenticación sobre la base real app_medica_citas.
 *
 * - La contraseña vive en usuarios.contrasena_hash (no en pacientes).
 * - Pacientes inician sesión con su correo o su CURP (ambos son únicos en la tabla pacientes).
 * - El personal (Medico, Recepcionista, Administrador) inicia sesión con su correo
 *   de la tabla medicos o personal.
 * - Solo entran cuentas con usuarios.estatus = 'Activo' (y pacientes.estatus = 'Activo').
 * - Contraseñas nuevas se guardan con password_hash (bcrypt). Si una cuenta todavía tiene
 *   la contraseña en texto plano (como los datos de prueba del volcado), se acepta una vez
 *   y se convierte a hash automáticamente.
 */

const ROLES_PERSONAL = ['Medico', 'Recepcionista', 'Administrador'];
const MAX_INTENTOS = 5;          // intentos fallidos permitidos…
const BLOQUEO_SEGUNDOS = 120;    // …antes de esperar este tiempo

/** Usuario que inició sesión, o null. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function es_personal(): bool
{
    return in_array(usuario_actual()['rol'] ?? '', ROLES_PERSONAL, true);
}

/** Exige sesión del personal; si no hay, manda al login (o responde 401 en peticiones JSON). */
function requerir_personal(bool $json = false): void
{
    $u = usuario_actual();
    if ($u && in_array($u['rol'], ROLES_PERSONAL, true)) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Sesión no iniciada']);
        exit;
    }
    // Un paciente que intenta entrar al panel regresa a su espacio
    header('Location: ' . ($u ? 'mi_espacio.php' : 'login.php?volver=' . urlencode($_SERVER['REQUEST_URI'] ?? '')));
    exit;
}

function requerir_paciente(): void
{
    $u = usuario_actual();
    if ($u && $u['rol'] === 'Paciente') {
        return;
    }
    header('Location: ' . ($u ? 'index.php' : 'login.php'));
    exit;
}

/** Página a la que va cada rol después de entrar. */
function inicio_por_rol(string $rol): string
{
    return $rol === 'Paciente' ? 'mi_espacio.php' : 'index.php?vista=dashboard';
}

/* ----------------------------- Contraseñas ----------------------------- */

/** ¿El valor guardado ya es un hash de password_hash()? */
function es_hash_valido(string $guardado): bool
{
    return password_get_info($guardado)['algo'] !== null;
}

function contrasena_correcta(string $escrita, string $guardada): bool
{
    if (es_hash_valido($guardada)) {
        return password_verify($escrita, $guardada);
    }
    return hash_equals($guardada, $escrita);   // cuenta antigua en texto plano
}

/** Valida la fortaleza mínima de una contraseña nueva. Regresa el error o null. */
function error_contrasena(string $c): ?string
{
    if (mb_strlen($c) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $c) || !preg_match('/\d/', $c)) {
        return 'La contraseña debe combinar letras y números.';
    }
    return null;
}

/* ------------------------------- Login -------------------------------- */

/**
 * Busca la cuenta por correo (paciente, médico o personal) o por CURP (paciente).
 * Regresa los datos para la sesión más el hash guardado, o null.
 */
function buscar_cuenta(string $identificador): ?array
{
    $id = trim($identificador);
    $correo = mb_strtolower($id);
    $curp = strtoupper($id);

    $p = fila("SELECT u.id_usuario, u.rol, u.contrasena_hash, u.estatus AS estatus_usuario,
                      p.id_paciente AS id_perfil, p.nombre, p.ap_paterno, p.ap_materno, p.correo, p.estatus AS estatus_perfil
               FROM pacientes p JOIN usuarios u ON u.id_usuario = p.id_usuario
               WHERE LOWER(p.correo) = ? OR p.curp = ? LIMIT 1", [$correo, $curp]);
    if ($p) {
        return $p;
    }
    foreach (['medicos' => 'id_medico', 'personal' => 'id_personal'] as $tabla => $clave) {
        $r = fila("SELECT u.id_usuario, u.rol, u.contrasena_hash, u.estatus AS estatus_usuario,
                          t.$clave AS id_perfil, t.nombre, t.ap_paterno, t.ap_materno, t.correo, 'Activo' AS estatus_perfil
                   FROM $tabla t JOIN usuarios u ON u.id_usuario = t.id_usuario
                   WHERE LOWER(t.correo) = ? LIMIT 1", [$correo]);
        if ($r) {
            return $r;
        }
    }
    return null;
}

/**
 * Intenta iniciar sesión. Regresa null si entró, o el mensaje de error.
 * Lleva la cuenta de intentos fallidos en la sesión para frenar ataques de fuerza bruta.
 */
function iniciar_sesion(string $identificador, string $contrasena): ?string
{
    $bloqueo = $_SESSION['login_bloqueado_hasta'] ?? 0;
    if ($bloqueo > time()) {
        return 'Demasiados intentos. Espera ' . ($bloqueo - time()) . ' segundos e inténtalo de nuevo.';
    }

    $cuenta = buscar_cuenta($identificador);
    $ok = $cuenta && contrasena_correcta($contrasena, (string)$cuenta['contrasena_hash']);

    if (!$ok) {
        $_SESSION['login_fallidos'] = ($_SESSION['login_fallidos'] ?? 0) + 1;
        if ($_SESSION['login_fallidos'] >= MAX_INTENTOS) {
            $_SESSION['login_bloqueado_hasta'] = time() + BLOQUEO_SEGUNDOS;
            $_SESSION['login_fallidos'] = 0;
        }
        // Mismo mensaje si no existe la cuenta o si la contraseña es incorrecta
        return 'Correo/CURP o contraseña incorrectos.';
    }
    if ($cuenta['estatus_usuario'] !== 'Activo' || $cuenta['estatus_perfil'] !== 'Activo') {
        $estatus = $cuenta['estatus_usuario'] !== 'Activo' ? $cuenta['estatus_usuario'] : $cuenta['estatus_perfil'];
        return ($estatus === 'Bloqueado' ? 'Tu cuenta está bloqueada.' : 'Tu cuenta está dada de baja.')
             . ' Comunícate con recepción para reactivarla.';
    }

    // Convierte contraseñas antiguas (texto plano) o con algoritmo viejo a un hash actual
    if (!es_hash_valido($cuenta['contrasena_hash']) || password_needs_rehash($cuenta['contrasena_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario = ?')
            ->execute([password_hash($contrasena, PASSWORD_DEFAULT), $cuenta['id_usuario']]);
    }
    db()->prepare('UPDATE usuarios SET ultimo_acceso = ? WHERE id_usuario = ?')
        ->execute([date('Y-m-d H:i:s'), $cuenta['id_usuario']]);

    session_regenerate_id(true);   // evita fijación de sesión
    unset($_SESSION['login_fallidos'], $_SESSION['login_bloqueado_hasta']);
    $_SESSION['usuario'] = [
        'id_usuario' => (int)$cuenta['id_usuario'],
        'id_perfil' => (int)$cuenta['id_perfil'],
        'rol' => $cuenta['rol'],
        'nombre' => trim($cuenta['nombre'] . ' ' . $cuenta['ap_paterno'] . ' ' . ($cuenta['ap_materno'] ?? '')),
        'nombre_corto' => $cuenta['nombre'],
        'correo' => $cuenta['correo'],
        'iniciales' => mb_strtoupper(mb_substr($cuenta['nombre'], 0, 1) . mb_substr($cuenta['ap_paterno'], 0, 1)),
    ];
    return null;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Ruta interna segura para regresar después del login (evita redirecciones a otros sitios). */
function destino_seguro(?string $volver, string $porDefecto): string
{
    $volver = (string)$volver;
    $ruta = parse_url($volver, PHP_URL_PATH) ?? '';
    if ($volver === '' || parse_url($volver, PHP_URL_HOST) || basename($ruta) !== 'index.php') {
        return $porDefecto;
    }
    $consulta = parse_url($volver, PHP_URL_QUERY);
    return 'index.php' . ($consulta ? '?' . $consulta : '');
}

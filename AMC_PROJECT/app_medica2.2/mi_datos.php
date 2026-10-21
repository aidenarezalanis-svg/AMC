<?php
/**
 * AMC-4: el paciente guarda sus datos personales desde mi_espacio.php.
 * El id del paciente sale de la sesión, nunca del formulario.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/perfil.php';
requerir_paciente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        flash('datos_error', 'Tu sesión expiró. Vuelve a intentarlo.');
    } else {
        $r = actualizar_datos_paciente(usuario_actual()['id_perfil'], $_POST);
        if ($r['ok']) {
            unset($_SESSION['viejo'], $_SESSION['errores_datos']);
            flash('datos_ok', $r['mensaje']);
        } else {
            // Se conserva lo capturado para no obligar a escribir todo de nuevo
            $_SESSION['viejo'] = array_intersect_key($_POST, CAMPOS_PERFIL);
            $_SESSION['errores_datos'] = $r['errores'];
            flash('datos_error', $r['mensaje']);
        }
    }
}
header('Location: mi_espacio.php#mis-datos');
exit;

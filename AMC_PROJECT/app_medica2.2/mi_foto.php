<?php
/**
 * AMC-17: el paciente sube o cambia su propia foto de perfil desde mi_espacio.php.
 * El id del paciente sale de la sesión, nunca del formulario.
 */
require_once __DIR__ . '/includes/bootstrap.php';
requerir_paciente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // El archivo rebasó post_max_size de PHP: llegan $_POST y $_FILES vacíos
        flash('foto_error', 'El archivo es demasiado grande. La foto debe pesar máximo 2 MB.');
    } elseif (!csrf_valido($_POST['csrf'] ?? null)) {
        flash('foto_error', 'Tu sesión expiró. Vuelve a intentarlo.');
    } else {
        $error = guardar_foto_paciente(usuario_actual()['id_perfil'], $_FILES['foto'] ?? null);
        flash($error ? 'foto_error' : 'foto_ok', $error ?? 'Tu foto de perfil se actualizó.');
    }
}
header('Location: mi_espacio.php');
exit;

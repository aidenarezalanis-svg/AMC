<?php
/** Cierra la sesión (solo por POST con token, para que un enlace externo no pueda cerrarla). */
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null)) {
    cerrar_sesion();
    header('Location: login.php?salida=1');
    exit;
}
header('Location: login.php');
exit;

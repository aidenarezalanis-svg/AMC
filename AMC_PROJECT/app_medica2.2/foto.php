<?php
/**
 * Entrega la foto de perfil de un paciente (AMC-16).
 * El personal puede ver cualquier foto; un paciente solo la suya.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$u = usuario_actual();
$id = (int)($_GET['id'] ?? 0);
$permitido = $u && (es_personal() || ($u['rol'] === 'Paciente' && $u['id_perfil'] === $id));

$fila = $permitido ? fila('SELECT * FROM pacientes WHERE id_paciente = ?', [$id]) : null;
$ruta = $fila ? ruta_foto($fila['foto_perfil'] ?? null) : null;

if (!$ruta) {
    http_response_code($permitido ? 404 : 403);
    exit;
}

header('Content-Type: ' . (str_ends_with($ruta, '.png') ? 'image/png' : 'image/jpeg'));
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
readfile($ruta);

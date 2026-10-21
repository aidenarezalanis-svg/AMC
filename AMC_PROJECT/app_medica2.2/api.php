<?php
/**
 * API JSON de solo lectura.
 *   api.php?accion=buscar&q=texto                    → pacientes, médicos y medicamentos (Ctrl+K)
 *   api.php?accion=pacientes&q=texto                 → pacientes activos (buscador de Agendar)
 *   api.php?accion=horarios&medico=ID&fecha=AAAA-MM-DD → turnos ocupados/libres del día y próximos libres
 */
require_once __DIR__ . '/includes/bootstrap.php';
requerir_personal(true);

header('Content-Type: application/json; charset=utf-8');

$accion = $_GET['accion'] ?? '';
$q = trim((string)($_GET['q'] ?? ''));

function paciente_json(array $p): array
{
    return [
        'id' => (int)$p['id_paciente'],
        'nombre_completo' => $p['nombre_completo'],
        'iniciales' => $p['iniciales'],
        'curp' => $p['curp'],
        'telefono' => $p['telefono'],
    ];
}

function responder(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($accion) {
    case 'buscar':
        if (mb_strlen($q) < 2) {
            responder(['pacientes' => [], 'medicos' => [], 'medicamentos' => []]);
        }
        responder([
            'pacientes' => array_map('paciente_json', buscar_pacientes($q, 4)),
            'medicos' => array_map(fn($m) => [
                'id' => (int)$m['id_medico'],
                'nombre' => $m['nombre_completo'],
                'especialidad' => implode(', ', array_column($m['especialidades'], 'nombre')),
                'consultorio' => implode(', ', array_column($m['consultorios'], 'nombre')),
            ], buscar_medicos($q, 3)),
            'medicamentos' => array_map(fn($m) => [
                'id' => (int)$m['id_medicamento'],
                'nombre' => $m['nombre_comercial'],
                'concentracion' => $m['principio_activo'] . ' · ' . $m['presentacion'],
                'stock' => $m['stock_actual'],
            ], buscar_medicamentos($q, 3)),
        ]);

    case 'pacientes':
        responder(['pacientes' => array_map('paciente_json', buscar_pacientes($q, 6))]);

    case 'horarios':
        $idMedico = (int)($_GET['medico'] ?? 0);
        $fecha = (string)($_GET['fecha'] ?? '');
        if (!$idMedico || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            responder(['dia' => [], 'proximos' => []]);
        }
        responder([
            'dia' => horarios_del_dia($idMedico, $fecha),
            'proximos' => array_map(fn($h) => [
                'fecha' => $h['fecha'],
                'hora' => hora_corta($h['hora_inicio']),
                'consultorio_id' => (int)$h['id_consultorio'],
                'consultorio' => $h['nombre_consultorio'],
            ], proximos_horarios_libres($idMedico)),
        ]);
}

http_response_code(400);
responder(['error' => 'Acción no válida']);

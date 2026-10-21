<?php
/**
 * App Médica — punto de entrada y enrutador de vistas.
 * Cada opción del menú apunta a index.php?vista=<id>.
 */
require_once __DIR__ . '/includes/bootstrap.php';
requerir_personal();

// id del menú => [archivo de la vista, título de la página]
$rutas = [
    'dashboard'      => ['dashboard', 'Panel Administrativo'],
    'agendar'        => ['agendar', 'Agendar Cita'],
    'pacientes'      => ['pacientes', 'Pacientes'],
    'citas'          => ['citas', 'Agenda de Citas'],
    'medicos'        => ['medicos', 'Médicos'],
    'especialidades' => ['medicos', 'Especialidades'],
    'consultorios'   => ['medicos', 'Consultorios'],
    'medicamentos'   => ['inventario', 'Medicamentos'],
    'inventario'     => ['inventario', 'Inventario'],
    'compras'        => ['inventario', 'Compras'],
    'ventas'         => ['inventario', 'Ventas'],
    'reportes'       => ['reportes', 'Reportes'],
    'seguimiento'    => ['reportes', 'Seguimiento'],
];

$vistaActiva = $_GET['vista'] ?? 'agendar';
if (!isset($rutas[$vistaActiva])) {
    $vistaActiva = 'agendar';
}
[$archivoVista, $tituloPagina] = $rutas[$vistaActiva];
$scriptsVista = [];

// La vista se genera primero para que pueda registrar sus scripts propios
ob_start();
require __DIR__ . '/vistas/' . $archivoVista . '.php';
$contenidoVista = ob_get_clean();

require __DIR__ . '/includes/layout_inicio.php';
echo $contenidoVista;
require __DIR__ . '/includes/layout_fin.php';

limpiar_viejo();

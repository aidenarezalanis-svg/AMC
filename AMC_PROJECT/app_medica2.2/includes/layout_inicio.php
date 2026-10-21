<?php
/**
 * Inicio del layout: <head>, barra lateral y encabezado superior.
 * Variables esperadas: $vistaActiva (id del menú) y $tituloPagina.
 */
$sedeActual = sede_actual();
$listaSedes = consultorios();
$usuario = usuario_sesion();
$listaNotificaciones = notificaciones();
$noLeidas = count(array_filter($listaNotificaciones, fn($n) => !$n['leido']));
$alertasInventario = count(medicamentos_criticos());
$volver = $_SERVER['REQUEST_URI'] ?? 'index.php';

$menu = [
    'CLÍNICA' => [
        ['dashboard', 'Dashboard', 'layout-dashboard'],
        ['agendar', 'Agendar Cita', 'calendar-plus'],
        ['pacientes', 'Pacientes', 'users'],
        ['medicos', 'Médicos', 'stethoscope'],
        ['citas', 'Citas', 'calendar'],
        ['especialidades', 'Especialidades', 'sparkles'],
        ['consultorios', 'Consultorios', 'building'],
    ],
    'FARMACIA & OPERACIONES' => [
        ['medicamentos', 'Medicamentos', 'pill'],
        ['inventario', 'Inventario', 'package', $alertasInventario ? $alertasInventario . ($alertasInventario === 1 ? ' Alerta' : ' Alertas') : null],
        ['compras', 'Compras', 'shopping-cart'],
        ['ventas', 'Ventas', 'receipt'],
    ],
    'GESTIÓN & CONTROL' => [
        ['reportes', 'Reportes', 'chart-column'],
        ['seguimiento', 'Seguimiento', 'activity'],
    ],
];
?><!doctype html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($tituloPagina) ?> — <?= e(APP_NOMBRE) ?></title>
  <meta name="description" content="Plataforma clínica para gestión de citas médicas, pacientes, farmacia y panel administrativo.">

  <!-- Tema: se aplica antes de pintar para evitar parpadeo -->
  <script>window.APP_TEMA = <?= tema_json() ?>;</script>
  <script src="assets/js/tema.js"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400..700&family=JetBrains+Mono:wght@400;500;600&family=Lexend:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300..800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS v4 (CDN) con modo oscuro por clase -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">
    @custom-variant dark (&:where(.dark, .dark *));
  </style>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-teal-500/20 transition-colors duration-200">
<div class="min-h-screen flex flex-col">

  <!-- ===================== Barra lateral ===================== -->
  <div id="sidebar-overlay" data-accion="cerrar-sidebar" class="hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-xs lg:hidden"></div>

  <aside id="sidebar" class="fixed top-0 bottom-0 left-0 z-40 w-64 bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800/80 flex flex-col transition-transform duration-200 -translate-x-full lg:translate-x-0">
    <div class="h-16 px-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
      <a href="<?= e(url('dashboard')) ?>" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-xs bg-primary">
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/></svg>
        </div>
        <div>
          <p class="text-sm font-bold tracking-tight text-slate-900 dark:text-white leading-none"><?= e(APP_NOMBRE) ?></p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 leading-none font-medium">Sistema Clínico Integral</p>
        </div>
      </a>
      <button type="button" data-accion="cerrar-sidebar" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Cerrar menú">
        <?= icono('x', 'w-5 h-5') ?>
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
      <?php foreach ($menu as $grupo => $items): ?>
        <div>
          <div class="px-3 mb-2 text-[10px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase"><?= e($grupo) ?></div>
          <div class="space-y-0.5">
            <?php foreach ($items as $item):
              [$id, $etiqueta, $ico] = $item;
              $insignia = $item[3] ?? null;
              $activo = $vistaActiva === $id; ?>
              <a href="<?= e(url($id)) ?>"
                 class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors <?= $activo
                    ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200' ?>"
                 <?= $activo ? 'aria-current="page"' : '' ?>>
                <span class="flex items-center gap-3 truncate">
                  <?= icono($ico, 'w-4 h-4 ' . ($activo ? 'text-primary' : 'text-slate-400 dark:text-slate-500')) ?>
                  <span class="truncate"><?= e($etiqueta) ?></span>
                </span>
                <?php if ($insignia): ?>
                  <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono shrink-0 bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"><?= e($insignia) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 space-y-2">
      <button type="button" data-accion="alternar-oscuro"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/70 transition-colors cursor-pointer">
        <span class="flex items-center gap-2">
          <span class="dark:hidden"><?= icono('moon', 'w-3.5 h-3.5 text-slate-600') ?></span>
          <span class="hidden dark:inline"><?= icono('sun', 'w-3.5 h-3.5 text-amber-400') ?></span>
          <span>Modo: <span class="dark:hidden">Claro</span><span class="hidden dark:inline">Oscuro</span></span>
        </span>
        <span class="text-[10px] text-slate-400">Alternar</span>
      </button>
      <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 px-1">
        <span>Versión 2.4-PHP</span>
        <span class="font-mono">v2.4.0</span>
      </div>
    </div>
  </aside>

  <!-- ===================== Contenido ===================== -->
  <div class="lg:pl-64 flex flex-col flex-1 min-w-0">

    <header class="sticky top-0 z-30 h-16 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 px-4 sm:px-6 transition-colors flex items-center justify-between gap-4">

      <!-- Zona 1: menú móvil y sede -->
      <div class="flex items-center gap-3 shrink-0">
        <button type="button" data-accion="abrir-sidebar" class="lg:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Abrir menú">
          <?= icono('menu', 'w-5 h-5') ?>
        </button>

        <div class="relative" data-desplegable>
          <button type="button" data-desplegable-boton
                  class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
            <?= icono('building-2', 'w-3.5 h-3.5 text-slate-500 dark:text-slate-400') ?>
            <span class="truncate max-w-[140px] sm:max-w-[240px]"><?= e($sedeActual['nombre_consultorio']) ?> · <?= e($sedeActual['ciudad']) ?></span>
            <?= icono('chevron-down', 'w-3 h-3 text-slate-400 ml-0.5') ?>
          </button>
          <div data-desplegable-panel hidden class="animate-in absolute left-0 mt-2 w-72 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 py-1.5 z-40">
            <div class="px-3 py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Cambiar Consultorio / Sede</span>
            </div>
            <?php foreach ($listaSedes as $s): $sel = (int)$s['id_consultorio'] === (int)$sedeActual['id_consultorio']; ?>
              <form method="post" action="acciones.php">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="cambiar_sede">
                <input type="hidden" name="sede_id" value="<?= (int)$s['id_consultorio'] ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">
                <button type="submit" class="w-full flex items-center justify-between px-3 py-2 text-left text-xs transition-colors cursor-pointer <?= $sel
                    ? 'bg-slate-100 dark:bg-slate-800/80 text-teal-600 dark:text-teal-400 font-medium'
                    : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40' ?>">
                  <span class="truncate">
                    <span class="block font-medium truncate"><?= e($s['nombre_consultorio']) ?></span>
                    <span class="block text-[10px] text-slate-400 truncate"><?= e($s['direccion']) ?>, <?= e($s['ciudad']) ?> · Tel. <?= e($s['telefono']) ?></span>
                  </span>
                  <?php if ($sel): ?><?= icono('check', 'w-3.5 h-3.5 text-teal-600 dark:text-teal-400 ml-2') ?><?php endif; ?>
                </button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="hidden xl:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 text-xs font-medium">
          <?= icono('circle-check', 'w-3.5 h-3.5 text-emerald-500') ?>
          <span>Online / DB Sincronizada</span>
        </div>
      </div>

      <!-- Zona 2: buscador universal -->
      <div class="flex-1 max-w-md mx-2 hidden md:block">
        <button type="button" data-accion="abrir-busqueda"
                class="w-full flex items-center justify-between px-3.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-800/50 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 text-xs transition-colors cursor-pointer">
          <span class="flex items-center gap-2">
            <?= icono('search', 'w-3.5 h-3.5') ?>
            <span>Buscar paciente, médico o medicamento...</span>
          </span>
          <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded text-slate-500 dark:text-slate-400">Ctrl K</kbd>
        </button>
      </div>

      <!-- Zona 3: acciones -->
      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <button type="button" data-accion="abrir-busqueda" class="md:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Buscar">
          <?= icono('search', 'w-4 h-4') ?>
        </button>

        <button type="button" data-accion="abrir-tema" title="Personalizar colores y tipografía" aria-label="Personalizar tema"
                class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
          <?= icono('palette', 'w-4 h-4') ?>
        </button>

        <button type="button" data-accion="alternar-oscuro" title="Cambiar entre modo claro y oscuro"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all cursor-pointer bg-slate-100/80 border-slate-200 text-slate-700 hover:bg-slate-200/80 dark:bg-slate-800 dark:border-slate-700 dark:text-amber-300 dark:hover:bg-slate-700">
          <span class="dark:hidden"><?= icono('moon', 'w-4 h-4') ?></span>
          <span class="hidden dark:inline"><?= icono('sun', 'w-4 h-4 text-amber-400') ?></span>
          <span class="hidden sm:inline font-semibold"><span class="dark:hidden">Claro</span><span class="hidden dark:inline">Oscuro</span></span>
        </button>

        <!-- Notificaciones -->
        <div class="relative" data-desplegable>
          <button type="button" data-desplegable-boton class="relative p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" aria-label="Notificaciones">
            <?= icono('bell', 'w-4 h-4') ?>
            <span id="punto-notificaciones" <?= $noLeidas ? '' : 'hidden' ?> class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
          </button>
          <div data-desplegable-panel hidden class="animate-in absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden z-40">
            <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-900 dark:text-white">Centro de Notificaciones</span>
              <span class="text-[10px] text-slate-400 font-mono"><span id="contador-notificaciones"><?= $noLeidas ?></span> nuevas</span>
            </div>
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
              <?php if (!$listaNotificaciones): ?>
                <p class="p-4 text-xs text-center text-slate-400">Sin notificaciones.</p>
              <?php endif; ?>
              <?php foreach ($listaNotificaciones as $n):
                $leida = (int)$n['leido'];
                $icoN = $n['tipo'] === 'alerta' ? icono('triangle-alert', 'w-4 h-4 text-amber-500')
                      : ($n['tipo'] === 'exito' ? icono('circle-check', 'w-4 h-4 text-emerald-500') : icono('bell', 'w-4 h-4 text-teal-500')); ?>
                <div data-notificacion="<?= e($n['id']) ?>" data-leida="<?= $leida ?>"
                     class="p-3 text-left transition-colors cursor-pointer <?= $leida ? 'hover:bg-slate-50 dark:hover:bg-slate-800/40' : 'bg-teal-50/40 dark:bg-teal-950/20' ?>">
                  <div class="flex items-start gap-2.5">
                    <div class="mt-0.5 shrink-0"><?= $icoN ?></div>
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-900 dark:text-white truncate"><?= e($n['titulo']) ?></p>
                        <span class="text-[10px] text-slate-400 whitespace-nowrap ml-2"><?= e($n['fecha'] ? tiempo_relativo($n['fecha']) : 'Ahora') ?></span>
                      </div>
                      <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5 leading-snug"><?= e($n['mensaje']) ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <a href="<?= e(url('agendar')) ?>" class="bg-primary flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white rounded-lg shadow-xs hover:opacity-90 transition-all whitespace-nowrap">
          <?= icono('plus', 'w-3.5 h-3.5') ?>
          <span class="hidden sm:inline">Nueva Cita</span>
        </a>

        <!-- Perfil -->
        <div class="relative pl-1 border-l border-slate-200 dark:border-slate-800" data-desplegable>
          <button type="button" data-desplegable-boton class="flex items-center gap-2 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left">
            <span class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xs shadow-xs"><?= e($usuario['iniciales']) ?></span>
            <span class="hidden lg:block text-left pr-1">
              <span class="block text-xs font-semibold text-slate-900 dark:text-white leading-tight"><?= e($usuario['nombre']) ?></span>
              <span class="block text-[10px] text-slate-400 leading-tight"><?= e($usuario['rol']) ?></span>
            </span>
            <?= icono('chevron-down', 'w-3 h-3 text-slate-400 hidden lg:block') ?>
          </button>
          <div data-desplegable-panel hidden class="animate-in absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 py-1.5 z-40">
            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
              <p class="text-xs font-semibold text-slate-900 dark:text-white"><?= e($usuario['nombre']) ?></p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= e($usuario['correo']) ?></p>
              <span class="inline-block mt-1 text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">Usuario #<?= (int)$usuario['id_usuario'] ?> · <?= e($usuario['rol']) ?></span>
            </div>
            <form method="post" action="salir.php">
              <?= csrf_campo() ?>
              <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 text-left cursor-pointer">
                <?= icono('log-out', 'w-3.5 h-3.5') ?>
                <span>Cerrar sesión</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">

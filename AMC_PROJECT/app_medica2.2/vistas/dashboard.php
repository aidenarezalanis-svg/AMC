<?php
/** Vista: Panel administrativo (Dashboard) */
$sede = sede_actual();
$citasHoy = citas_de_hoy();
$proximas = proximas_citas(8);
$listaMedicos = medicos();
$listaMedicamentos = medicamentos();
$criticos = medicamentos_criticos();

$cuenta = fn(array $estados) => count(array_filter($citasHoy, fn($c) => in_array($c['estatus_cita'], $estados, true)));
$completadas = $cuenta(['Completada']);
$pendientes = $cuenta(['Programada', 'Confirmada']);
$canceladas = $cuenta(['Cancelada']);

$facturacion = ventas_del_dia(hoy());
$stockTotal = array_sum(array_column($listaMedicamentos, 'stock_actual'));
$nuevosSemana = (int)valor('SELECT COUNT(*) FROM pacientes WHERE fecha_registro >= ?', [date('Y-m-d', strtotime('-6 days'))]);

$reordenOk = flash('reorden_ok');
$estadoOk = flash('estado_ok');
$error = flash('error');

// Siguiente paso del flujo de atención según el estatus de la cita
$siguientePaso = [
    'Programada' => ['Confirmada', 'Confirmar', 'bg-amber-500 hover:bg-amber-600'],
    'Confirmada' => ['Completada', 'Marcar atendida', 'bg-emerald-600 hover:bg-emerald-700'],
];

// Consultorios con los médicos que atienden hoy
$hoyDia = dia_atencion(hoy());
$ocupacion = [];
foreach (consultorios() as $c) {
    $ocupacion[$c['id_consultorio']] = ['consultorio' => $c, 'medicos' => [], 'hoy' => false];
}
foreach ($listaMedicos as $m) {
    foreach ($m['consultorios'] as $c) {
        $atiende = atiende_el_dia($c['dias'], hoy());
        $ocupacion[$c['id']]['medicos'][] = ['nombre' => $m['nombre_completo'], 'dias' => $c['dias'], 'hoy' => $atiende, 'id' => $m['id_medico']];
        $ocupacion[$c['id']]['hoy'] = $ocupacion[$c['id']]['hoy'] || $atiende;
    }
}
?>
<div class="space-y-6 pb-12">

  <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-colors">
    <div class="flex items-start sm:items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center shrink-0">
        <?= icono('circle-check', 'w-5 h-5') ?>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-900 dark:text-white">Base de datos sincronizada</span>
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-semibold uppercase tracking-wider border border-emerald-200 dark:border-emerald-800">EN VIVO</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          <?= count($citasHoy) ?> citas para la jornada de hoy (<?= e($hoyDia) ?> <?= e(hoy()) ?>). Datos leídos directamente de <code class="font-mono text-slate-600 dark:text-slate-300"><?= e(DB_NAME) ?></code>.
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2 self-end sm:self-auto">
      <button type="button" data-accion="recargar" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Refrescar">
        <?= icono('refresh-cw', 'w-3.5 h-3.5') ?>
      </button>
      <a href="<?= e(url('reportes')) ?>" class="text-xs font-medium text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1">
        <span>Ver reporte</span><?= icono('arrow-right', 'w-3.5 h-3.5') ?>
      </a>
    </div>
  </div>

  <?= encabezado_vista(
      'SEDE · ' . $sede['nombre_consultorio'] . ' — ' . $sede['ciudad'],
      'Panel Administrativo — Clínica Central',
      'Resumen operativo general de atención a pacientes, farmacia activa y asignación de consultorios.',
      boton_primario(url('agendar'), 'plus', 'Nueva Cita Rápida')
  ) ?>

  <?php if ($reordenOk): ?><?= alerta('exito', $reordenOk, '', 5000) ?><?php endif; ?>
  <?php if ($estadoOk): ?><?= alerta('exito', $estadoOk, '', 4000) ?><?php endif; ?>
  <?php if ($error): ?><?= alerta('error', $error) ?><?php endif; ?>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $kpis = [
        ['pacientes', 'Pacientes Activos', 'users', number_format(total_pacientes_activos()),
         '<span>Total en Pacientes</span><span class="text-teal-600 dark:text-teal-400 font-medium">+' . $nuevosSemana . ' esta semana</span>', 'flex items-center justify-between text-slate-500 dark:text-slate-400'],
        ['citas', 'Citas de la Jornada', 'calendar', count($citasHoy),
         '<span class="tabular-nums font-medium">' . $completadas . ' atendidas</span><span class="mx-1">·</span><span class="tabular-nums font-medium">' . $pendientes . ' pendientes</span><span class="mx-1">·</span><span class="tabular-nums font-medium text-rose-500">' . $canceladas . ' canc.</span>', 'text-slate-500 dark:text-slate-400 truncate'],
        ['ventas', 'Facturación del Día', 'dollar-sign', dinero($facturacion, 2),
         '<span>Suma de venta.total de hoy</span>', 'text-slate-500 dark:text-slate-400 truncate'],
        ['inventario', 'Stock Medicamentos', 'package', number_format($stockTotal),
         count($criticos) . ' medicamento(s) por reordenar', 'text-rose-600 dark:text-rose-400 font-medium truncate'],
    ];
    foreach ($kpis as [$destino, $titulo, $ico, $valorKpi, $pie, $clasesPie]): ?>
      <a href="<?= e(url($destino)) ?>" class="group bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800/80 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all">
        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
          <span class="text-[11px] font-bold uppercase tracking-wider"><?= e($titulo) ?></span>
          <?= icono($ico, 'w-4 h-4 text-slate-400 group-hover:text-teal-600 transition-colors') ?>
        </div>
        <div class="text-3xl font-extrabold text-slate-900 dark:text-white tabular-nums tracking-tight"><?= e($valorKpi) ?></div>
        <div class="text-xs mt-2 <?= $clasesPie ?>"><?= $pie ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800/80 p-5 shadow-xs transition-colors">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
      <div class="flex items-center gap-2.5">
        <?= icono('triangle-alert', 'w-4 h-4 text-amber-500') ?>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Alertas Críticas de Inventario</h2>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-medium"><?= STOCK_MINIMO ?> u. o menos</span>
      </div>
      <a href="<?= e(url('inventario')) ?>" class="text-xs font-medium text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 self-start sm:self-auto">
        <span>Ir a Gestión de Inventario</span><?= icono('arrow-right', 'w-3.5 h-3.5') ?>
      </a>
    </div>
    <?php if (!$criticos): ?>
      <p class="text-xs text-slate-500 dark:text-slate-400">Todo el inventario está por encima de <?= STOCK_MINIMO ?> unidades.</p>
    <?php endif; ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <?php foreach ($criticos as $m): ?>
        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= e($m['nombre_comercial']) ?></p>
            <p class="text-[11px] text-rose-600 dark:text-rose-400 font-medium mt-0.5">
              <span class="font-mono font-bold tabular-nums"><?= $m['stock_actual'] ?></span> unidades restantes
              <span class="text-slate-400 dark:text-slate-500 font-normal"> · Mínimo <?= STOCK_MINIMO ?> u.</span>
            </p>
            <p class="text-[10px] text-slate-400 mt-0.5"><?= e($m['principio_activo']) ?> · <?= e($m['presentacion']) ?></p>
          </div>
          <?= boton_accion('reordenar', ['id' => $m['id_medicamento'], 'cantidad' => CANTIDAD_REORDEN], 'Reordenar +' . CANTIDAD_REORDEN,
              'px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-2xs transition-colors whitespace-nowrap') ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800/80 p-5 shadow-xs transition-colors">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-sm font-bold text-slate-900 dark:text-white">Turnos de Hoy y Próximas Citas</h2>
          <p class="text-[11px] text-slate-400">Citas programadas y confirmadas a partir de hoy</p>
        </div>
        <a href="<?= e(url('citas')) ?>" class="text-xs text-teal-600 dark:text-teal-400 hover:underline">Ver agenda completa →</a>
      </div>

      <?php if (!$proximas): ?>
        <div class="py-8 text-center">
          <p class="text-xs text-slate-500 dark:text-slate-400">No hay citas pendientes de hoy en adelante.</p>
          <a href="<?= e(url('agendar')) ?>" class="inline-block mt-2 text-xs font-medium text-teal-600 dark:text-teal-400 hover:underline">Agendar una cita →</a>
        </div>
      <?php endif; ?>
      <div class="space-y-2.5">
        <?php foreach ($proximas as $c): ?>
          <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50/40 dark:bg-slate-800/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center shrink-0">
                <span class="text-[10px] font-mono font-bold text-slate-800 dark:text-slate-200"><?= e($c['hora']) ?></span>
                <span class="text-[9px] font-mono <?= $c['es_hoy'] ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-slate-400' ?>"><?= $c['es_hoy'] ? 'HOY' : e(substr($c['fecha'], 5)) ?></span>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= e($c['paciente_nombre']) ?></p>
                  <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border whitespace-nowrap <?= clases_estado_cita($c['estatus_cita']) ?>"><?= e($c['estatus_cita']) ?></span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                  <?= e($c['medico']) ?> · <span class="font-medium text-slate-700 dark:text-slate-300"><?= e($c['nombre_especialidad']) ?></span> (<?= e($c['nombre_consultorio']) ?>)
                </p>
              </div>
            </div>
            <div class="flex items-center gap-1.5 self-end sm:self-auto shrink-0">
              <?php if (isset($siguientePaso[$c['estatus_cita']])): [$nuevo, $texto, $color] = $siguientePaso[$c['estatus_cita']]; ?>
                <?= boton_accion('estado_cita', ['id' => $c['id_cita'], 'estado' => $nuevo], e($texto), 'px-2.5 py-1 text-[11px] font-medium rounded-lg text-white ' . $color) ?>
              <?php endif; ?>
              <span class="text-[10px] font-mono text-slate-400 ml-1">#<?= (int)$c['id_cita'] ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="lg:col-span-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800/80 p-5 shadow-xs transition-colors">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-sm font-bold text-slate-900 dark:text-white">Consultorios Activos</h2>
          <p class="text-[11px] text-slate-400">Médicos que atienden hoy (<?= e($hoyDia) ?>)</p>
        </div>
      </div>
      <div class="space-y-2.5">
        <?php foreach ($ocupacion as $o): $c = $o['consultorio']; ?>
          <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50/30 dark:bg-slate-800/20">
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full <?= $o['hoy'] ? 'bg-emerald-500' : 'bg-slate-400' ?>" title="<?= $o['hoy'] ? 'Con atención hoy' : 'Sin atención hoy' ?>"></span>
              <span class="text-xs font-bold text-slate-900 dark:text-white"><?= e($c['nombre_consultorio']) ?></span>
              <span class="text-[10px] text-slate-400"><?= e($c['ciudad']) ?></span>
            </div>
            <?php if (!$o['medicos']): ?>
              <p class="text-[11px] text-slate-400 mt-1">Sin médicos asignados</p>
            <?php endif; ?>
            <?php foreach ($o['medicos'] as $m): ?>
              <a href="<?= e(url('agendar', ['medico' => $m['id']])) ?>" class="flex items-center justify-between mt-1 group">
                <span class="text-[11px] <?= $m['hoy'] ? 'text-slate-700 dark:text-slate-200 font-medium' : 'text-slate-500 dark:text-slate-400' ?>"><?= e($m['nombre']) ?></span>
                <span class="text-[10px] font-mono text-slate-400 group-hover:text-teal-600"><?= e($m['dias']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

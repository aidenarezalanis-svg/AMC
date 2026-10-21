<?php
/** Vista: Reportes operativos calculados con los datos reales de la base */
$todas = citas();
$total = count($todas);
$porEstado = array_count_values(array_column($todas, 'estatus_cita'));
$completadas = $porEstado['Completada'] ?? 0;
$canceladas = $porEstado['Cancelada'] ?? 0;
$pct = fn($n, $d) => $d ? round($n * 100 / $d, 1) : 0;

$mesActual = date('Y-m');
$mesAnterior = date('Y-m', strtotime('first day of last month'));
$ventasMes = [];
foreach (filas("SELECT SUBSTR(fecha_venta, 1, 7) AS mes, COUNT(*) AS num, SUM(total) AS total
                FROM venta WHERE estatus_venta = 'Completada' AND fecha_venta >= ?
                GROUP BY SUBSTR(fecha_venta, 1, 7) ORDER BY mes", [date('Y') . '-01-01']) as $v) {
    $ventasMes[$v['mes']] = ['num' => (int)$v['num'], 'total' => (float)$v['total']];
}
$totalMes = $ventasMes[$mesActual]['total'] ?? 0;
$totalAnterior = $ventasMes[$mesAnterior]['total'] ?? 0;
$variacion = $totalAnterior ? round(($totalMes - $totalAnterior) * 100 / $totalAnterior, 1) : null;
$maxVentas = max(array_merge([1], array_column($ventasMes, 'total')));

$porEspecialidad = filas('SELECT e.nombre_especialidad, COUNT(c.id_cita) AS num
                          FROM especialidades e LEFT JOIN citas c ON c.id_especialidad = e.id_especialidad
                          GROUP BY e.id_especialidad, e.nombre_especialidad ORDER BY num DESC, e.nombre_especialidad');
$maxEsp = max(array_merge([1], array_map('intval', array_column($porEspecialidad, 'num'))));

$seguimiento = filas('SELECT s.nombre_cliente, s.fecha_hora, s.id_paciente FROM seguimiento_clientes s ORDER BY s.fecha_hora DESC LIMIT 10');
$meses = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
          '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
?>
<div class="space-y-6 pb-12">
  <?= encabezado_vista('GESTIÓN & CONTROL / REPORTES', 'Reportes Operativos y Estadísticas Clínicas',
      'Indicadores calculados en tiempo real a partir de citas, ventas y seguimiento de clientes.') ?>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <span class="text-xs font-semibold text-slate-400 uppercase">Citas Completadas</span>
      <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1 tabular-nums"><?= $pct($completadas, $total) ?>%</div>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-2"><?= $completadas ?> de <?= $total ?> citas registradas</p>
    </div>
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <span class="text-xs font-semibold text-slate-400 uppercase">Tasa de Cancelación</span>
      <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1 tabular-nums"><?= $pct($canceladas, $total) ?>%</div>
      <p class="text-xs mt-2 font-medium <?= $pct($canceladas, $total) <= 10 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>"><?= $canceladas ?> cita(s) cancelada(s)</p>
    </div>
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <span class="text-xs font-semibold text-slate-400 uppercase">Ventas del Mes (<?= $meses[date('m')] ?>)</span>
      <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1 tabular-nums"><?= dinero($totalMes, 2) ?></div>
      <p class="text-xs mt-2 font-medium <?= $variacion === null ? 'text-slate-500 dark:text-slate-400' : ($variacion >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') ?>">
        <?= $variacion === null ? 'Sin ventas el mes anterior para comparar' : ($variacion >= 0 ? '+' : '') . $variacion . '% vs ' . $meses[substr($mesAnterior, 5)] ?>
      </p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Citas por Especialidad</h3>
      <div class="space-y-3">
        <?php foreach ($porEspecialidad as $r): ?>
          <div class="space-y-1">
            <div class="flex justify-between text-xs">
              <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($r['nombre_especialidad']) ?></span>
              <span class="font-mono text-slate-500"><?= (int)$r['num'] ?> citas</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div class="bg-primary h-full rounded-full transition-all" style="width: <?= round((int)$r['num'] * 100 / $maxEsp) ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Ventas por Mes (<?= date('Y') ?>)</h3>
      <?php if (!$ventasMes): ?><p class="text-xs text-slate-400">Sin ventas registradas este año.</p><?php endif; ?>
      <div class="space-y-3">
        <?php foreach ($ventasMes as $mes => $v): ?>
          <div class="space-y-1">
            <div class="flex justify-between text-xs">
              <span class="font-semibold text-slate-700 dark:text-slate-300"><?= $meses[substr($mes, 5)] ?> <span class="font-normal text-slate-400">· <?= $v['num'] ?> venta(s)</span></span>
              <span class="font-mono text-slate-500"><?= dinero($v['total'], 2) ?></span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div class="bg-primary h-full rounded-full transition-all" style="width: <?= round($v['total'] * 100 / $maxVentas) ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Citas por Estatus</h3>
      <div class="grid grid-cols-2 gap-3">
        <?php foreach (ESTADOS_CITA as $estado): ?>
          <div class="p-3 rounded-xl border <?= clases_estado_cita($estado) ?>">
            <p class="text-[10px] uppercase font-semibold opacity-80"><?= e($estado) ?></p>
            <p class="text-xl font-extrabold tabular-nums mt-0.5"><?= (int)($porEstado[$estado] ?? 0) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="seguimiento" class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Seguimiento de Clientes</h3>
      <p class="text-[11px] text-slate-400 mb-3">Últimas visitas registradas por el trigger de ventas (seguimiento_clientes)</p>
      <div class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach ($seguimiento as $s): ?>
          <a href="<?= e(url('pacientes', ['id' => $s['id_paciente']])) ?>" class="py-2 flex items-center justify-between text-xs hover:text-teal-600">
            <span class="text-slate-700 dark:text-slate-300"><?= e(trim($s['nombre_cliente'])) ?></span>
            <span class="font-mono text-slate-400"><?= e(substr($s['fecha_hora'], 0, 16)) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php if ($vistaActiva === 'seguimiento'): ?>
<script>document.getElementById('seguimiento').scrollIntoView({ block: 'start' });</script>
<?php endif; ?>

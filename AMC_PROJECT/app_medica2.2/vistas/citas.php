<?php
/** Vista: Agenda médica y control de turnos */
$lista = citas();
$estadoOk = flash('estado_ok');
$error = flash('error');

$botonesEstado = [
    ['Programada', 'Programada', 'data-[act=true]:bg-amber-500'],
    ['Confirmada', 'Confirmada', 'data-[act=true]:bg-teal-600'],
    ['Completada', 'Atendida', 'data-[act=true]:bg-emerald-600'],
];
$claseBoton = 'px-2 py-1 text-[11px] rounded-lg transition-colors text-slate-600 dark:text-slate-400 enabled:hover:text-slate-900 dark:enabled:hover:text-white disabled:cursor-not-allowed data-[act=true]:text-white data-[act=true]:font-semibold';
?>
<div class="space-y-6 pb-12" data-filtro="citas">
  <?= encabezado_vista('CLÍNICA / AGENDA', 'Agenda Médica y Control de Turnos',
      'Programación de consultas, estados de atención y cancelaciones (sp_cancelar_cita).',
      boton_primario(url('agendar'), 'plus', 'Agendar Nueva Cita')) ?>

  <?php if ($estadoOk): ?><?= alerta('exito', $estadoOk, '', 4000) ?><?php endif; ?>
  <?php if ($error): ?><?= alerta('error', $error) ?><?php endif; ?>

  <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <div class="relative flex-1 max-w-md">
      <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('search', 'w-4 h-4') ?></span>
      <input type="text" data-filtro-texto placeholder="Buscar por paciente, médico, especialidad o consultorio..." class="<?= CLASES_INPUT ?> pl-9" aria-label="Buscar cita">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <?php foreach (['Todas' => 'Todas las fechas', 'proximas' => 'De hoy en adelante', 'pasadas' => 'Pasadas'] as $v => $et): ?>
          <button type="button" data-filtro-campo="cuando" data-filtro-opcion="<?= $v ?>" class="<?= CLASES_SEGMENTO ?>"><?= $et ?></button>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-x-auto max-w-full">
        <?php foreach (array_merge(['Todas'], ESTADOS_CITA) as $st): ?>
          <button type="button" data-filtro-opcion="<?= $st ?>" class="<?= CLASES_SEGMENTO ?>"><?= $st ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <span><span data-filtro-contador><?= count($lista) ?></span> citas</span>
      <span class="font-mono">Hoy: <?= e(hoy()) ?></span>
    </div>

    <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
      <?php foreach ($lista as $c):
        $final = in_array($c['estatus_cita'], ['Cancelada', 'Completada'], true); ?>
        <div data-filtro-item data-grupo="<?= e($c['estatus_cita']) ?>" data-cuando="<?= $c['fecha'] >= hoy() ? 'proximas' : 'pasadas' ?>"
             data-texto="<?= e($c['paciente_nombre'] . ' ' . $c['medico'] . ' ' . $c['nombre_especialidad'] . ' ' . $c['nombre_consultorio'] . ' #' . $c['id_cita']) ?>"
             class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
          <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center shrink-0">
              <span class="text-xs font-mono font-bold text-slate-900 dark:text-white"><?= e($c['hora']) ?></span>
              <span class="text-[10px] font-mono mt-0.5 <?= $c['es_hoy'] ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-slate-400' ?>"><?= $c['es_hoy'] ? 'HOY' : e(substr($c['fecha'], 5)) ?></span>
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white"><?= e($c['paciente_nombre']) ?></h3>
                <span class="text-[10px] px-2.5 py-0.5 rounded-full font-medium border <?= clases_estado_cita($c['estatus_cita']) ?>"><?= e($c['estatus_cita']) ?></span>
                <span class="text-[10px] font-mono text-slate-400">#<?= (int)$c['id_cita'] ?> · <?= e($c['fecha']) ?></span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                <?= e($c['medico']) ?> · <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($c['nombre_especialidad']) ?></span> (<?= e($c['nombre_consultorio']) ?>)
              </p>
              <?php if ($c['motivo_consulta']): ?>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 italic">"<?= e($c['motivo_consulta']) ?>"</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
              <?php foreach ($botonesEstado as [$estado, $texto, $color]): ?>
                <?= boton_accion('estado_cita', ['id' => $c['id_cita'], 'estado' => $estado], e($texto), $claseBoton . ' ' . $color, '',
                    'data-act="' . ($c['estatus_cita'] === $estado ? 'true' : 'false') . '"' . ($final ? ' disabled' : '')) ?>
              <?php endforeach; ?>
              <?= boton_accion('estado_cita', ['id' => $c['id_cita'], 'estado' => 'Cancelada'], icono('x', 'w-3.5 h-3.5'),
                  'px-2 py-1 text-[11px] rounded-lg transition-colors text-slate-600 dark:text-slate-400 enabled:hover:text-rose-600 disabled:cursor-not-allowed data-[act=true]:bg-rose-600 data-[act=true]:text-white',
                  'Cancelar cita (libera el horario)',
                  'data-act="' . ($c['estatus_cita'] === 'Cancelada' ? 'true' : 'false') . '"' . ($final ? ' disabled' : '')
                  . ' data-confirmar="¿Cancelar la cita #' . (int)$c['id_cita'] . ' de ' . e($c['paciente_nombre']) . '? El horario quedará disponible."') ?>
            </div>
            <?php if ($c['pago_monto'] !== null): ?>
              <span class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 ml-2" title="Pago <?= e($c['estatus_pago']) ?>"><?= dinero($c['pago_monto']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <p data-filtro-vacio hidden class="p-6 text-center text-xs text-slate-400">No hay citas con ese filtro.</p>
    </div>
  </div>
</div>

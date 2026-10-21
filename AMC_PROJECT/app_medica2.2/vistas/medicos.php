<?php
/** Vista: Plantilla médica, especialidades y consultorios */
$lista = medicos();
$listaEspecialidades = especialidades();
$listaConsultorios = consultorios();
$hoyDia = dia_atencion(hoy());
?>
<div class="space-y-6 pb-12">
  <?= encabezado_vista('CLÍNICA / EQUIPO MÉDICO', 'Plantilla Médica y Consultorios Asignados',
      'Especialistas, días de atención por consultorio y horarios libres publicados.',
      boton_primario(url('agendar'), 'calendar-plus', 'Agendar Cita Médica')) ?>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($lista as $d): ?>
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between transition-colors">
        <div>
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
              <div class="bg-primary w-12 h-12 rounded-xl text-white flex items-center justify-center font-bold text-sm shadow-xs"><?= e($d['iniciales']) ?></div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight"><?= e($d['nombre_completo']) ?></h3>
                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium mt-0.5"><?= e(implode(' · ', array_column($d['especialidades'], 'nombre')) ?: 'Sin especialidad') ?></p>
                <span class="text-[10px] font-mono text-slate-400"><?= e($d['cedula_profesional']) ?></span>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium whitespace-nowrap <?= $d['atiende_hoy']
                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' ?>"><?= $d['atiende_hoy'] ? 'Atiende hoy' : 'No atiende hoy' ?></span>
          </div>

          <div class="space-y-2 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300">
            <?php foreach ($d['consultorios'] as $c): ?>
              <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2"><?= icono('building', 'w-3.5 h-3.5 text-slate-400') ?><span class="font-semibold text-slate-800 dark:text-slate-200"><?= e($c['nombre']) ?></span></span>
                <span class="font-mono text-[11px] <?= atiende_el_dia($c['dias'], hoy()) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-400' ?>"><?= e($c['dias']) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="flex items-center gap-2"><?= icono('clock', 'w-3.5 h-3.5 text-slate-400') ?><span><?= $d['horarios_libres'] ?> horario(s) libre(s) publicados</span></div>
            <div class="flex items-center gap-2"><?= icono('phone', 'w-3.5 h-3.5 text-slate-400') ?><span class="font-mono"><?= e($d['telefono']) ?></span></div>
            <div class="flex items-center gap-2"><?= icono('mail', 'w-3.5 h-3.5 text-slate-400') ?><span class="truncate"><?= e($d['correo']) ?></span></div>
            <div class="flex items-center gap-2"><?= icono('calendar', 'w-3.5 h-3.5 text-slate-400') ?><span>Ingreso: <?= e($d['fecha_ingreso']) ?></span></div>
          </div>
        </div>

        <a href="<?= e(url('agendar', ['medico' => $d['id_medico']])) ?>" class="mt-4 w-full py-2 px-3 text-xs font-semibold text-center rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors">
          Agendar con este Especialista
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div id="especialidades" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
      <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2"><?= icono('sparkles', 'w-4 h-4 text-primary') ?> Especialidades</h2>
      <div class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach ($listaEspecialidades as $esp):
          $medicosEsp = array_filter($lista, fn($m) => in_array((int)$esp['id_especialidad'], array_column($m['especialidades'], 'id'), true)); ?>
          <div class="py-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200"><?= e($esp['nombre_especialidad']) ?></span>
              <span class="text-[10px] font-mono text-slate-400"><?= count($medicosEsp) ?> médico(s)</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= e($esp['descripcion']) ?></p>
            <p class="text-[11px] text-teal-600 dark:text-teal-400 mt-0.5"><?= e(implode(', ', array_column($medicosEsp, 'nombre_completo'))) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="consultorios" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
      <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2"><?= icono('building', 'w-4 h-4 text-primary') ?> Consultorios</h2>
      <div class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach ($listaConsultorios as $c): ?>
          <div class="py-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200"><?= e($c['nombre_consultorio']) ?></span>
              <span class="text-[10px] font-mono text-slate-400"><?= e($c['telefono']) ?></span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= e($c['direccion']) ?>, <?= e($c['ciudad']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php if ($vistaActiva === 'especialidades' || $vistaActiva === 'consultorios'): ?>
<script>document.getElementById('<?= $vistaActiva ?>').scrollIntoView({ block: 'start' });</script>
<?php endif; ?>

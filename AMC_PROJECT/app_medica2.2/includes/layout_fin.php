    </main>
  </div>
</div>

<!-- ===================== Búsqueda universal (Ctrl+K) ===================== -->
<div id="modal-busqueda" hidden class="fixed inset-0 z-50 flex items-start justify-center pt-20 p-4 bg-black/50 backdrop-blur-xs">
  <div class="animate-in w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col" role="dialog" aria-modal="true" aria-label="Búsqueda universal">
    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
      <?= icono('search', 'w-5 h-5 text-slate-400') ?>
      <input id="busqueda-input" type="text" autocomplete="off"
             placeholder="Buscar paciente, médico, especialidad o medicamento..."
             class="w-full bg-transparent text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden">
      <button type="button" data-accion="cerrar-busqueda" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer" aria-label="Cerrar">
        <?= icono('x', 'w-5 h-5') ?>
      </button>
    </div>
    <div id="busqueda-resultados" class="p-3 max-h-96 overflow-y-auto space-y-4">
      <div class="py-8 text-center text-xs text-slate-400">
        <p>Escribe al menos 2 letras para iniciar la búsqueda universal.</p>
        <div class="flex justify-center gap-2 mt-3">
          <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">Pacientes</span>
          <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">Especialistas</span>
          <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">Farmacia</span>
        </div>
      </div>
    </div>
    <div class="p-3 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
      <span>Presiona ESC para salir</span>
      <kbd class="font-mono text-[10px] bg-slate-200 dark:bg-slate-800 px-1.5 py-0.5 rounded">↵ Enter para seleccionar</kbd>
    </div>
  </div>
</div>

<!-- ===================== Personalización visual ===================== -->
<div id="modal-tema" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
  <div class="animate-in w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh]" role="dialog" aria-modal="true" aria-labelledby="titulo-tema">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400"><?= icono('palette', 'w-5 h-5') ?></div>
        <div>
          <h2 id="titulo-tema" class="text-base font-semibold text-slate-900 dark:text-white">Personalización Visual y Tipografía</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Ajusta el modo de luz, paleta clínica y tipografía optimizada para lectura médica</p>
        </div>
      </div>
      <button type="button" data-accion="cerrar-tema" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" aria-label="Cerrar">
        <?= icono('x', 'w-5 h-5') ?>
      </button>
    </div>

    <div class="p-6 overflow-y-auto space-y-6">
      <!-- Modo -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1.5"><?= icono('sun', 'w-3.5 h-3.5') ?> Modo de Visualización</p>
        <div class="grid grid-cols-3 gap-3">
          <?php foreach ([['light', 'Modo Claro', 'sun', 'Óptimo para turnos diurnos'], ['dark', 'Modo Oscuro', 'moon', 'Reduce fatiga ocular en guardias'], ['system', 'Automático', 'monitor', 'Sigue la preferencia del SO']] as [$id, $et, $ic, $desc]): ?>
            <button type="button" data-tema-modo="<?= $id ?>"
                    class="flex flex-col items-center justify-center p-3.5 rounded-xl border text-center transition-all cursor-pointer border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 bg-slate-50/50 dark:bg-slate-800/40 data-[sel=true]:border-teal-500 data-[sel=true]:bg-teal-50/50 dark:data-[sel=true]:bg-teal-950/30 data-[sel=true]:text-teal-800 dark:data-[sel=true]:text-teal-300 data-[sel=true]:ring-2 data-[sel=true]:ring-teal-500/20">
              <?= icono($ic, 'w-5 h-5 mb-1.5') ?>
              <span class="text-xs font-semibold"><?= e($et) ?></span>
              <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 line-clamp-1"><?= e($desc) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Paletas -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1.5"><?= icono('palette', 'w-3.5 h-3.5') ?> Paleta Cromática Clínica</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
          <?php foreach (PALETAS as $clave => $p): ?>
            <button type="button" data-tema-color="<?= e($clave) ?>"
                    class="group flex items-center gap-2.5 p-2.5 rounded-xl border text-left transition-all cursor-pointer border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-800/40 data-[sel=true]:border-slate-900 dark:data-[sel=true]:border-white data-[sel=true]:bg-slate-100/80 dark:data-[sel=true]:bg-slate-800/80 data-[sel=true]:ring-2 data-[sel=true]:ring-slate-400/20">
              <span class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center shadow-xs" style="background-color: <?= e($p['hex']) ?>">
                <span class="hidden group-data-[sel=true]:inline text-white"><?= icono('check', 'w-3 h-3') ?></span>
              </span>
              <span class="text-xs font-medium text-slate-800 dark:text-slate-200 truncate"><?= e($p['nombre']) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tipografías -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5"><?= icono('type', 'w-3.5 h-3.5') ?> Tipografía de Interfaz Médica</p>
          <span class="text-[11px] text-teal-600 dark:text-teal-400 font-medium">Google Fonts en vivo</span>
        </div>
        <div class="space-y-2">
          <?php foreach (TIPOGRAFIAS as $clave => $f): ?>
            <button type="button" data-tema-fuente="<?= e($clave) ?>"
                    class="group w-full flex items-center justify-between p-3 rounded-xl border text-left transition-all cursor-pointer border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-800/40 text-slate-700 dark:text-slate-300 data-[sel=true]:border-teal-500 data-[sel=true]:bg-teal-50/50 dark:data-[sel=true]:bg-teal-950/20 data-[sel=true]:ring-1 data-[sel=true]:ring-teal-500/30">
              <span class="min-w-0 pr-3">
                <span class="flex items-center gap-2">
                  <span class="text-xs font-semibold text-slate-900 dark:text-white" style="font-family: <?= e($f['css']) ?>"><?= e($f['nombre']) ?></span>
                  <?php if ($f['etiqueta'] === 'Anti-fatiga'): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-medium">Anti-fatiga</span>
                  <?php elseif ($f['etiqueta'] === 'Recomendada'): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-sm bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-medium">Recomendada</span>
                  <?php endif; ?>
                </span>
                <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"><?= e($f['descripcion']) ?></span>
              </span>
              <span class="shrink-0 flex items-center gap-2">
                <span class="text-xs text-slate-400 dark:text-slate-500 hidden sm:inline" style="font-family: <?= e($f['css']) ?>">AaBb123</span>
                <span class="w-4 h-4 rounded-full border flex items-center justify-center border-slate-300 dark:border-slate-600 group-data-[sel=true]:border-teal-600 group-data-[sel=true]:bg-teal-600 text-white">
                  <span class="hidden group-data-[sel=true]:inline"><?= icono('check', 'w-2.5 h-2.5') ?></span>
                </span>
              </span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Densidad -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5"><?= icono('sliders-horizontal', 'w-3.5 h-3.5') ?> Densidad de Pantalla</p>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach ([['comfortable', 'Cómoda (Estándar)', 'Espaciado ergonómico para atención y recepción'], ['compact', 'Compacta (Alta Densidad)', 'Filas y formularios reducidos para mayor contenido']] as [$id, $et, $desc]): ?>
            <button type="button" data-tema-densidad="<?= $id ?>"
                    class="p-3 rounded-xl border text-left transition-all cursor-pointer border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 data-[sel=true]:border-teal-500 data-[sel=true]:bg-teal-50/40 dark:data-[sel=true]:bg-teal-950/20 data-[sel=true]:text-slate-900 dark:data-[sel=true]:text-white">
              <span class="block text-xs font-semibold"><?= e($et) ?></span>
              <span class="block text-[11px] text-slate-500 dark:text-slate-400"><?= e($desc) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Vista previa -->
      <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-2">
          <span class="font-semibold flex items-center gap-1"><?= icono('sparkles', 'w-3.5 h-3.5 text-teal-600 dark:text-teal-400') ?> Previsualización en tiempo real</span>
          <span>Guardado automático</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="bg-primary w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-xs">MC</div>
            <div>
              <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Ricardo Solis Mendoza</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400">Cardiologia · Consultorio Centro · 09:00 AM</p>
            </div>
          </div>
          <span class="bg-primary px-3 py-1.5 text-xs font-medium text-white rounded-lg shadow-xs">Atender Cita</span>
        </div>
      </div>
    </div>

    <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <p class="text-xs text-slate-500 dark:text-slate-400">Los ajustes se guardan automáticamente en tu navegador.</p>
      <button type="button" data-accion="cerrar-tema" class="px-4 py-2 text-xs font-semibold text-white bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors cursor-pointer">Aplicar y Volver</button>
    </div>
  </div>
</div>

<script>window.APP = { csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="assets/js/app.js"></script>
<?php if (!empty($scriptsVista)): foreach ($scriptsVista as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>

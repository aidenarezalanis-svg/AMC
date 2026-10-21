<?php
/** Vista: Agendar nueva cita y registrar paciente */
$listaMedicos = medicos();
$listaEspecialidades = especialidades();

$pacienteSel = paciente((int)($_GET['paciente'] ?? viejo('paciente_id', 0)));
if ($pacienteSel && !$pacienteSel['activo']) {
    $pacienteSel = null;
}
$medicoSel = medico((int)($_GET['medico'] ?? viejo('medico_id', 0)));
$especialidadSel = (int)viejo('especialidad_id', $medicoSel['especialidades'][0]['id'] ?? 0);

$fechaCita = viejo('fecha', hoy());
$horaCita = viejo('hora', '09:00');

$citaOk = flash('cita_ok');
$pacienteOk = flash('paciente_ok');
$error = flash('error');

$motivosRapidos = ['Chequeo de rutina', 'Dolor agudo / Malestar', 'Revisión de laboratorio',
                   'Control de hipertensión', 'Certificado médico', 'Seguimiento post-tratamiento'];

$datosJs = [
    'medicos' => array_map(fn($m) => [
        'id' => (int)$m['id_medico'],
        'nombre' => $m['nombre_completo'],
        'especialidades' => array_column($m['especialidades'], 'id'),
        'consultorios' => $m['consultorios'],
    ], $listaMedicos),
    'consultorioPrevio' => (int)viejo('consultorio_id', 0),
];
$scriptsVista[] = 'assets/js/agendar.js';
$etiqueta = 'block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1';
?>
<script>window.AGENDAR = <?= json_encode($datosJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>

<div class="space-y-6 pb-12">
  <div>
    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-teal-600 dark:text-teal-400">
      <span>CLÍNICA</span><span>/</span><span>GESTIÓN DE CITAS</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-1">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Agendar Nueva Cita y Gestión de Pacientes</h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Selecciona un paciente mediante el buscador inteligente o registra uno nuevo al instante.</p>
      </div>
      <a href="<?= e(url('dashboard')) ?>" class="self-start sm:self-auto px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Ver Dashboard General →</a>
    </div>
  </div>

  <?php if ($citaOk): ?><?= alerta('exito', $citaOk, 'El horario quedó marcado como Ocupado en horarios_disponibles.', 7000) ?><?php endif; ?>
  <?php if ($pacienteOk): ?><?= alerta('info', $pacienteOk, 'Puedes completar los datos de su cita en la columna izquierda.', 6000) ?><?php endif; ?>
  <?php if ($error): ?><?= alerta('error', $error) ?><?php endif; ?>
  <div id="error-cliente" hidden></div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    <!-- ==================== Datos de la cita ==================== -->
    <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800/80 shadow-xs transition-colors">
      <div class="p-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="bg-primary p-2 rounded-xl text-white shadow-xs"><?= icono('calendar-check-2', 'w-4 h-4') ?></div>
          <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Datos de la Cita</h2>
            <p class="text-[11px] text-slate-400">Asignación médica, consultorio y fecha programada</p>
          </div>
        </div>
        <span id="insignia-vinculado" <?= $pacienteSel ? '' : 'hidden' ?> class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-medium border border-emerald-200 dark:border-emerald-800">Paciente vinculado</span>
      </div>

      <form id="form-cita" method="post" action="acciones.php" class="p-5 space-y-4" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="guardar_cita">
        <input type="hidden" name="paciente_id" id="paciente-id" value="<?= $pacienteSel ? (int)$pacienteSel['id_paciente'] : '' ?>">

        <div class="relative">
          <label for="buscar-paciente" class="<?= $etiqueta ?>">Buscar y Seleccionar Paciente <span class="text-rose-500">*</span></label>
          <div class="relative">
            <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('search', 'w-4 h-4') ?></span>
            <input type="text" id="buscar-paciente" autocomplete="off"
                   value="<?= $pacienteSel ? e($pacienteSel['nombre_completo']) : '' ?>"
                   placeholder="Escribe el nombre, CURP, teléfono o correo del paciente..."
                   class="<?= CLASES_INPUT ?> pl-9">
          </div>
          <p class="text-[11px] text-slate-400 mt-1">Solo aparecen pacientes con estatus Activo.</p>

          <div id="resumen-paciente" <?= $pacienteSel ? '' : 'hidden' ?> class="mt-2 p-2.5 rounded-xl bg-teal-50/70 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div id="resumen-iniciales" class="bg-primary w-7 h-7 rounded-full text-white flex items-center justify-center text-xs font-bold"><?= $pacienteSel ? e($pacienteSel['iniciales']) : '' ?></div>
              <div>
                <p id="resumen-nombre" class="text-xs font-semibold text-slate-900 dark:text-white"><?= $pacienteSel ? e($pacienteSel['nombre_completo']) : '' ?></p>
                <p id="resumen-datos" class="text-[10px] text-slate-500 dark:text-slate-400 font-mono"><?= $pacienteSel ? 'CURP: ' . e($pacienteSel['curp']) . ' · Tel: ' . e($pacienteSel['telefono']) : '' ?></p>
              </div>
            </div>
            <button type="button" id="cambiar-paciente" class="text-[11px] text-teal-700 dark:text-teal-300 hover:underline cursor-pointer">Cambiar</button>
          </div>

          <div id="sugerencias-paciente" hidden class="absolute left-0 right-0 mt-1 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 py-1 z-30 max-h-56 overflow-y-auto"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="especialidad" class="<?= $etiqueta ?>">Especialidad <span class="text-rose-500">*</span></label>
            <select id="especialidad" name="especialidad_id" class="<?= CLASES_INPUT ?> cursor-pointer">
              <option value="">Seleccione una especialidad primero...</option>
              <?php foreach ($listaEspecialidades as $esp): ?>
                <option value="<?= (int)$esp['id_especialidad'] ?>" <?= (int)$esp['id_especialidad'] === $especialidadSel ? 'selected' : '' ?>><?= e($esp['nombre_especialidad']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="medico" class="<?= $etiqueta ?>">Médico Asignado <span class="text-rose-500">*</span></label>
            <select id="medico" name="medico_id" class="<?= CLASES_INPUT ?> cursor-pointer" data-seleccionado="<?= $medicoSel ? (int)$medicoSel['id_medico'] : '' ?>">
              <option value="">Seleccione una especialidad...</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="consultorio" class="<?= $etiqueta ?>">Consultorio Asignado <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2.5 text-slate-400 pointer-events-none"><?= icono('building', 'w-4 h-4') ?></span>
              <select id="consultorio" name="consultorio_id" class="<?= CLASES_INPUT ?> pl-9 cursor-pointer">
                <option value="">Seleccione un médico...</option>
              </select>
            </div>
            <p id="aviso-dia" class="text-[10px] text-amber-600 dark:text-amber-400 mt-1" hidden></p>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="fecha" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha de Cita <span class="text-rose-500">*</span></label>
              <div class="flex items-center gap-1">
                <button type="button" data-fecha-rapida="0" class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline cursor-pointer">Hoy</button>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <button type="button" data-fecha-rapida="1" class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline cursor-pointer">Mañana</button>
              </div>
            </div>
            <input type="date" id="fecha" name="fecha" value="<?= e($fechaCita) ?>" min="<?= e(hoy()) ?>" class="<?= CLASES_INPUT ?> cursor-pointer font-mono">
          </div>
        </div>

        <div id="caja-proximos" hidden>
          <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Horarios ya publicados y libres de este médico:</p>
          <div id="lista-proximos" class="flex flex-wrap gap-1.5"></div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Hora de Inicio (Turno de 30 min) <span class="text-rose-500">*</span></span>
            <button type="button" id="alternar-horario" class="text-[10px] text-teal-600 dark:text-teal-400 hover:underline cursor-pointer">Ver selector clásico</button>
          </div>
          <input type="hidden" name="hora" id="hora" value="<?= e($horaCita) ?>">
          <div id="cuadricula-horarios" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/80">
            <div class="grid grid-cols-4 sm:grid-cols-6 gap-1.5 max-h-36 overflow-y-auto pr-1">
              <?php foreach (HORARIOS_CITA as $h): ?>
                <button type="button" data-horario="<?= $h ?>"
                        class="py-1 px-2 rounded-lg text-xs font-mono font-medium transition-all cursor-pointer bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed disabled:line-through data-[sel=true]:bg-teal-600 data-[sel=true]:border-teal-600 data-[sel=true]:text-white data-[sel=true]:font-semibold data-[sel=true]:shadow-xs"><?= $h ?></button>
              <?php endforeach; ?>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Los turnos tachados ya están ocupados o ya pasaron.</p>
          </div>
          <select id="selector-horario" hidden class="<?= CLASES_INPUT ?> font-mono cursor-pointer" aria-label="Hora de inicio">
            <?php foreach (HORARIOS_CITA as $h): ?>
              <option value="<?= $h ?>"><?= $h ?> hrs (Turno de 30 min)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="motivo" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Motivo de la Consulta</label>
            <span class="text-[10px] text-slate-400">Opcional · máx. 255</span>
          </div>
          <textarea id="motivo" name="motivo" rows="3" maxlength="255"
                    placeholder="Describe brevemente el motivo de la visita o sintomatología principal..."
                    class="<?= CLASES_INPUT ?> resize-none"><?= e(viejo('motivo')) ?></textarea>
          <div class="flex flex-wrap gap-1.5 mt-2">
            <?php foreach ($motivosRapidos as $m): ?>
              <button type="button" data-motivo="<?= e($m) ?>" class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">+ <?= e($m) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800/80">
          <a href="<?= e(url('agendar')) ?>" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancelar</a>
          <button type="submit" class="bg-primary px-5 py-2 rounded-xl text-xs font-semibold text-white shadow-xs hover:opacity-90 transition-all cursor-pointer flex items-center gap-1.5">
            <?= icono('check', 'w-4 h-4') ?><span>Guardar Cita</span>
          </button>
        </div>
      </form>
    </div>

    <!-- ==================== Registrar paciente ==================== -->
    <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800/80 shadow-xs overflow-hidden transition-colors">
      <div class="p-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center gap-2.5">
        <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200"><?= icono('user-plus', 'w-4 h-4') ?></div>
        <div>
          <h2 class="text-sm font-bold text-slate-900 dark:text-white">Registrar Nuevo Paciente</h2>
          <p class="text-[11px] text-slate-400">Alta con sp_registrar_paciente (crea usuario y paciente)</p>
        </div>
      </div>

      <form id="form-paciente" method="post" action="acciones.php" class="p-5 space-y-3.5">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="registrar_paciente">

        <div class="p-3 rounded-xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-200/80 dark:border-teal-900/60 flex items-start gap-2.5">
          <span class="text-teal-600 dark:text-teal-400 mt-0.5"><?= icono('info', 'w-4 h-4') ?></span>
          <p class="text-xs text-teal-800 dark:text-teal-200 leading-snug">¿El paciente no aparece en el buscador de la izquierda? Regístralo aquí al instante para poder agendar su cita de inmediato.</p>
        </div>

        <div>
          <label for="np-nombre" class="<?= $etiqueta ?>">Nombre(s) <span class="text-rose-500">*</span></label>
          <input type="text" id="np-nombre" name="nombre" required maxlength="50" value="<?= e(viejo('nombre')) ?>" placeholder="Ej. Ana Sofía" class="<?= CLASES_INPUT ?>">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="np-paterno" class="<?= $etiqueta ?>">Apellido Paterno <span class="text-rose-500">*</span></label>
            <input type="text" id="np-paterno" name="ap_paterno" required maxlength="50" value="<?= e(viejo('ap_paterno')) ?>" placeholder="Ej. Morales" class="<?= CLASES_INPUT ?>">
          </div>
          <div>
            <label for="np-materno" class="<?= $etiqueta ?>">Apellido Materno</label>
            <input type="text" id="np-materno" name="ap_materno" maxlength="50" value="<?= e(viejo('ap_materno')) ?>" placeholder="Ej. Del Río" class="<?= CLASES_INPUT ?>">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="np-nacimiento" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha Nacimiento <span class="text-rose-500">*</span></label>
              <span id="np-edad" class="text-[10px] text-teal-600 dark:text-teal-400 font-mono font-medium"></span>
            </div>
            <input type="date" id="np-nacimiento" name="fecha_nacimiento" required max="<?= e(hoy()) ?>" value="<?= e(viejo('fecha_nacimiento', '1995-01-01')) ?>" class="<?= CLASES_INPUT ?> font-mono cursor-pointer">
          </div>
          <div>
            <label for="np-genero" class="<?= $etiqueta ?>">Género <span class="text-rose-500">*</span></label>
            <select id="np-genero" name="genero" class="<?= CLASES_INPUT ?> cursor-pointer">
              <?php foreach (GENEROS as $clave => $g): ?>
                <option value="<?= $clave ?>" <?= viejo('genero', 'M') === $clave ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="np-curp" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">CURP (18 caract.) <span class="text-rose-500">*</span></label>
            <span id="np-curp-contador" class="text-[10px] font-mono text-slate-400">0/18</span>
          </div>
          <input type="text" id="np-curp" name="curp" required minlength="18" maxlength="18" value="<?= e(viejo('curp')) ?>" placeholder="EJ. AAAA000000HXXXXX00" class="<?= CLASES_INPUT ?> font-mono uppercase">
        </div>

        <div>
          <label for="np-telefono" class="<?= $etiqueta ?>">Teléfono (10 dígitos) <span class="text-rose-500">*</span></label>
          <div class="relative">
            <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('phone', 'w-3.5 h-3.5') ?></span>
            <input type="tel" id="np-telefono" name="telefono" required maxlength="15" value="<?= e(viejo('telefono')) ?>" placeholder="Ej. 7771234567" class="<?= CLASES_INPUT ?> pl-9 font-mono">
          </div>
        </div>

        <div>
          <label for="np-correo" class="<?= $etiqueta ?>">Correo Electrónico <span class="text-rose-500">*</span></label>
          <div class="relative">
            <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('mail', 'w-3.5 h-3.5') ?></span>
            <input type="email" id="np-correo" name="correo" required maxlength="100" value="<?= e(viejo('correo')) ?>" placeholder="correo@ejemplo.com" class="<?= CLASES_INPUT ?> pl-9">
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-2">
            <?= icono('user-plus', 'w-4 h-4') ?><span>Registrar Paciente y Cargar a Cita</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

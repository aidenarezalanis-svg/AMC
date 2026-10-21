<?php
/** Vista: Directorio de pacientes con ficha */
$lista = pacientes();
$numCitas = citas_por_paciente();
$seleccionado = paciente((int)($_GET['id'] ?? 0)) ?? ($lista[0] ?? null);
$estadoOk = flash('estado_ok');
$error = flash('error');
$scriptsVista[] = 'assets/js/foto.js';

$fichas = [];
foreach ($lista as $p) {
    $id = (int)$p['id_paciente'];
    $fichas[$id] = [
        'id' => $id,
        'nombre_completo' => $p['nombre_completo'],
        'iniciales' => $p['iniciales'],
        'foto' => $p['foto'],
        'curp' => $p['curp'],
        'edad' => $p['edad'] !== null ? $p['edad'] . ' años' : '—',
        'telefono' => $p['telefono'],
        'fecha_nacimiento' => $p['fecha_nacimiento'],
        'correo' => $p['correo'],
        'genero' => $p['genero_texto'],
        'registro' => substr($p['fecha_registro'], 0, 10),
        'citas' => (string)($numCitas[$id] ?? 0),
        'estatus' => $p['estatus'],
        'activo' => $p['activo'],
        'agendar' => url('agendar', ['paciente' => $id]),
    ];
}
$campoFicha = fn(string $c) => $seleccionado ? e($fichas[$seleccionado['id_paciente']][$c] ?? '') : '';
?>
<script>window.FICHAS_PACIENTES = <?= json_encode($fichas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>

<div class="space-y-6 pb-12" data-filtro="pacientes">
  <?= encabezado_vista('CLÍNICA / EXPEDIENTES', 'Directorio de Pacientes Registrados',
      'Gestión de expedientes, datos de contacto y programación directa de consultas.',
      boton_primario(url('agendar'), 'plus', 'Registrar Paciente')) ?>

  <?php if ($estadoOk): ?><?= alerta('exito', $estadoOk, '', 4000) ?><?php endif; ?>
  <?php if ($error): ?><?= alerta('error', $error) ?><?php endif; ?>

  <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <div class="relative flex-1 max-w-md">
      <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('search', 'w-4 h-4') ?></span>
      <input type="text" data-filtro-texto placeholder="Buscar por nombre, CURP, teléfono o correo..." class="<?= CLASES_INPUT ?> pl-9" aria-label="Buscar paciente">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <?php foreach (['Activo' => 'Activos', 'Inactivo' => 'Dados de baja', 'Todos' => 'Todos'] as $v => $et): ?>
          <button type="button" data-filtro-campo="estatus" data-filtro-opcion="<?= $v ?>" class="<?= CLASES_SEGMENTO ?>"><?= $et ?></button>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <?php foreach (['Todos', 'Masculino', 'Femenino'] as $g): ?>
          <button type="button" data-filtro-opcion="<?= $g ?>" class="<?= CLASES_SEGMENTO ?>"><?= $g ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
      <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono">
        <span>Mostrando <span data-filtro-contador><?= count($lista) ?></span> pacientes</span>
        <span><?= count($lista) ?> en total</span>
      </div>
      <div class="divide-y divide-slate-100 dark:divide-slate-800/60 max-h-[580px] overflow-y-auto">
        <?php foreach ($lista as $p): $id = (int)$p['id_paciente']; $sel = $seleccionado && (int)$seleccionado['id_paciente'] === $id; ?>
          <div data-filtro-item data-paciente="<?= $id ?>" data-sel="<?= $sel ? 'true' : 'false' ?>"
               data-texto="<?= e($p['nombre_completo'] . ' ' . $p['curp'] . ' ' . $p['telefono'] . ' ' . $p['correo']) ?>"
               data-grupo="<?= e($p['genero_texto']) ?>" data-estatus="<?= e($p['estatus']) ?>"
               class="p-3.5 flex items-center justify-between gap-3 cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40 data-[sel=true]:bg-teal-50/50 dark:data-[sel=true]:bg-teal-950/20">
            <div class="flex items-center gap-3 min-w-0">
              <?php if ($p['foto']): ?>
                <img src="<?= e($p['foto']) ?>" alt="" class="w-9 h-9 rounded-xl object-cover shrink-0" loading="lazy">
              <?php else: ?>
                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center justify-center shrink-0"><?= e($p['iniciales']) ?></div>
              <?php endif; ?>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                  <?= e($p['nombre_completo']) ?>
                  <?php if (!$p['activo']): ?><span class="ml-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">Inactivo</span><?php endif; ?>
                </p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono truncate">CURP: <?= e($p['curp']) ?> · <?= e($p['telefono']) ?></p>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <?php if ($p['activo']): ?>
                <a href="<?= e(url('agendar', ['paciente' => $id])) ?>" class="px-2.5 py-1 text-[11px] font-medium text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950 border border-teal-200 dark:border-teal-800 rounded-lg hover:bg-teal-100 transition-colors flex items-center gap-1">
                  <?= icono('calendar-plus', 'w-3 h-3') ?><span>Cita</span>
                </a>
              <?php endif; ?>
              <?= icono('chevron-right', 'w-4 h-4 text-slate-300 dark:text-slate-600') ?>
            </div>
          </div>
        <?php endforeach; ?>
        <p data-filtro-vacio hidden class="p-6 text-center text-xs text-slate-400">No hay pacientes que coincidan con la búsqueda.</p>
      </div>
    </div>

    <?php if ($seleccionado): ?>
      <div id="ficha-paciente" class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4 lg:sticky lg:top-24">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-3">
            <div class="relative w-16 h-16 shrink-0">
              <img id="ficha-foto" data-foto-img src="<?= e($seleccionado['foto'] ?? '') ?>" alt="Foto de perfil" <?= $seleccionado['foto'] ? '' : 'hidden' ?> class="w-16 h-16 rounded-2xl object-cover shadow-xs">
              <div id="ficha-iniciales" data-foto-iniciales data-ficha="iniciales" <?= $seleccionado['foto'] ? 'hidden' : '' ?> class="bg-primary w-16 h-16 rounded-2xl text-white flex items-center justify-center text-lg font-bold shadow-xs"><?= $campoFicha('iniciales') ?></div>
            </div>
            <div>
              <h3 data-ficha="nombre_completo" class="text-sm font-bold text-slate-900 dark:text-white"><?= $campoFicha('nombre_completo') ?></h3>
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">ID: <span data-ficha="id"><?= $campoFicha('id') ?></span> · <span data-ficha="estatus"><?= $campoFicha('estatus') ?></span></span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs">
          <?php foreach ([['curp', 'CURP', 'font-mono font-medium truncate'], ['edad', 'Edad', 'font-bold'],
                          ['telefono', 'Teléfono', 'font-mono'], ['fecha_nacimiento', 'Fecha Nacimiento', 'font-mono'],
                          ['genero', 'Género', ''], ['citas', 'Citas registradas', 'font-bold'],
                          ['correo', 'Correo', 'truncate col-span-2'], ['registro', 'Fecha de registro', 'font-mono']] as [$campo, $et, $clase]): ?>
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 <?= $campo === 'correo' ? 'col-span-2' : '' ?>">
              <span class="text-[10px] text-slate-400 uppercase font-semibold"><?= $et ?></span>
              <p data-ficha="<?= $campo ?>" class="<?= $clase ?> text-slate-900 dark:text-white mt-0.5"><?= $campoFicha($campo) ?></p>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- AMC-17: subir o cambiar la foto de perfil -->
        <form method="post" action="acciones.php" enctype="multipart/form-data" id="form-foto" data-foto data-max="<?= FOTO_MAX_BYTES ?>" class="p-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="subir_foto">
          <input type="hidden" name="id" value="<?= (int)$seleccionado['id_paciente'] ?>">
          <input type="hidden" name="MAX_FILE_SIZE" value="<?= FOTO_MAX_BYTES ?>">
          <div class="flex items-center justify-between gap-2 mb-2">
            <label for="foto" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Foto de perfil</label>
            <span class="text-[10px] text-slate-400">JPG o PNG · máx. 2 MB</span>
          </div>
          <div class="flex items-center gap-2">
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required
                   class="flex-1 min-w-0 text-[11px] text-slate-600 dark:text-slate-300 file:mr-2 file:px-2.5 file:py-1.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 file:cursor-pointer">
            <button type="submit" id="boton-foto" data-foto-boton disabled class="bg-primary px-3 py-1.5 rounded-lg text-[11px] font-semibold text-white whitespace-nowrap hover:opacity-90 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"><?= $seleccionado['foto'] ? 'Cambiar foto' : 'Subir foto' ?></button>
          </div>
          <p id="foto-error" data-foto-error hidden role="alert" class="text-[11px] text-rose-600 dark:text-rose-400 mt-2"></p>
        </form>

        <a id="ficha-agendar" href="<?= e(url('agendar', ['paciente' => $seleccionado['id_paciente']])) ?>" <?= $seleccionado['activo'] ? '' : 'hidden' ?>
           class="bg-primary w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-white shadow-xs hover:opacity-90 transition-all flex items-center justify-center gap-2">
          <?= icono('calendar-plus', 'w-4 h-4') ?><span>Agendar Cita para este Paciente</span>
        </a>

        <form method="post" action="acciones.php" id="form-estatus">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="estatus_paciente">
          <input type="hidden" name="volver" value="<?= e(url('pacientes', ['id' => $seleccionado['id_paciente']])) ?>">
          <input type="hidden" name="id" value="<?= (int)$seleccionado['id_paciente'] ?>">
          <input type="hidden" name="estatus" value="<?= $seleccionado['activo'] ? 'Inactivo' : 'Activo' ?>">
          <button type="submit" data-confirmar="<?= $seleccionado['activo'] ? '¿Dar de baja a este paciente? Su historial se conserva y puedes reactivarlo después.' : '' ?>"
                  class="w-full py-2 px-4 rounded-xl text-xs font-semibold border transition-colors cursor-pointer <?= $seleccionado['activo']
                      ? 'border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40'
                      : 'border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' ?>">
            <?= $seleccionado['activo'] ? 'Dar de baja (borrado lógico)' : 'Reactivar paciente' ?>
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Al elegir un paciente se actualiza la ficha sin recargar la página
document.addEventListener('click', function (e) {
  var fila = e.target.closest('[data-paciente]');
  if (!fila || e.target.closest('a')) return;
  var f = window.FICHAS_PACIENTES[fila.dataset.paciente];
  if (!f) return;
  document.querySelectorAll('[data-paciente]').forEach(function (x) { x.dataset.sel = String(x === fila); });
  document.querySelectorAll('[data-ficha]').forEach(function (el) { el.textContent = f[el.dataset.ficha]; });
  var img = document.getElementById('ficha-foto');
  img.hidden = !f.foto; if (f.foto) img.src = f.foto;
  document.getElementById('ficha-iniciales').hidden = !!f.foto;
  var formFoto = document.getElementById('form-foto');
  formFoto.querySelector('[name=id]').value = f.id;
  formFoto.reset();
  document.getElementById('foto-error').hidden = true;
  document.getElementById('boton-foto').disabled = true;
  document.getElementById('boton-foto').textContent = f.foto ? 'Cambiar foto' : 'Subir foto';
  document.getElementById('ficha-agendar').href = f.agendar;
  document.getElementById('ficha-agendar').hidden = !f.activo;
  // El botón de baja/reactivación se recarga con la ficha correcta
  var form = document.getElementById('form-estatus');
  form.querySelector('[name=id]').value = f.id;
  form.querySelector('[name=volver]').value = 'index.php?vista=pacientes&id=' + f.id;
  form.querySelector('[name=estatus]').value = f.activo ? 'Inactivo' : 'Activo';
  var boton = form.querySelector('button');
  boton.textContent = f.activo ? 'Dar de baja (borrado lógico)' : 'Reactivar paciente';
  boton.dataset.confirmar = f.activo ? '¿Dar de baja a este paciente? Su historial se conserva y puedes reactivarlo después.' : '';
  boton.className = boton.className.replace(/border-(rose|emerald)-200 dark:border-(rose|emerald)-900 text-(rose|emerald)-700 dark:text-(rose|emerald)-300 hover:bg-(rose|emerald)-50 dark:hover:bg-(rose|emerald)-950\/40/,
    f.activo ? 'border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40'
             : 'border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40');
  history.replaceState(null, '', 'index.php?vista=pacientes&id=' + f.id);
});
</script>

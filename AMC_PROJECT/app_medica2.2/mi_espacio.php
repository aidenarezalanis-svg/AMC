<?php
/**
 * Página de inicio del paciente después de iniciar sesión.
 * Muestra sus datos y sus citas, y permite editar sus datos (AMC-4) y su foto (AMC-5).
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout_acceso.php';
requerir_paciente();

$u = usuario_actual();
$p = paciente($u['id_perfil']);
if (!$p || !$p['activo']) {      // la cuenta pudo darse de baja después de iniciar sesión
    cerrar_sesion();
    header('Location: login.php');
    exit;
}

$misCitas = array_map('preparar_cita', filas(SQL_CITAS . ' WHERE c.id_paciente = ? ORDER BY h.fecha DESC, h.hora_inicio DESC', [$p['id_paciente']]));
$proximas = array_values(array_filter($misCitas, fn($c) => $c['fecha'] >= hoy() && in_array($c['estatus_cita'], ['Programada', 'Confirmada'], true)));
$anteriores = array_values(array_filter($misCitas, fn($c) => !in_array($c, $proximas, true)));
usort($proximas, fn($a, $b) => [$a['fecha'], $a['hora']] <=> [$b['fecha'], $b['hora']]);

$fotoOk = flash('foto_ok');
$fotoError = flash('foto_error');

// AMC-4: mensajes y valores del formulario de datos
$datosOk = flash('datos_ok');
$datosError = flash('datos_error');
$errores = $_SESSION['errores_datos'] ?? [];
unset($_SESSION['errores_datos']);
$v = fn(string $campo) => e(viejo($campo, $p[$campo] ?? ''));   // lo capturado si hubo error; si no, lo guardado
limpiar_viejo();
$claseCampo = 'w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800/60 border rounded-xl text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all';
$campo = function (string $nombre, string $etiqueta, string $tipo, bool $obligatorio, string $extra = '') use ($errores, $v, $claseCampo) {
    $error = $errores[$nombre] ?? null;
    return '<div><label for="dp-' . $nombre . '" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">' . e($etiqueta)
         . ($obligatorio ? ' <span class="text-rose-500">*</span>' : '') . '</label>'
         . '<input type="' . $tipo . '" id="dp-' . $nombre . '" name="' . $nombre . '" value="' . $v($nombre) . '"' . ($obligatorio ? ' required' : '') . ' ' . $extra
         . ($error ? ' aria-invalid="true" aria-describedby="dp-' . $nombre . '-error"' : '')
         . ' class="' . $claseCampo . ' ' . ($error ? 'border-rose-400 dark:border-rose-600' : 'border-slate-200 dark:border-slate-700') . '">'
         . ($error ? '<p id="dp-' . $nombre . '-error" class="text-[11px] text-rose-600 dark:text-rose-400 mt-1">' . e($error) . '</p>' : '')
         . '</div>';
};
acceso_inicio('Mi espacio', 'Tu espacio de salud', 'max-w-3xl');
?>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
          <img data-foto-img src="<?= e($p['foto'] ?? '') ?>" alt="Tu foto de perfil" <?= $p['foto'] ? '' : 'hidden' ?> class="w-16 h-16 rounded-2xl object-cover shadow-xs">
          <div data-foto-iniciales <?= $p['foto'] ? 'hidden' : '' ?> class="bg-primary w-16 h-16 rounded-2xl text-white flex items-center justify-center text-lg font-bold"><?= e($p['iniciales']) ?></div>
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">¡Bienvenido(a), <?= e($p['nombre']) ?>!</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Has iniciado sesión correctamente.</p>
          </div>
        </div>
        <form method="post" action="salir.php">
          <?= csrf_campo() ?>
          <button type="submit" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer">
            <?= icono('log-out', 'w-4 h-4') ?><span>Cerrar sesión</span>
          </button>
        </form>
      </div>

      <?php if ($fotoOk): ?><div class="mt-5"><?= alerta('exito', $fotoOk, '', 5000) ?></div><?php endif; ?>
      <?php if ($fotoError): ?><div class="mt-5"><?= alerta('error', $fotoError) ?></div><?php endif; ?>

      <!-- AMC-17: el paciente sube o cambia su foto -->
      <form method="post" action="mi_foto.php" enctype="multipart/form-data" data-foto data-max="<?= FOTO_MAX_BYTES ?>"
            class="mt-5 p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
        <?= csrf_campo() ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= FOTO_MAX_BYTES ?>">
        <div class="flex items-center justify-between gap-2 mb-2">
          <label for="foto" class="text-xs font-semibold text-slate-700 dark:text-slate-300"><?= $p['foto'] ? 'Cambiar mi foto de perfil' : 'Agregar mi foto de perfil' ?></label>
          <span class="text-[10px] text-slate-400">JPG o PNG · máx. 2 MB</span>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-3">Ayuda al personal de la clínica a identificarte en recepción.</p>
        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required
                 class="flex-1 min-w-0 text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 file:cursor-pointer">
          <button type="submit" data-foto-boton disabled class="bg-primary px-4 py-2 rounded-xl text-xs font-semibold text-white whitespace-nowrap hover:opacity-90 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"><?= $p['foto'] ? 'Cambiar foto' : 'Subir foto' ?></button>
        </div>
        <p data-foto-error hidden role="alert" class="text-xs text-rose-600 dark:text-rose-400 mt-2"></p>
      </form>

      <!-- AMC-4: ver y editar mis datos -->
      <section id="mis-datos" class="scroll-mt-6">
        <div class="flex items-center justify-between gap-3 mt-6 mb-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mis datos</h3>
          <button type="button" id="boton-editar" <?= $errores ? 'hidden' : '' ?>
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer">
            <?= icono('pencil', 'w-3.5 h-3.5') ?><span>Editar mis datos</span>
          </button>
        </div>

        <?php if ($datosOk): ?><div class="mb-3"><?= alerta('exito', $datosOk, '', 6000) ?></div><?php endif; ?>
        <?php if ($datosError): ?><div class="mb-3"><?= alerta('error', $datosError) ?></div><?php endif; ?>

        <div id="datos-vista" <?= $errores ? 'hidden' : '' ?> class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
          <?php foreach ([['Nombre completo', $p['nombre_completo'], ''], ['Fecha de nacimiento', $p['fecha_nacimiento'], 'font-mono'], ['Edad', $p['edad'] !== null ? $p['edad'] . ' años' : '—', ''],
                          ['Teléfono', $p['telefono'], 'font-mono'], ['Correo', $p['correo'], 'break-all'], ['CURP', $p['curp'], 'font-mono']] as [$et, $valor, $cls]): ?>
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
              <span class="text-[10px] text-slate-400 uppercase font-semibold"><?= e($et) ?></span>
              <p class="<?= $cls ?> text-slate-900 dark:text-white mt-0.5"><?= e($valor) ?></p>
            </div>
          <?php endforeach; ?>
        </div>

        <form id="datos-form" method="post" action="mi_datos.php" novalidate <?= $errores ? '' : 'hidden' ?>
              class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 space-y-4">
          <?= csrf_campo() ?>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?= $campo('nombre', 'Nombre(s)', 'text', true, 'maxlength="50" autocomplete="given-name"') ?>
            <?= $campo('ap_paterno', 'Apellido paterno', 'text', true, 'maxlength="50" autocomplete="family-name"') ?>
            <?= $campo('ap_materno', 'Apellido materno', 'text', false, 'maxlength="50"') ?>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?= $campo('fecha_nacimiento', 'Fecha de nacimiento', 'date', true, 'max="' . e(hoy()) . '"') ?>
            <?= $campo('telefono', 'Teléfono (10 dígitos)', 'tel', true, 'maxlength="15" inputmode="numeric" autocomplete="tel"') ?>
            <?= $campo('correo', 'Correo electrónico', 'email', true, 'maxlength="100" autocomplete="email"') ?>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400">Los campos con <span class="text-rose-500">*</span> son obligatorios. Si cambias tu correo, úsalo la próxima vez que inicies sesión. La CURP solo la corrige recepción.</p>
          <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <a href="mi_espacio.php#mis-datos" id="boton-cancelar" class="px-4 py-2 rounded-xl text-xs font-semibold text-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-primary px-5 py-2 rounded-xl text-xs font-semibold text-white hover:opacity-90 cursor-pointer">Guardar cambios</button>
          </div>
        </form>
      </section>

      <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-6 mb-3">Próximas citas</h3>
      <?php if (!$proximas): ?>
        <p class="text-xs text-slate-500 dark:text-slate-400 p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 text-center">No tienes citas próximas. Para agendar, comunícate con recepción.</p>
      <?php endif; ?>
      <div class="space-y-2">
        <?php foreach ($proximas as $c): ?>
          <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 flex flex-col items-center justify-center shrink-0">
                <span class="text-xs font-mono font-bold text-slate-900 dark:text-white"><?= e($c['hora']) ?></span>
                <span class="text-[10px] font-mono text-slate-400"><?= e(substr($c['fecha'], 5)) ?></span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white"><?= e($c['nombre_especialidad']) ?> con <?= e($c['medico']) ?></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= e($c['nombre_consultorio']) ?> · <?= e($c['fecha']) ?></p>
              </div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border <?= clases_estado_cita($c['estatus_cita']) ?>"><?= e($c['estatus_cita']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($anteriores): ?>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-6 mb-3">Historial de citas</h3>
        <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
          <?php foreach ($anteriores as $c): ?>
            <div class="py-2 flex items-center justify-between gap-3">
              <span class="text-slate-700 dark:text-slate-300"><span class="font-mono text-slate-400"><?= e($c['fecha']) ?></span> · <?= e($c['nombre_especialidad']) ?> con <?= e($c['medico']) ?></span>
              <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border whitespace-nowrap <?= clases_estado_cita($c['estatus_cita']) ?>"><?= e($c['estatus_cita']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
<script src="assets/js/foto.js"></script>
<script>
// AMC-4: mostrar u ocultar el formulario de edición
(function () {
  var form = document.getElementById('datos-form');
  var vista = document.getElementById('datos-vista');
  var boton = document.getElementById('boton-editar');
  boton.addEventListener('click', function () {
    form.hidden = false; vista.hidden = true; boton.hidden = true;
    document.getElementById('dp-nombre').focus();
  });
  document.getElementById('boton-cancelar').addEventListener('click', function (e) {
    e.preventDefault();
    form.reset();
    form.querySelectorAll('[aria-invalid]').forEach(function (c) { c.removeAttribute('aria-invalid'); });
    form.hidden = true; vista.hidden = false; boton.hidden = false;
  });
  // Aviso inmediato si falta un obligatorio (el servidor vuelve a validar todo)
  form.addEventListener('submit', function (e) {
    var vacio = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (c) { return !c.value.trim(); })[0];
    if (vacio) {
      e.preventDefault();
      vacio.setCustomValidity('Este campo es obligatorio.');
      vacio.reportValidity();
      vacio.addEventListener('input', function () { vacio.setCustomValidity(''); }, { once: true });
    }
  });
})();
</script>
<?php acceso_fin();

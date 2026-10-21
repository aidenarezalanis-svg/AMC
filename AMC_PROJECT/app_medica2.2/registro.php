<?php
/**
 * Registro público de pacientes. Crea la cuenta con sp_registrar_paciente
 * (usuarios + pacientes) guardando la contraseña con password_hash.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout_acceso.php';

if ($u = usuario_actual()) {
    header('Location: ' . inicio_por_rol($u['rol']));
    exit;
}

$error = null;
$v = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = $_POST;
    unset($v['contrasena'], $v['confirmar']);
    $contrasena = (string)($_POST['contrasena'] ?? '');

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'Tu sesión expiró. Vuelve a intentarlo.';
    } elseif ($contrasena !== (string)($_POST['confirmar'] ?? '')) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($e = error_contrasena($contrasena)) {
        $error = $e;
    } else {
        [$id, $error] = registrar_paciente($_POST, password_hash($contrasena, PASSWORD_DEFAULT));
        if ($id) {
            flash('registro_ok', 'Registro exitoso. Ya puedes iniciar sesión con tu correo o tu CURP.');
            header('Location: login.php');
            exit;
        }
    }
}

$val = fn(string $k, string $d = '') => e($v[$k] ?? $d);
$etq = 'block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1';
acceso_inicio('Crear cuenta', 'Crea tu cuenta de paciente', 'max-w-xl');
?>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Crear cuenta</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-5">Todos los campos con <span class="text-rose-500">*</span> son obligatorios.</p>

      <?php if ($error): ?><div class="mb-4"><?= alerta('error', $error) ?></div><?php endif; ?>

      <form method="post" action="registro.php" class="space-y-4">
        <?= csrf_campo() ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label for="nombre" class="<?= $etq ?>">Nombre(s) <span class="text-rose-500">*</span></label>
            <input type="text" id="nombre" name="nombre" required maxlength="50" autocomplete="given-name" value="<?= $val('nombre') ?>" class="<?= CLASES_INPUT ?>">
          </div>
          <div>
            <label for="ap_paterno" class="<?= $etq ?>">Apellido paterno <span class="text-rose-500">*</span></label>
            <input type="text" id="ap_paterno" name="ap_paterno" required maxlength="50" autocomplete="family-name" value="<?= $val('ap_paterno') ?>" class="<?= CLASES_INPUT ?>">
          </div>
          <div>
            <label for="ap_materno" class="<?= $etq ?>">Apellido materno</label>
            <input type="text" id="ap_materno" name="ap_materno" maxlength="50" value="<?= $val('ap_materno') ?>" class="<?= CLASES_INPUT ?>">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="fecha_nacimiento" class="<?= $etq ?>">Fecha de nacimiento <span class="text-rose-500">*</span></label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required max="<?= e(hoy()) ?>" value="<?= $val('fecha_nacimiento') ?>" class="<?= CLASES_INPUT ?> font-mono">
          </div>
          <div>
            <label for="genero" class="<?= $etq ?>">Género <span class="text-rose-500">*</span></label>
            <select id="genero" name="genero" class="<?= CLASES_INPUT ?> cursor-pointer">
              <?php foreach (GENEROS as $k => $g): ?>
                <option value="<?= $k ?>" <?= ($v['genero'] ?? 'M') === $k ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label for="curp" class="<?= $etq ?>">CURP <span class="text-rose-500">*</span></label>
          <input type="text" id="curp" name="curp" required minlength="18" maxlength="18" value="<?= $val('curp') ?>"
                 placeholder="18 caracteres" class="<?= CLASES_INPUT ?> font-mono uppercase"
                 oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="correo" class="<?= $etq ?>">Correo electrónico <span class="text-rose-500">*</span></label>
            <input type="email" id="correo" name="correo" required maxlength="100" autocomplete="email" value="<?= $val('correo') ?>" class="<?= CLASES_INPUT ?>">
          </div>
          <div>
            <label for="telefono" class="<?= $etq ?>">Teléfono (10 dígitos) <span class="text-rose-500">*</span></label>
            <input type="tel" id="telefono" name="telefono" required maxlength="15" autocomplete="tel" value="<?= $val('telefono') ?>" class="<?= CLASES_INPUT ?> font-mono">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?= campo_contrasena('contrasena', 'contrasena', 'Contraseña', 'new-password', true) ?>
          <?= campo_contrasena('confirmar', 'confirmar', 'Confirmar contraseña', 'new-password') ?>
        </div>

        <button type="submit" class="bg-primary w-full py-2.5 rounded-xl text-sm font-semibold text-white shadow-xs hover:opacity-90 transition-all cursor-pointer">Crear cuenta</button>
      </form>

      <div class="border-t border-slate-100 dark:border-slate-800 mt-6 pt-5 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400">¿Ya tienes una cuenta?</p>
        <a href="login.php" class="inline-block mt-2 text-sm font-semibold text-teal-700 dark:text-teal-300 hover:underline">Iniciar sesión</a>
      </div>
<?php acceso_fin();

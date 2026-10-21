<?php
/**
 * Inicio de sesión para pacientes (correo o CURP) y personal (correo).
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout_acceso.php';

if ($u = usuario_actual()) {
    header('Location: ' . inicio_por_rol($u['rol']));
    exit;
}

$error = null;
$identificador = '';
$volver = (string)($_POST['volver'] ?? $_GET['volver'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = mb_substr(trim((string)($_POST['identificador'] ?? '')), 0, 100);
    $contrasena = (string)($_POST['contrasena'] ?? '');
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'Tu sesión expiró. Vuelve a intentarlo.';
    } elseif ($identificador === '' || $contrasena === '') {
        $error = 'Completa tu correo (o CURP) y tu contraseña.';
    } else {
        $error = iniciar_sesion($identificador, $contrasena);
        if ($error === null) {
            $u = usuario_actual();
            $destino = $u['rol'] === 'Paciente' ? inicio_por_rol('Paciente') : destino_seguro($volver, inicio_por_rol($u['rol']));
            header('Location: ' . $destino);
            exit;
        }
    }
}

$registro = flash('registro_ok');
$salida = isset($_GET['salida']) ? 'Cerraste sesión correctamente.' : null;
acceso_inicio('Iniciar sesión', 'Tu bienestar comienza aquí');
?>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Iniciar sesión</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-5">Pacientes: usa tu correo o tu CURP. Personal de la clínica: usa tu correo institucional.</p>

      <?php if ($registro): ?><div class="mb-4"><?= alerta('exito', $registro) ?></div><?php endif; ?>
      <?php if ($salida): ?><div class="mb-4"><?= alerta('info', $salida, '', 4000) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="mb-4"><?= alerta('error', $error) ?></div><?php endif; ?>

      <form method="post" action="login.php" class="space-y-4" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <div>
          <label for="identificador" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Correo o CURP <span class="text-rose-500">*</span></label>
          <input type="text" id="identificador" name="identificador" required autofocus autocomplete="username" maxlength="100"
                 value="<?= e($identificador) ?>" placeholder="correo@ejemplo.com o CURP" class="<?= CLASES_INPUT ?>">
        </div>
        <?= campo_contrasena('contrasena', 'contrasena', 'Contraseña', 'current-password') ?>
        <button type="submit" class="bg-primary w-full py-2.5 rounded-xl text-sm font-semibold text-white shadow-xs hover:opacity-90 transition-all cursor-pointer">Iniciar sesión</button>
      </form>

      <div class="border-t border-slate-100 dark:border-slate-800 mt-6 pt-5 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400">¿Eres paciente y no tienes cuenta?</p>
        <a href="registro.php" class="inline-block mt-2 text-sm font-semibold text-teal-700 dark:text-teal-300 hover:underline">Crear cuenta</a>
      </div>
<?php acceso_fin();

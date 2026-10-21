<?php
/**
 * Layout de las páginas sin barra lateral (login, registro).
 * Uso: acceso_inicio('Título', 'subtítulo'); …contenido…; acceso_fin();
 */

function acceso_inicio(string $titulo, string $subtitulo, string $ancho = 'max-w-md'): void
{ ?><!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> — <?= e(APP_NOMBRE) ?></title>
  <script>window.APP_TEMA = <?= tema_json() ?>;</script>
  <script src="assets/js/tema.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400..700&family=Lexend:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300..800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">@custom-variant dark (&:where(.dark, .dark *));</style>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased flex items-center justify-center px-4 py-10">
  <button type="button" data-accion="alternar-oscuro" class="fixed top-4 right-4 p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-amber-300 cursor-pointer" aria-label="Cambiar modo claro u oscuro">
    <span class="dark:hidden"><?= icono('moon', 'w-4 h-4') ?></span>
    <span class="hidden dark:inline"><?= icono('sun', 'w-4 h-4') ?></span>
  </button>
  <main class="w-full <?= $ancho ?>">
    <div class="text-center mb-6">
      <div class="bg-primary w-12 h-12 mx-auto rounded-2xl flex items-center justify-center text-white shadow-sm">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/></svg>
      </div>
      <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white mt-3"><?= e(APP_NOMBRE) ?></h1>
      <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($subtitulo) ?></p>
    </div>
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 sm:p-8">
<?php }

function acceso_fin(): void
{ ?>
    </div>
  </main>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
    if (window.lucide) lucide.createIcons();
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-accion="alternar-oscuro"]')) window.AppTema.alternarOscuro();
      var ojo = e.target.closest('[data-ver-contrasena]');
      if (ojo) {
        var campo = document.getElementById(ojo.dataset.verContrasena);
        campo.type = campo.type === 'password' ? 'text' : 'password';
        ojo.setAttribute('aria-label', campo.type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
        ojo.textContent = campo.type === 'password' ? 'Ver' : 'Ocultar';
      }
    });
  </script>
</body>
</html>
<?php }

/** Campo de contraseña con botón para mostrarla. */
function campo_contrasena(string $id, string $nombre, string $etiqueta, string $autocompletar, bool $ayuda = false): string
{
    return '<div><label for="' . e($id) . '" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">' . e($etiqueta) . ' <span class="text-rose-500">*</span></label>'
         . '<div class="relative"><input type="password" id="' . e($id) . '" name="' . e($nombre) . '" required autocomplete="' . e($autocompletar) . '"'
         . ($ayuda ? ' minlength="8" aria-describedby="' . e($id) . '-ayuda"' : '')
         . ' class="' . CLASES_INPUT . ' pr-16">'
         . '<button type="button" data-ver-contrasena="' . e($id) . '" aria-label="Mostrar contraseña" class="absolute right-2 top-1.5 px-2 py-0.5 text-[11px] font-medium text-teal-700 dark:text-teal-300 hover:underline cursor-pointer">Ver</button></div>'
         . ($ayuda ? '<p id="' . e($id) . '-ayuda" class="text-[11px] text-slate-400 mt-1">Mínimo 8 caracteres, con letras y números.</p>' : '')
         . '</div>';
}

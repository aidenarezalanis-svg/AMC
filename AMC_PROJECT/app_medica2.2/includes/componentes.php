<?php
/**
 * Pequeños componentes HTML reutilizados por varias vistas.
 */

/** Botón que envía un POST a acciones.php y regresa a la página actual. */
function boton_accion(string $accion, array $campos, string $contenido, string $clases, string $titulo = '', string $atributos = ''): string
{
    $html = '<form method="post" action="acciones.php" class="inline">' . csrf_campo()
          . '<input type="hidden" name="accion" value="' . e($accion) . '">'
          . '<input type="hidden" name="volver" value="' . e($_SERVER['REQUEST_URI'] ?? 'index.php') . '">';
    foreach ($campos as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="' . e($clases) . ' cursor-pointer"'
         . ($titulo ? ' title="' . e($titulo) . '"' : '') . ($atributos ? ' ' . $atributos : '') . '>' . $contenido . '</button></form>';
}

/** Alerta de éxito / información / error. */
function alerta(string $tipo, string $mensaje, string $detalle = '', int $autocerrar = 0): string
{
    $estilos = [
        'exito' => ['bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200', 'circle-check', 'text-emerald-600 dark:text-emerald-400', 'text-emerald-700/80 dark:text-emerald-300/80'],
        'info'  => ['bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200', 'circle-check', 'text-blue-600 dark:text-blue-400', 'text-blue-700/80 dark:text-blue-300/80'],
        'error' => ['bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200', 'circle-alert', 'text-rose-600 dark:text-rose-400', ''],
    ];
    [$caja, $ico, $colorIco, $colorDetalle] = $estilos[$tipo] ?? $estilos['info'];
    return '<div role="' . ($tipo === 'error' ? 'alert' : 'status') . '" ' . ($autocerrar ? 'data-autocerrar="' . $autocerrar . '"' : '')
         . ' class="animate-in p-4 rounded-xl border flex items-start gap-3 shadow-xs ' . $caja . '">'
         . icono($ico, 'w-5 h-5 mt-0.5 ' . $colorIco)
         . '<div class="text-xs"><p class="font-semibold">' . e($mensaje) . '</p>'
         . ($detalle ? '<p class="mt-0.5 ' . $colorDetalle . '">' . e($detalle) . '</p>' : '')
         . '</div></div>';
}

/** Encabezado estándar de cada vista. */
function encabezado_vista(string $miga, string $titulo, string $descripcion, string $accionHtml = ''): string
{
    return '<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"><div>'
         . '<div class="text-[11px] font-semibold tracking-wider uppercase text-teal-600 dark:text-teal-400">' . e($miga) . '</div>'
         . '<h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5">' . e($titulo) . '</h1>'
         . '<p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">' . e($descripcion) . '</p>'
         . '</div>' . $accionHtml . '</div>';
}

/** Botón principal (color de la paleta) que enlaza a otra vista. */
function boton_primario(string $href, string $icono, string $texto): string
{
    return '<a href="' . e($href) . '" class="bg-primary self-start sm:self-auto flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white rounded-xl shadow-xs hover:opacity-90 transition-all shrink-0">'
         . icono($icono, 'w-4 h-4') . '<span>' . e($texto) . '</span></a>';
}

/** Clases para los botones de un control segmentado de filtros. */
const CLASES_SEGMENTO = 'px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors cursor-pointer text-slate-500 hover:text-slate-900 dark:hover:text-slate-200 data-[sel=true]:bg-white dark:data-[sel=true]:bg-slate-900 data-[sel=true]:text-slate-900 dark:data-[sel=true]:text-white data-[sel=true]:shadow-2xs data-[sel=true]:font-semibold';

const CLASES_INPUT = 'w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all';

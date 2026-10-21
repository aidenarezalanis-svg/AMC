<?php
/** Vista: Inventario de medicamentos (con reorden por compra a proveedor) */
$lista = medicamentos();
$listaProveedores = proveedores();
$totalUnidades = array_sum(array_column($lista, 'stock_actual'));
$valorInventario = array_sum(array_map(fn($m) => $m['stock_actual'] * $m['precio_unitario'], $lista));
$reordenOk = flash('reorden_ok');
$error = flash('error');

$ultimasCompras = filas('SELECT c.id_compra, c.fecha_compra, c.total, pr.nombre AS proveedor,
                                dc.cantidad, m.nombre_comercial
                         FROM compra c
                         LEFT JOIN proveedores pr ON pr.id_proveedor = c.id_proveedor
                         LEFT JOIN detalle_compra dc ON dc.id_compra = c.id_compra
                         LEFT JOIN medicamentos m ON m.id_medicamento = dc.id_medicamento
                         ORDER BY c.fecha_registro DESC, c.id_compra DESC LIMIT 6');

$insignias = [
    'Agotado' => ['Agotado', 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border-rose-200 dark:border-rose-800'],
    'Bajo'    => ['Stock Bajo', 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800'],
    'Normal'  => ['Disponible', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'],
];
?>
<div class="space-y-6 pb-12" data-filtro="inventario">
  <?= encabezado_vista('FARMACIA & OPERACIONES / INVENTARIO', 'Inventario Clínico y Medicamentos',
      'Existencias, precios, control de receta y reabastecimiento por compra a proveedor.',
      '<div class="flex flex-wrap gap-2 self-start sm:self-auto"><div class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono"><span class="text-slate-400">Stock Total:</span> <strong class="text-slate-900 dark:text-white">'
      . number_format($totalUnidades) . ' u.</strong></div><div class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono"><span class="text-slate-400">Valor:</span> <strong class="text-slate-900 dark:text-white">'
      . dinero($valorInventario, 2) . '</strong></div></div>') ?>

  <?php if ($reordenOk): ?><?= alerta('exito', $reordenOk, 'Se registró en las tablas compra y detalle_compra.', 6000) ?><?php endif; ?>
  <?php if ($error): ?><?= alerta('error', $error) ?><?php endif; ?>

  <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <div class="relative flex-1 max-w-md">
      <span class="absolute left-3 top-2.5 text-slate-400"><?= icono('search', 'w-4 h-4') ?></span>
      <input type="text" data-filtro-texto placeholder="Buscar por nombre comercial o principio activo..." class="<?= CLASES_INPUT ?> pl-9" aria-label="Buscar medicamento">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        Proveedor para reórdenes
        <select id="proveedor-reorden" class="px-2 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white cursor-pointer">
          <option value="">Sin proveedor</option>
          <?php foreach ($listaProveedores as $p): ?>
            <option value="<?= (int)$p['id_proveedor'] ?>"><?= e($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
        <?php foreach (['Todos', 'Agotado', 'Bajo', 'Normal'] as $st): ?>
          <button type="button" data-filtro-opcion="<?= $st ?>" class="<?= CLASES_SEGMENTO ?>"><?= $st ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200 dark:border-slate-800">
          <tr>
            <th class="px-5 py-3.5 font-semibold">Medicamento / Presentación</th>
            <th class="px-4 py-3.5 font-semibold">Receta</th>
            <th class="px-4 py-3.5 font-semibold text-right">Existencias</th>
            <th class="px-4 py-3.5 font-semibold">Estado</th>
            <th class="px-4 py-3.5 font-semibold text-right">Precio</th>
            <th class="px-5 py-3.5 font-semibold text-right">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
          <?php foreach ($lista as $m): [$etq, $cls] = $insignias[$m['estado']]; ?>
            <tr data-filtro-item data-grupo="<?= e($m['estado']) ?>" data-texto="<?= e($m['nombre_comercial'] . ' ' . $m['principio_activo'] . ' ' . $m['presentacion']) ?>" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                <p class="font-bold"><?= e($m['nombre_comercial']) ?></p>
                <p class="text-[11px] text-slate-400"><?= e($m['principio_activo']) ?> · <?= e($m['presentacion']) ?></p>
              </td>
              <td class="px-4 py-3.5">
                <?php if ((int)$m['requiere_receta']): ?>
                  <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-300 border border-violet-200 dark:border-violet-800 whitespace-nowrap">Con receta</span>
                <?php else: ?>
                  <span class="text-[10px] text-slate-400">Libre</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 font-mono text-right font-bold tabular-nums text-slate-900 dark:text-white">
                <?= $m['stock_actual'] ?> u.
                <span class="block text-[10px] font-normal text-slate-400">Mín: <?= STOCK_MINIMO ?></span>
              </td>
              <td class="px-4 py-3.5">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border whitespace-nowrap <?= $cls ?>"><?= $etq ?></span>
              </td>
              <td class="px-4 py-3.5 font-mono text-right tabular-nums text-slate-700 dark:text-slate-300 font-semibold"><?= dinero($m['precio_unitario'], 2) ?></td>
              <td class="px-5 py-3.5 text-right">
                <form method="post" action="acciones.php" class="inline" data-reorden>
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="reordenar">
                  <input type="hidden" name="volver" value="<?= e(url($vistaActiva)) ?>">
                  <input type="hidden" name="id" value="<?= (int)$m['id_medicamento'] ?>">
                  <input type="hidden" name="cantidad" value="<?= CANTIDAD_REORDEN ?>">
                  <input type="hidden" name="proveedor_id" value="">
                  <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-2xs transition-colors whitespace-nowrap cursor-pointer">Reordenar +<?= CANTIDAD_REORDEN ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p data-filtro-vacio hidden class="p-6 text-center text-xs text-slate-400">No hay medicamentos con ese filtro.</p>
    </div>
  </div>

  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Últimas compras registradas</h2>
    <?php if (!$ultimasCompras): ?><p class="text-xs text-slate-400">Aún no hay compras.</p><?php endif; ?>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
      <?php foreach ($ultimasCompras as $c): ?>
        <div class="py-2 flex items-center justify-between text-xs">
          <span class="text-slate-700 dark:text-slate-300">
            <span class="font-mono text-slate-400">#<?= (int)$c['id_compra'] ?></span>
            <?= e($c['nombre_comercial'] ?? 'Compra sin detalle') ?><?= $c['cantidad'] ? ' × ' . (int)$c['cantidad'] : '' ?>
            <span class="text-slate-400">· <?= e($c['proveedor'] ?? 'Sin proveedor') ?></span>
          </span>
          <span class="font-mono"><span class="text-slate-400"><?= e($c['fecha_compra']) ?></span> <strong class="text-slate-900 dark:text-white ml-2"><?= dinero($c['total'], 2) ?></strong></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
// El proveedor elegido se envía con cada botón "Reordenar" y se recuerda en este navegador
(function () {
  var sel = document.getElementById('proveedor-reorden');
  try { sel.value = localStorage.getItem('app_medica_proveedor') || ''; } catch (e) {}
  sel.addEventListener('change', function () { try { localStorage.setItem('app_medica_proveedor', sel.value); } catch (e) {} });
  document.querySelectorAll('form[data-reorden]').forEach(function (f) {
    f.addEventListener('submit', function () { f.querySelector('[name=proveedor_id]').value = sel.value; });
  });
})();
</script>

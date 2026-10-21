/**
 * Interacciones comunes del layout: menú lateral, desplegables, modo oscuro,
 * búsqueda universal (Ctrl+K), personalización de tema, notificaciones
 * y filtros en vivo de listas.
 */
(function () {
  'use strict';

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  function escapar(texto) {
    return String(texto == null ? '' : texto)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function pintarIconos() { if (window.lucide) window.lucide.createIcons(); }

  window.AppUtil = { escapar: escapar, pintarIconos: pintarIconos, $: $, $$: $$ };

  /* ------------------------- Menú lateral móvil ------------------------- */
  function sidebar(abrir) {
    $('#sidebar').classList.toggle('-translate-x-full', !abrir);
    $('#sidebar-overlay').classList.toggle('hidden', !abrir);
  }

  /* ----------------------------- Desplegables --------------------------- */
  function cerrarDesplegables(excepto) {
    $$('[data-desplegable]').forEach(function (d) {
      if (d !== excepto) $('[data-desplegable-panel]', d).hidden = true;
    });
  }
  $$('[data-desplegable]').forEach(function (d) {
    $('[data-desplegable-boton]', d).addEventListener('click', function (e) {
      e.stopPropagation();
      var panel = $('[data-desplegable-panel]', d);
      cerrarDesplegables(d);
      panel.hidden = !panel.hidden;
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-desplegable]')) cerrarDesplegables();
  });

  /* ---------------------------- Notificaciones -------------------------- */
  $$('[data-notificacion]').forEach(function (n) {
    n.addEventListener('click', function () {
      if (n.dataset.leida === '1') return;
      var datos = new FormData();
      datos.append('accion', 'marcar_notificacion');
      datos.append('id', n.dataset.notificacion);
      datos.append('csrf', window.APP.csrf);
      fetch('acciones.php', { method: 'POST', body: datos, headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          if (!r.ok) return;
          n.dataset.leida = '1';
          n.classList.remove('bg-teal-50/40', 'dark:bg-teal-950/20');
          n.classList.add('hover:bg-slate-50', 'dark:hover:bg-slate-800/40');
          var contador = $('#contador-notificaciones');
          var restantes = Math.max(0, parseInt(contador.textContent, 10) - 1);
          contador.textContent = restantes;
          $('#punto-notificaciones').hidden = restantes === 0;
        });
    });
  });

  /* ----------------------- Personalización de tema ---------------------- */
  function marcarSeleccionTema() {
    var T = window.AppTema;
    $$('[data-tema-modo]').forEach(function (b) { b.dataset.sel = String(b.dataset.temaModo === T.obtener('modo')); });
    $$('[data-tema-color]').forEach(function (b) { b.dataset.sel = String(b.dataset.temaColor === T.obtener('color')); });
    $$('[data-tema-fuente]').forEach(function (b) { b.dataset.sel = String(b.dataset.temaFuente === T.obtener('fuente')); });
    $$('[data-tema-densidad]').forEach(function (b) { b.dataset.sel = String(b.dataset.temaDensidad === T.obtener('densidad')); });
  }
  document.addEventListener('tema:cambio', marcarSeleccionTema);
  marcarSeleccionTema();

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-tema-modo],[data-tema-color],[data-tema-fuente],[data-tema-densidad]');
    if (!b) return;
    if (b.dataset.temaModo) window.AppTema.fijarModo(b.dataset.temaModo);
    if (b.dataset.temaColor) window.AppTema.fijarColor(b.dataset.temaColor);
    if (b.dataset.temaFuente) window.AppTema.fijarFuente(b.dataset.temaFuente);
    if (b.dataset.temaDensidad) window.AppTema.fijarDensidad(b.dataset.temaDensidad);
  });

  /* -------------------------- Búsqueda universal ------------------------ */
  var modalBusqueda = $('#modal-busqueda');
  var inputBusqueda = $('#busqueda-input');
  var cajaResultados = $('#busqueda-resultados');
  var vacioBusqueda = cajaResultados.innerHTML;
  var temporizador = null;
  var primerEnlace = null;

  function abrirBusqueda() {
    modalBusqueda.hidden = false;
    inputBusqueda.value = '';
    cajaResultados.innerHTML = vacioBusqueda;
    primerEnlace = null;
    setTimeout(function () { inputBusqueda.focus(); }, 10);
  }
  function cerrarBusqueda() { modalBusqueda.hidden = true; }

  function seccion(titulo, icono, filas) {
    return '<div><div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">' +
      '<i data-lucide="' + icono + '" class="w-3.5 h-3.5"></i><span>' + titulo + '</span></div>' +
      '<div class="space-y-1 mt-1">' + filas + '</div></div>';
  }
  function fila(href, principal, secundario, derecha) {
    return '<a href="' + href + '" class="w-full flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-left transition-colors">' +
      '<div><p class="text-xs font-bold text-slate-900 dark:text-white">' + principal + '</p>' + secundario + '</div>' + derecha + '</a>';
  }

  function pintarResultados(q, r) {
    var html = '';
    if (r.pacientes.length) {
      html += seccion('Pacientes (' + r.pacientes.length + ')', 'users', r.pacientes.map(function (p) {
        return fila('index.php?vista=agendar&paciente=' + p.id, escapar(p.nombre_completo),
          '<p class="text-[11px] text-slate-400 font-mono">CURP: ' + escapar(p.curp) + ' · Tel: ' + escapar(p.telefono) + '</p>',
          '<span class="text-[11px] text-teal-600 dark:text-teal-400 font-medium flex items-center gap-1">Agendar Cita <i data-lucide="arrow-right" class="w-3 h-3"></i></span>');
      }).join(''));
    }
    if (r.medicos.length) {
      html += seccion('Médicos (' + r.medicos.length + ')', 'stethoscope', r.medicos.map(function (d) {
        return fila('index.php?vista=agendar&medico=' + d.id, escapar(d.nombre),
          '<p class="text-[11px] text-teal-600 dark:text-teal-400">' + escapar(d.especialidad) + ' · ' + escapar(d.consultorio) + '</p>',
          '<span class="text-[11px] text-slate-400">Ver turno</span>');
      }).join(''));
    }
    if (r.medicamentos.length) {
      html += seccion('Farmacia (' + r.medicamentos.length + ')', 'package', r.medicamentos.map(function (m) {
        return fila('index.php?vista=inventario', escapar(m.nombre),
          '<p class="text-[11px] text-slate-400">' + escapar(m.concentracion) + ' · Lote: ' + escapar(m.lote) + '</p>',
          '<span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">' + m.stock + ' u.</span>');
      }).join(''));
    }
    if (!html) {
      html = '<div class="py-8 text-center text-xs text-slate-400">No se encontraron coincidencias para "' + escapar(q) + '".</div>';
    }
    cajaResultados.innerHTML = html;
    primerEnlace = $('a', cajaResultados);
    pintarIconos();
  }

  inputBusqueda.addEventListener('input', function () {
    var q = inputBusqueda.value.trim();
    clearTimeout(temporizador);
    if (q.length < 2) { cajaResultados.innerHTML = vacioBusqueda; primerEnlace = null; return; }
    temporizador = setTimeout(function () {
      fetch('api.php?accion=buscar&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (r) { if (inputBusqueda.value.trim() === q) pintarResultados(q, r); });
    }, 180);
  });
  inputBusqueda.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && primerEnlace) window.location.href = primerEnlace.getAttribute('href');
  });
  modalBusqueda.addEventListener('click', function (e) { if (e.target === modalBusqueda) cerrarBusqueda(); });

  /* ---------------------- Botones con data-accion ----------------------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-accion]');
    if (!b) return;
    switch (b.dataset.accion) {
      case 'abrir-sidebar': sidebar(true); break;
      case 'cerrar-sidebar': sidebar(false); break;
      case 'alternar-oscuro': window.AppTema.alternarOscuro(); break;
      case 'abrir-busqueda': abrirBusqueda(); break;
      case 'cerrar-busqueda': cerrarBusqueda(); break;
      case 'abrir-tema': marcarSeleccionTema(); $('#modal-tema').hidden = false; break;
      case 'cerrar-tema': $('#modal-tema').hidden = true; break;
      case 'recargar':
        var ico = $('svg', b);
        if (ico) ico.classList.add('animate-spin');
        setTimeout(function () { window.location.reload(); }, 350);
        break;
    }
  });
  $('#modal-tema').addEventListener('click', function (e) { if (e.target.id === 'modal-tema') e.target.hidden = true; });

  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrirBusqueda(); }
    if (e.key === 'Escape') { cerrarBusqueda(); $('#modal-tema').hidden = true; cerrarDesplegables(); }
  });

  /* --------------- Confirmación antes de acciones delicadas -------------- */
  document.addEventListener('submit', function (e) {
    var b = e.submitter || $('button[data-confirmar]', e.target);
    if (b && b.dataset.confirmar && !window.confirm(b.dataset.confirmar)) e.preventDefault();
  });

  /* ------------------ Alertas que se ocultan solas --------------------- */
  $$('[data-autocerrar]').forEach(function (a) {
    setTimeout(function () {
      a.style.transition = 'opacity .3s';
      a.style.opacity = '0';
      setTimeout(function () { a.remove(); }, 320);
    }, parseInt(a.dataset.autocerrar, 10) || 5000);
  });

  /* --------------------------- Filtros en vivo -------------------------- *
   * <div data-filtro="clave">
   *   <input data-filtro-texto>
   *   <button data-filtro-opcion="Todos">  (la opción "Todos"/"Todas" muestra todo)
   *   <span data-filtro-contador></span>
   *   <el data-filtro-item data-texto="..." data-grupo="...">
   * El estado se recuerda durante la sesión del navegador.
   */
  $$('[data-filtro]').forEach(function (zona) {
    var clave = 'filtro:' + zona.dataset.filtro;
    var input = $('[data-filtro-texto]', zona);
    var opciones = $$('[data-filtro-opcion]', zona);
    var items = $$('[data-filtro-item]', zona);
    var contador = $('[data-filtro-contador]', zona);
    var vacio = $('[data-filtro-vacio]', zona);
    // Cada grupo de botones filtra un atributo distinto (data-filtro-campo, por defecto "grupo")
    var campo = function (o) { return o.dataset.filtroCampo || 'grupo'; };
    var estado = { texto: '', opciones: {} };
    opciones.forEach(function (o) { if (!(campo(o) in estado.opciones)) estado.opciones[campo(o)] = o.dataset.filtroOpcion; });
    try {
      var guardado = JSON.parse(sessionStorage.getItem(clave) || '{}');
      if (typeof guardado.texto === 'string') estado.texto = guardado.texto;
      if (guardado.opciones) Object.assign(estado.opciones, guardado.opciones);
    } catch (e) {}

    function aplicar() {
      var t = estado.texto.trim().toLowerCase();
      var visibles = 0;
      items.forEach(function (it) {
        var ok = !t || (it.dataset.texto || '').toLowerCase().indexOf(t) !== -1;
        Object.keys(estado.opciones).forEach(function (c) {
          var v = estado.opciones[c];
          if (ok && v && !/^Tod[oa]s$/.test(v)) ok = it.dataset[c] === v;
        });
        it.hidden = !ok;
        if (ok) visibles++;
      });
      opciones.forEach(function (o) { o.dataset.sel = String(estado.opciones[campo(o)] === o.dataset.filtroOpcion); });
      if (contador) contador.textContent = visibles;
      if (vacio) vacio.hidden = visibles > 0;
      try { sessionStorage.setItem(clave, JSON.stringify(estado)); } catch (e) {}
    }

    if (input) {
      input.value = estado.texto;
      input.addEventListener('input', function () { estado.texto = input.value; aplicar(); });
    }
    opciones.forEach(function (o) {
      o.addEventListener('click', function () { estado.opciones[campo(o)] = o.dataset.filtroOpcion; aplicar(); });
    });
    aplicar();
  });

  pintarIconos();
})();

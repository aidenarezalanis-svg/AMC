/**
 * Vista "Agendar Cita": buscador de pacientes, especialidad → médico → consultorio,
 * turnos libres/ocupados según horarios_disponibles, fechas rápidas, motivos sugeridos
 * y ayudas del alta de paciente.
 */
(function () {
  'use strict';
  var U = window.AppUtil;
  var $ = U.$, $$ = U.$$;
  var datos = window.AGENDAR || { medicos: [] };

  /* ------------------------- Paciente seleccionado ------------------------ */
  var inputBuscar = $('#buscar-paciente');
  var campoId = $('#paciente-id');
  var cajaSugerencias = $('#sugerencias-paciente');
  var resumen = $('#resumen-paciente');
  var temporizador = null;

  function seleccionarPaciente(p) {
    campoId.value = p ? p.id : '';
    resumen.hidden = !p;
    $('#insignia-vinculado').hidden = !p;
    cajaSugerencias.hidden = true;
    if (p) {
      inputBuscar.value = p.nombre_completo;
      $('#resumen-iniciales').textContent = p.iniciales;
      $('#resumen-nombre').textContent = p.nombre_completo;
      $('#resumen-datos').textContent = 'CURP: ' + p.curp + ' · Tel: ' + p.telefono;
    }
  }

  function mostrarSugerencias(lista) {
    if (!lista.length) {
      cajaSugerencias.innerHTML = '<p class="px-3 py-2 text-[11px] text-slate-400">Sin coincidencias. Puedes registrarlo a la derecha.</p>';
      cajaSugerencias.hidden = false;
      return;
    }
    cajaSugerencias.innerHTML =
      '<div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Resultados de búsqueda (' + lista.length + ')</div>' +
      lista.map(function (p, i) {
        return '<button type="button" data-indice="' + i + '" class="w-full flex items-center justify-between px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer border-b border-slate-100 dark:border-slate-800/50 last:border-0">' +
          '<div><p class="text-xs font-semibold text-slate-900 dark:text-white">' + U.escapar(p.nombre_completo) + '</p>' +
          '<p class="text-[10px] text-slate-400 font-mono">CURP: ' + U.escapar(p.curp) + ' · ' + U.escapar(p.telefono) + '</p></div>' +
          '<span class="text-[10px] font-medium text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950 px-2 py-0.5 rounded-md">Seleccionar</span></button>';
      }).join('');
    $$('[data-indice]', cajaSugerencias).forEach(function (b) {
      b.addEventListener('click', function () { seleccionarPaciente(lista[+b.dataset.indice]); });
    });
    cajaSugerencias.hidden = false;
  }

  inputBuscar.addEventListener('input', function () {
    if (campoId.value) seleccionarPaciente(null);
    var q = inputBuscar.value.trim();
    clearTimeout(temporizador);
    if (q.length < 2) { cajaSugerencias.hidden = true; return; }
    temporizador = setTimeout(function () {
      fetch('api.php?accion=pacientes&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (r) { if (inputBuscar.value.trim() === q && !campoId.value) mostrarSugerencias(r.pacientes); });
    }, 150);
  });
  inputBuscar.addEventListener('focus', function () {
    if (!campoId.value && inputBuscar.value.trim()) inputBuscar.dispatchEvent(new Event('input'));
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('#sugerencias-paciente') && e.target !== inputBuscar) cajaSugerencias.hidden = true;
  });
  $('#cambiar-paciente').addEventListener('click', function () {
    seleccionarPaciente(null);
    inputBuscar.value = '';
    inputBuscar.focus();
  });

  /* --------------- Especialidad → médico → consultorio --------------- */
  var selEsp = $('#especialidad');
  var selMed = $('#medico');
  var selCons = $('#consultorio');
  var campoFecha = $('#fecha');
  var avisoDia = $('#aviso-dia');
  var DIAS = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];

  function medicoActual() {
    return datos.medicos.filter(function (m) { return String(m.id) === String(selMed.value); })[0];
  }
  function diaDeFecha(f) { return f ? DIAS[new Date(f + 'T12:00:00').getDay()] : ''; }

  function llenarMedicos(idPreferido) {
    var esp = selEsp.value;
    var lista = esp ? datos.medicos.filter(function (m) { return m.especialidades.map(String).indexOf(esp) !== -1; }) : datos.medicos;
    selMed.innerHTML = '<option value="">' + (esp ? (lista.length ? 'Seleccione médico asignado...' : 'Ningún médico tiene esta especialidad') : 'Seleccione una especialidad...') + '</option>' +
      lista.map(function (m) { return '<option value="' + m.id + '">' + U.escapar(m.nombre) + '</option>'; }).join('');
    if (idPreferido && lista.some(function (m) { return String(m.id) === String(idPreferido); })) {
      selMed.value = String(idPreferido);
    } else if (esp && lista.length) {
      selMed.value = String(lista[0].id);
    }
    llenarConsultorios();
  }

  function llenarConsultorios(idPreferido) {
    var m = medicoActual();
    if (!m) {
      selCons.innerHTML = '<option value="">Seleccione un médico...</option>';
      avisoDia.hidden = true;
      cargarHorarios();
      return;
    }
    selCons.innerHTML = m.consultorios.map(function (c) {
      return '<option value="' + c.id + '" data-dias="' + U.escapar(c.dias) + '">' + U.escapar(c.nombre) + ' (' + U.escapar(c.dias) + ')</option>';
    }).join('') || '<option value="">El médico no tiene consultorio asignado</option>';
    elegirConsultorioPorDia(idPreferido);
    cargarHorarios();
  }

  /** Elige el consultorio donde el médico atiende ese día de la semana. */
  function elegirConsultorioPorDia(idPreferido) {
    var m = medicoActual();
    if (!m || !m.consultorios.length) { avisoDia.hidden = true; return; }
    var dia = diaDeFecha(campoFecha.value);
    if (idPreferido && m.consultorios.some(function (c) { return String(c.id) === String(idPreferido); })) {
      selCons.value = String(idPreferido);
    } else {
      var delDia = m.consultorios.filter(function (c) { return c.dias.split(',').map(function (d) { return d.trim(); }).indexOf(dia) !== -1; })[0];
      if (delDia) selCons.value = String(delDia.id);
    }
    var opcion = selCons.selectedOptions[0];
    var atiende = opcion && opcion.dataset.dias.split(',').map(function (d) { return d.trim(); }).indexOf(dia) !== -1;
    avisoDia.hidden = atiende;
    avisoDia.textContent = 'Aviso: en ese consultorio atiende ' + (opcion ? opcion.dataset.dias : '') + ' y la fecha elegida es ' + dia + '.';
  }

  selEsp.addEventListener('change', function () { llenarMedicos(); });
  selMed.addEventListener('change', function () {
    var m = medicoActual();
    if (m && selEsp.value && m.especialidades.map(String).indexOf(selEsp.value) === -1) {
      selEsp.value = String(m.especialidades[0] || '');
    }
    llenarConsultorios();
  });
  selCons.addEventListener('change', function () { elegirConsultorioPorDia(selCons.value); });

  /* ------------------------------- Fecha ------------------------------- */
  function fechaLocal(desplazamiento) {
    var d = new Date();
    d.setDate(d.getDate() + desplazamiento);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  $$('[data-fecha-rapida]').forEach(function (b) {
    b.addEventListener('click', function () { campoFecha.value = fechaLocal(+b.dataset.fechaRapida); alCambiarFecha(); });
  });
  function alCambiarFecha() { elegirConsultorioPorDia(); cargarHorarios(); }
  campoFecha.addEventListener('change', alCambiarFecha);

  /* ------------------------- Horarios del médico ------------------------ */
  var campoHora = $('#hora');
  var selHora = $('#selector-horario');
  var cuadricula = $('#cuadricula-horarios');
  var botonAlternar = $('#alternar-horario');
  var botonesHora = $$('[data-horario]');

  function fijarHora(h) {
    campoHora.value = h;
    selHora.value = h;
    botonesHora.forEach(function (b) { b.dataset.sel = String(b.dataset.horario === h); });
  }

  /** Marca como no disponibles los turnos ocupados o que ya pasaron. */
  function aplicarDisponibilidad(ocupados) {
    var esHoy = campoFecha.value === fechaLocal(0);
    var ahora = new Date();
    var minutosAhora = ahora.getHours() * 60 + ahora.getMinutes();
    botonesHora.forEach(function (b) {
      var partes = b.dataset.horario.split(':');
      var pasado = esHoy && (+partes[0] * 60 + +partes[1]) <= minutosAhora;
      var estado = ocupados[b.dataset.horario];
      b.disabled = pasado || (estado && estado !== 'Disponible');
      b.title = pasado ? 'Este horario ya pasó' : (estado === 'Ocupado' ? 'Ocupado' : (estado === 'Disponible' ? 'Horario publicado y libre' : ''));
    });
    $$('option', selHora).forEach(function (o) {
      var b = botonesHora.filter(function (x) { return x.dataset.horario === o.value; })[0];
      o.disabled = b.disabled;
    });
    var actual = botonesHora.filter(function (b) { return b.dataset.horario === campoHora.value; })[0];
    if (actual && actual.disabled) {
      var libre = botonesHora.filter(function (b) { return !b.disabled; })[0];
      fijarHora(libre ? libre.dataset.horario : '');
    }
  }

  function pintarProximos(lista) {
    var caja = $('#caja-proximos');
    caja.hidden = !lista.length;
    $('#lista-proximos').innerHTML = lista.map(function (h, i) {
      return '<button type="button" data-proximo="' + i + '" class="text-[10px] font-mono px-2 py-1 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 hover:bg-teal-100 cursor-pointer">' +
        U.escapar(h.fecha) + ' · ' + U.escapar(h.hora) + ' · ' + U.escapar(h.consultorio) + '</button>';
    }).join('');
    $$('[data-proximo]').forEach(function (b) {
      b.addEventListener('click', function () {
        var h = lista[+b.dataset.proximo];
        campoFecha.value = h.fecha;
        elegirConsultorioPorDia(h.consultorio_id);
        cargarHorarios(h.hora);
      });
    });
  }

  var peticion = 0;
  function cargarHorarios(horaDeseada) {
    var m = medicoActual();
    if (!m || !campoFecha.value) { aplicarDisponibilidad({}); pintarProximos([]); return; }
    var n = ++peticion;
    fetch('api.php?accion=horarios&medico=' + m.id + '&fecha=' + campoFecha.value)
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (n !== peticion) return;
        if (horaDeseada) fijarHora(horaDeseada);
        aplicarDisponibilidad(Array.isArray(r.dia) ? {} : r.dia);
        pintarProximos(r.proximos || []);
      });
  }

  botonesHora.forEach(function (b) {
    b.addEventListener('click', function () { if (!b.disabled) fijarHora(b.dataset.horario); });
  });
  selHora.addEventListener('change', function () { fijarHora(selHora.value); });
  botonAlternar.addEventListener('click', function () {
    var verClasico = !cuadricula.hidden;
    cuadricula.hidden = verClasico;
    selHora.hidden = !verClasico;
    botonAlternar.textContent = verClasico ? 'Ver cuadrícula de horarios' : 'Ver selector clásico';
  });

  /* ------------------------------ Motivos rápidos ------------------------- */
  var motivo = $('#motivo');
  $$('[data-motivo]').forEach(function (b) {
    b.addEventListener('click', function () {
      var nuevo = motivo.value.trim() ? motivo.value.trim() + ', ' + b.dataset.motivo : b.dataset.motivo;
      motivo.value = nuevo.slice(0, 255);
    });
  });

  /* --------------------------- Validación del envío ----------------------- */
  var cajaError = $('#error-cliente');
  function mostrarError(msg) {
    cajaError.innerHTML = '<div role="alert" class="animate-in p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center gap-2.5 text-xs shadow-xs">' +
      '<i data-lucide="circle-alert" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i><span>' + U.escapar(msg) + '</span></div>';
    cajaError.hidden = false;
    U.pintarIconos();
    cajaError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  $('#form-cita').addEventListener('submit', function (e) {
    var error = !campoId.value ? 'Por favor selecciona un paciente o regístralo a la derecha.'
      : !selEsp.value ? 'Por favor selecciona una especialidad médica.'
      : !selMed.value ? 'Por favor asigna un médico para la consulta.'
      : !selCons.value ? 'El médico no tiene un consultorio asignado.'
      : !campoFecha.value ? 'Por favor indica la fecha de la cita.'
      : !campoHora.value ? 'No quedan turnos libres ese día; elige otra fecha.' : null;
    if (error) { e.preventDefault(); mostrarError(error); }
  });

  /* ----------------------- Ayudas del alta de paciente -------------------- */
  var nacimiento = $('#np-nacimiento');
  var edad = $('#np-edad');
  function calcularEdad() {
    if (!nacimiento.value) { edad.textContent = ''; return; }
    var n = new Date(nacimiento.value + 'T00:00:00');
    var hoy = new Date();
    var a = hoy.getFullYear() - n.getFullYear();
    var m = hoy.getMonth() - n.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < n.getDate())) a--;
    edad.textContent = a >= 0 ? a + ' años' : '';
  }
  nacimiento.addEventListener('input', calcularEdad);
  calcularEdad();

  var curp = $('#np-curp');
  var contadorCurp = $('#np-curp-contador');
  function actualizarCurp() {
    curp.value = curp.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    contadorCurp.textContent = curp.value.length + '/18';
  }
  curp.addEventListener('input', actualizarCurp);
  actualizarCurp();

  /* ------------------------------- Inicio -------------------------------- */
  fijarHora(campoHora.value || '09:00');
  var medicoInicial = selMed.dataset.seleccionado;
  if (!selEsp.value && medicoInicial) {
    var mi = datos.medicos.filter(function (m) { return String(m.id) === medicoInicial; })[0];
    if (mi && mi.especialidades.length) selEsp.value = String(mi.especialidades[0]);
  }
  llenarMedicos(medicoInicial);
  if (datos.consultorioPrevio) elegirConsultorioPorDia(datos.consultorioPrevio);
})();

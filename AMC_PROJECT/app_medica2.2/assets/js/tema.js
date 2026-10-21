/**
 * Motor de temas — modo claro/oscuro/automático, paleta, tipografía y densidad.
 * Se carga en <head> (sin defer) para aplicar el tema antes de pintar y evitar parpadeos.
 * Requiere window.APP_TEMA = { paletas: {...}, fuentes: {...} } definido por PHP.
 */
(function () {
  var CLAVES = {
    modo: 'app_medica_theme_mode',
    color: 'app_medica_color_theme',
    fuente: 'app_medica_font_family',
    densidad: 'app_medica_density'
  };
  var DEFECTOS = { modo: 'light', color: 'teal', fuente: 'jakarta', densidad: 'comfortable' };
  var datos = window.APP_TEMA || { paletas: {}, fuentes: {} };
  var consultaSistema = window.matchMedia('(prefers-color-scheme: dark)');

  function leer(clave) {
    try { return localStorage.getItem(CLAVES[clave]) || DEFECTOS[clave]; } catch (e) { return DEFECTOS[clave]; }
  }
  function guardar(clave, valor) {
    try { localStorage.setItem(CLAVES[clave], valor); } catch (e) {}
  }

  function esOscuro() {
    var modo = leer('modo');
    return modo === 'dark' || (modo === 'system' && consultaSistema.matches);
  }

  function aplicar() {
    var root = document.documentElement;
    var oscuro = esOscuro();
    root.classList.toggle('dark', oscuro);
    root.setAttribute('data-theme', oscuro ? 'dark' : 'light');
    root.style.colorScheme = oscuro ? 'dark' : 'light';

    var paleta = datos.paletas[leer('color')] || datos.paletas[DEFECTOS.color] || {};
    Object.keys(paleta).forEach(function (k) { root.style.setProperty(k, paleta[k]); });

    var fuente = datos.fuentes[leer('fuente')] || datos.fuentes[DEFECTOS.fuente];
    if (fuente) root.style.setProperty('--font-family', fuente);

    root.setAttribute('data-density', leer('densidad'));
    document.dispatchEvent(new CustomEvent('tema:cambio'));
  }

  aplicar();
  if (consultaSistema.addEventListener) {
    consultaSistema.addEventListener('change', function () { if (leer('modo') === 'system') aplicar(); });
  }

  window.AppTema = {
    esOscuro: esOscuro,
    obtener: leer,
    alternarOscuro: function () { guardar('modo', esOscuro() ? 'light' : 'dark'); aplicar(); },
    fijarModo: function (v) { guardar('modo', v); aplicar(); },
    fijarColor: function (v) { if (datos.paletas[v]) { guardar('color', v); aplicar(); } },
    fijarFuente: function (v) { if (datos.fuentes[v]) { guardar('fuente', v); aplicar(); } },
    fijarDensidad: function (v) { guardar('densidad', v); aplicar(); }
  };
})();

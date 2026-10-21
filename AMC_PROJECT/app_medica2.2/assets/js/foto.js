/**
 * AMC-17: revisión inmediata de la foto y vista previa antes de subirla.
 * Se usa en la ficha del panel y en mi_espacio.php. El servidor vuelve a validar todo.
 *
 * <form data-foto data-max="2097152">
 *   <input type="file">  [data-foto-boton]  [data-foto-error]
 * La vista previa se pinta en [data-foto-img] y oculta [data-foto-iniciales].
 */
(function () {
  'use strict';
  var TIPOS = ['image/jpeg', 'image/png'];

  document.querySelectorAll('form[data-foto]').forEach(function (form) {
    var input = form.querySelector('input[type=file]');
    var error = form.querySelector('[data-foto-error]');
    var boton = form.querySelector('[data-foto-boton]');
    var max = parseInt(form.dataset.max, 10);

    input.addEventListener('change', function () {
      var f = input.files[0];
      var msg = '';
      if (f && TIPOS.indexOf(f.type) === -1) msg = 'Formato no permitido. Solo se aceptan imágenes JPG o PNG.';
      else if (f && f.size > max) msg = 'La imagen pesa ' + (f.size / 1048576).toFixed(1) + ' MB; el máximo es 2 MB.';
      error.textContent = msg;
      error.hidden = !msg;
      boton.disabled = !f || !!msg;
      if (f && !msg) {
        var img = document.querySelector('[data-foto-img]');
        var iniciales = document.querySelector('[data-foto-iniciales]');
        img.src = URL.createObjectURL(f);
        img.hidden = false;
        if (iniciales) iniciales.hidden = true;
      }
    });
    form.addEventListener('submit', function () {
      boton.disabled = true;
      boton.textContent = 'Subiendo…';
    });
  });
})();

/**
 * Utilidades compartidas por los módulos del formulario de nueva venta.
 *
 * El dinero se maneja en centavos enteros para que sumar líneas y compararlo con
 * lo pagado no arrastre errores de coma flotante (0.1 + 0.2).
 */
(function (window) {
    'use strict';

    var secuencia = 0;

    function aCentavos(valor) {
        return Math.round((parseFloat(valor) || 0) * 100);
    }

    function aTexto(centavos) {
        return (centavos / 100).toFixed(2);
    }

    function marcarInvalido(input, invalido) {
        input.classList.toggle('is-invalid', invalido);
        input.setAttribute('aria-invalid', invalido ? 'true' : 'false');
    }

    function marcarValidez(input) {
        var valido = input.checkValidity();
        marcarInvalido(input, !valido);
        return valido;
    }

    function idUnico(prefijo) {
        secuencia += 1;
        return prefijo + '-' + secuencia;
    }

    function avisar(titulo, texto) {
        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            timer: 3000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    }

    function debounce(fn, ms) {
        var temporizador;
        return function () {
            var args = arguments;
            clearTimeout(temporizador);
            temporizador = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    window.VentaUtil = {
        aCentavos: aCentavos,
        aTexto: aTexto,
        marcarInvalido: marcarInvalido,
        marcarValidez: marcarValidez,
        idUnico: idUnico,
        avisar: avisar,
        debounce: debounce
    };
})(window);

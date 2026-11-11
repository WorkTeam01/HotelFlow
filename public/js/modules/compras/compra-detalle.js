/**
 * Detalle de la compra (formulario de nueva compra).
 *
 * Las filas se clonan de un <template>; los eventos de cantidad y precio y el botón de
 * quitar son delegados sobre el <tbody>. Los importes se manejan en centavos enteros
 * (VentaUtil). El total mostrado es informativo: el servidor lo recalcula desde las líneas.
 */
(function (window) {
    'use strict';

    var U = window.VentaUtil;

    function init() {
        var tbody = document.querySelector('#tabla-compra tbody');
        var plantilla = document.getElementById('template-fila-compra');
        var envoltura = document.getElementById('tabla-compra').closest('.table-responsive');
        var vacio = document.getElementById('detalle-vacio');
        var spanTotal = document.getElementById('total-compra');
        var campos = new WeakMap();

        function filas() {
            return tbody.querySelectorAll('tr.fila-compra');
        }

        function subtotalCentavos(fila) {
            var f = campos.get(fila);
            return (parseInt(f.cantidad.value, 10) || 0) * U.aCentavos(f.precio.value);
        }

        function recalcular() {
            var total = 0;
            filas().forEach(function (fila) {
                var subtotal = subtotalCentavos(fila);
                total += subtotal;
                campos.get(fila).subtotal.textContent = U.aTexto(subtotal);
            });

            spanTotal.textContent = U.aTexto(total);

            var hayProductos = filas().length > 0;
            vacio.hidden = hayProductos;
            envoltura.hidden = !hayProductos;
        }

        function agregar(producto) {
            var existente = tbody.querySelector('tr[data-idproducto="' + producto.idproducto + '"]');
            if (existente) {
                // Un producto repetido suma una unidad (cómodo con lector de código)
                var cantidad = campos.get(existente).cantidad;
                cantidad.value = (parseInt(cantidad.value, 10) || 0) + 1;
                cantidad.focus();
                U.avisar('Producto ya agregado', 'Se sumó una unidad a ' + producto.nombre);
                recalcular();
                return;
            }

            var fila = plantilla.content.firstElementChild.cloneNode(true);
            var f = {
                cantidad: fila.querySelector('.cantidad'),
                precio: fila.querySelector('.precio'),
                subtotal: fila.querySelector('.subtotal')
            };
            campos.set(fila, f);

            fila.dataset.idproducto = producto.idproducto;
            fila.querySelector('.idproducto').value = producto.idproducto;
            fila.querySelector('.nombre-producto').textContent = producto.nombre;
            fila.querySelector('.codigo-producto').textContent = producto.codigo || 'N/A';
            fila.querySelector('.stock-actual').textContent = 'Stock actual: ' + producto.stock;

            // Último costo como sugerencia editable; si es 0 queda vacío e inválido hasta ingresarlo
            var sugerido = parseFloat(producto.preciocompra) || 0;
            f.precio.value = sugerido > 0 ? sugerido.toFixed(2) : '';

            // Cada campo apunta a su mensaje de error para que los lectores de pantalla lo anuncien
            [f.cantidad, f.precio].forEach(function (input) {
                var error = input.nextElementSibling;
                if (error && error.classList.contains('invalid-feedback')) {
                    error.id = U.idUnico('feedback');
                    input.setAttribute('aria-describedby', error.id);
                }
                input.setAttribute('aria-invalid', 'false');
            });

            tbody.appendChild(fila);
            recalcular();
        }

        tbody.addEventListener('input', function (e) {
            var input = e.target;
            if (!input.closest('tr.fila-compra') || !(input.classList.contains('cantidad') || input.classList.contains('precio'))) {
                return;
            }
            U.marcarValidez(input);
            recalcular();
        });

        tbody.addEventListener('click', function (e) {
            var boton = e.target.closest('.btn-eliminar-fila');
            if (!boton) return;
            boton.closest('tr').remove();
            recalcular();
        });

        /** Marca los campos inválidos de todas las filas (el form usa novalidate). */
        function validar() {
            var todas = filas();
            var valido = todas.length > 0;
            var primerInvalido = null;
            todas.forEach(function (fila) {
                var f = campos.get(fila);
                [f.cantidad, f.precio].forEach(function (input) {
                    if (!U.marcarValidez(input)) {
                        valido = false;
                        if (!primerInvalido) primerInvalido = input;
                    }
                });
            });
            if (primerInvalido) primerInvalido.focus();
            return valido;
        }

        recalcular();

        return {
            agregar: agregar,
            validar: validar,
            cantidadLineas: function () { return filas().length; },
            total: function () { return spanTotal.textContent; }
        };
    }

    window.CompraDetalle = { init: init };
})(window);

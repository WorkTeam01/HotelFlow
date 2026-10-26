/**
 * Detalle (carrito) del formulario de nueva venta.
 *
 * Las filas se clonan de un <template>; los eventos de cantidad, precio y
 * descuento y el botón Eliminar son delegados sobre el <tbody>, así que agregar
 * un producto no registra listeners. Las referencias a los campos de cada fila
 * se guardan una vez para no consultar el DOM en cada tecla.
 */
(function (window) {
    'use strict';

    var U = window.VentaUtil;

    function init(opciones) {
        var tbody = document.querySelector('#tabla-productos tbody');
        var plantilla = document.getElementById('template-fila-producto');
        var envoltura = document.getElementById('tabla-productos').closest('.table-responsive');
        var vacio = document.getElementById('carrito-vacio');
        var spanSubtotal = document.getElementById('subtotal-venta');
        var spanDescuento = document.getElementById('descuento-total');
        var spanTotal = document.getElementById('total-venta');
        var inputTotal = document.getElementById('totalventa-hidden');
        var campos = new WeakMap();

        function filas() {
            return tbody.querySelectorAll('tr.fila-producto');
        }

        function importes(fila) {
            var f = campos.get(fila);
            var bruto = (parseInt(f.cantidad.value, 10) || 0) * U.aCentavos(f.precio.value);
            return { bruto: bruto, descuento: U.aCentavos(f.descuento.value) };
        }

        // El servidor rechaza un descuento mayor al subtotal de la línea; se avisa antes de enviar
        function validarDescuento(fila) {
            var f = campos.get(fila);
            var i = importes(fila);
            f.descuento.setCustomValidity(i.descuento > i.bruto ? 'El descuento supera el subtotal de la línea' : '');
            return U.marcarValidez(f.descuento);
        }

        function recalcular() {
            var subtotal = 0;
            var descuento = 0;

            filas().forEach(function (fila) {
                var i = importes(fila);
                subtotal += i.bruto;
                descuento += i.descuento;
                campos.get(fila).subtotal.textContent = U.aTexto(i.bruto - i.descuento);
            });

            var total = subtotal - descuento;
            spanSubtotal.textContent = U.aTexto(subtotal);
            spanDescuento.textContent = U.aTexto(descuento);
            spanTotal.textContent = U.aTexto(total);
            inputTotal.value = U.aTexto(total);

            var hayProductos = filas().length > 0;
            vacio.hidden = hayProductos;
            envoltura.hidden = !hayProductos;

            opciones.alCambiarTotal(total);
        }

        function agregar(producto) {
            if (tbody.querySelector('tr[data-idproducto="' + producto.idproducto + '"]')) {
                Swal.fire('Producto ya agregado', 'Este producto ya está en el detalle de la venta', 'info');
                return;
            }

            var fila = plantilla.content.firstElementChild.cloneNode(true);
            var f = {
                cantidad: fila.querySelector('.cantidad'),
                precio: fila.querySelector('.precio'),
                descuento: fila.querySelector('.descuento'),
                subtotal: fila.querySelector('.subtotal')
            };
            campos.set(fila, f);

            fila.dataset.idproducto = producto.idproducto;
            fila.querySelector('.idproducto').value = producto.idproducto;
            fila.querySelector('.nombre-producto').textContent = producto.nombre;
            fila.querySelector('.codigo-producto').textContent = producto.codigo || 'N/A';
            fila.querySelector('.stock-disponible').textContent = 'Disponible: ' + producto.stock;
            f.cantidad.max = producto.stock;
            f.precio.value = parseFloat(producto.precioventa).toFixed(2);

            // Cada campo apunta a su mensaje de error para que los lectores de pantalla lo anuncien
            [f.cantidad, f.precio, f.descuento].forEach(function (input) {
                var error = input.parentElement.querySelector('.invalid-feedback') || input.nextElementSibling;
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
            var fila = input.closest('tr.fila-producto');
            if (!fila || !(input.classList.contains('cantidad') || input.classList.contains('precio') || input.classList.contains('descuento'))) {
                return;
            }

            if (input.classList.contains('cantidad')) {
                var maximo = parseInt(input.max, 10) || 0;
                if (parseInt(input.value, 10) > maximo) {
                    input.value = maximo;
                    Swal.fire('Stock insuficiente', 'Máximo disponible: ' + maximo, 'warning');
                }
            }

            U.marcarValidez(input);
            validarDescuento(fila);
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
            todas.forEach(function (fila) {
                var f = campos.get(fila);
                [f.cantidad, f.precio].forEach(function (input) {
                    if (!U.marcarValidez(input)) valido = false;
                });
                if (!validarDescuento(fila)) valido = false;
            });
            return valido;
        }

        recalcular();

        return { agregar: agregar, validar: validar };
    }

    window.VentaDetalle = { init: init };
})(window);

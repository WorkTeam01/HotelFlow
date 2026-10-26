/**
 * Forma de pago del formulario de nueva venta: pago único o mixto.
 *
 * Pago único y cada tarjeta del mixto comparten un mismo bloque de campos
 * (método, monto, recibido, cambio); `contexto()` los reúne y `refrescar()`
 * aplica a ambos la misma regla, de modo que no hay dos copias del cálculo del
 * cambio ni del estado visual. Los selects de método son Select2, que solo
 * dispara `change` por jQuery: se escuchan con jQuery, no con addEventListener.
 */
(function (window, $) {
    'use strict';

    var U = window.VentaUtil;

    function init() {
        var radioUnico = document.querySelector('#btn-pago-unico input');
        var radioMixto = document.querySelector('#btn-pago-mixto input');
        var seccionUnico = document.getElementById('seccion-pago-unico');
        var seccionMixto = document.getElementById('seccion-pago-mixto');
        var contenedor = document.getElementById('contenedor-pagos');
        var plantilla = document.getElementById('template-metodo-pago');
        var pagadoDisplay = document.getElementById('total-pagado-display');
        var diferenciaDisplay = document.getElementById('diferencia-pago');
        var tarjetaPago = document.getElementById('card-forma-pago');

        var unico = {
            select: document.getElementById('metodopago-unico'),
            monto: document.getElementById('monto-unico'),
            recibido: document.getElementById('pago-recibido-unico'),
            divRecibido: document.getElementById('div-pago-recibido-unico'),
            divCambio: document.getElementById('div-cambio-unico'),
            cambio: document.getElementById('cambio-unico'),
            cambioHidden: document.getElementById('cambio-unico-hidden')
        };

        var total = 0;
        var pagado = 0;

        function esMixto() {
            return radioMixto.checked;
        }

        function tarjetas() {
            return contenedor.querySelectorAll('.metodo-pago-item');
        }

        function contexto(item) {
            return {
                select: item.querySelector('.select-metodo-pago'),
                monto: item.querySelector('.monto-pago'),
                recibido: item.querySelector('.pago-recibido'),
                divRecibido: item.querySelector('.div-pago-recibido'),
                divCambio: item.querySelector('.div-cambio'),
                cambio: item.querySelector('.cambio-pago'),
                cambioHidden: item.querySelector('.cambio-hidden')
            };
        }

        /**
         * Recalcula cambio, visibilidad de "Pago recibido" y estado visual de un
         * bloque de pago. Solo el efectivo pide monto recibido; los demás métodos
         * cobran exactamente el monto.
         */
        function refrescar(c) {
            var efectivo = c.select.value === 'Efectivo';
            var monto = U.aCentavos(c.monto.value);

            c.divRecibido.hidden = !efectivo;
            c.recibido.required = efectivo;
            if (!efectivo) {
                c.recibido.value = c.monto.value;
            }

            var recibido = U.aCentavos(c.recibido.value);
            var insuficiente = efectivo && monto > 0 && recibido < monto;
            var cambio = efectivo && monto > 0 && !insuficiente ? recibido - monto : 0;

            c.cambio.value = U.aTexto(cambio);
            c.cambioHidden.value = U.aTexto(cambio);
            c.divCambio.hidden = !(efectivo && monto > 0);
            U.marcarInvalido(c.recibido, insuficiente);
            pintarEstadoPago();
        }

        /** Borde de la tarjeta de pago: verde si el cobro está completo, rojo si falta algo; neutro sin total. */
        function pintarEstadoPago() {
            var estado = total <= 0 ? 'primary' : (esValido() ? 'success' : 'danger');
            tarjetaPago.classList.remove('card-primary', 'card-success', 'card-danger');
            tarjetaPago.classList.add('card-' + estado);
        }

        function recalcularPagado() {
            if (esMixto()) {
                pagado = 0;
                tarjetas().forEach(function (item) {
                    pagado += U.aCentavos(item.querySelector('.monto-pago').value);
                });
            } else {
                pagado = U.aCentavos(unico.monto.value);
            }
            pagadoDisplay.textContent = U.aTexto(pagado) + ' Bs.';

            var diferencia = total - pagado;
            diferenciaDisplay.classList.remove('text-danger', 'text-warning', 'text-success');
            if (Math.abs(diferencia) <= 1) {
                diferenciaDisplay.textContent = 'Pago completo';
                diferenciaDisplay.classList.add('text-success');
            } else if (diferencia > 0) {
                diferenciaDisplay.textContent = 'Falta por pagar: ' + U.aTexto(diferencia) + ' Bs.';
                diferenciaDisplay.classList.add('text-danger');
            } else {
                diferenciaDisplay.textContent = 'Sobrepago: ' + U.aTexto(-diferencia) + ' Bs.';
                diferenciaDisplay.classList.add('text-warning');
            }
            pintarEstadoPago();
        }

        function renumerar() {
            tarjetas().forEach(function (item, indice) {
                item.querySelector('.metodo-pago-titulo').textContent = 'Método de pago #' + (indice + 1);
            });
        }

        function vincularCampo(input, etiqueta, prefijo) {
            input.id = U.idUnico(prefijo);
            if (etiqueta) etiqueta.setAttribute('for', input.id);
            var error = input.nextElementSibling;
            if (error && error.classList.contains('invalid-feedback')) {
                error.id = 'feedback-' + input.id;
                input.setAttribute('aria-describedby', error.id);
            }
            input.setAttribute('aria-invalid', 'false');
        }

        function agregarMetodo() {
            var item = plantilla.content.firstElementChild.cloneNode(true);
            var c = contexto(item);

            // Los ids de la plantilla se repiten en cada clon: se regeneran junto con su label
            vincularCampo(c.select, item.querySelector('label[for="metodopago-mixto"]'), 'metodopago-mixto');
            vincularCampo(c.monto, item.querySelector('label[for="monto-mixto"]'), 'monto-pago');
            vincularCampo(c.recibido, item.querySelector('label[for="pagorecibido-mixto"]'), 'recibido-pago');
            c.cambio.id = U.idUnico('cambio-mixto');
            item.querySelector('label[for="cambio-mixto"]').setAttribute('for', c.cambio.id);

            contenedor.appendChild(item);
            initializeSelect2($(c.select));
            renumerar();

            // Sugerir el saldo pendiente como monto de la nueva tarjeta
            c.monto.value = U.aTexto(Math.max(total - pagado, 0));
            refrescar(c);
            recalcularPagado();
        }

        // Sincroniza el monto único y, con una sola tarjeta mixta, su monto con el total
        function setTotal(centavos) {
            total = centavos;
            unico.monto.value = U.aTexto(total);
            if (unico.select.value !== 'Efectivo') {
                unico.recibido.value = unico.monto.value;
            }
            refrescar(unico);

            if (esMixto() && tarjetas().length === 1) {
                var c = contexto(tarjetas()[0]);
                c.monto.value = U.aTexto(total);
                if (c.select.value === 'Efectivo' && U.aCentavos(c.recibido.value) < total) {
                    c.recibido.value = U.aTexto(total);
                }
                refrescar(c);
            }
            recalcularPagado();
        }

        /** El total pagado debe cubrir exactamente el total y el efectivo, cubrir lo recibido. */
        function esValido() {
            if (total <= 0 || Math.abs(pagado - total) > 1) return false;

            if (!esMixto()) {
                return unico.select.value !== 'Efectivo' || U.aCentavos(unico.recibido.value) >= total;
            }

            return Array.prototype.every.call(tarjetas(), function (item) {
                var c = contexto(item);
                var monto = U.aCentavos(c.monto.value);
                if (monto <= 0) return false;
                return c.select.value !== 'Efectivo' || U.aCentavos(c.recibido.value) >= monto;
            });
        }

        $(radioUnico).on('change', function () {
            seccionUnico.hidden = false;
            seccionMixto.hidden = true;
            recalcularPagado();
        });

        $(radioMixto).on('change', function () {
            seccionUnico.hidden = true;
            seccionMixto.hidden = false;
            // Con el modo mixto activo lo pagado ya no es el monto único: la primera tarjeta sugiere el total completo
            recalcularPagado();
            if (tarjetas().length === 0) agregarMetodo();
        });

        $(unico.select).on('change', function () { refrescar(unico); });
        unico.recibido.addEventListener('input', function () { refrescar(unico); });

        document.getElementById('btn-agregar-pago').addEventListener('click', agregarMetodo);

        $(contenedor).on('change', '.select-metodo-pago', function () {
            refrescar(contexto(this.closest('.metodo-pago-item')));
        });

        contenedor.addEventListener('input', function (e) {
            var item = e.target.closest('.metodo-pago-item');
            if (!item) return;
            if (e.target.classList.contains('monto-pago') || e.target.classList.contains('pago-recibido')) {
                refrescar(contexto(item));
                recalcularPagado();
            }
        });

        contenedor.addEventListener('click', function (e) {
            var boton = e.target.closest('.btn-eliminar-pago');
            if (!boton) return;
            if (tarjetas().length <= 1) {
                Swal.fire('Atención', 'Debe haber al menos un método de pago', 'warning');
                return;
            }
            boton.closest('.metodo-pago-item').remove();
            renumerar();
            recalcularPagado();
        });

        initializeSelect2('#metodopago-unico');
        unico.monto.value = '0.00';
        unico.recibido.value = '0.00';
        refrescar(unico);
        recalcularPagado();

        return {
            setTotal: setTotal,
            esValido: esValido,
            tipo: function () { return esMixto() ? 'mixto' : 'unico'; }
        };
    }

    window.VentaPagos = { init: init };
})(window, jQuery);

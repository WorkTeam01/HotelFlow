/**
 * Modal de búsqueda de producto del formulario de nueva compra.
 *
 * El texto de búsqueda de cada producto se normaliza una sola vez; la lista se pinta
 * con un DocumentFragment y un único listener delegado. Enter con un código exacto
 * (lector de código de barras) agrega el producto sin elegirlo de la lista.
 * Requiere ventas/venta-util.js (VentaUtil.debounce).
 */
(function (window, $) {
    'use strict';

    function itemProducto(producto, indice) {
        var item = document.createElement('button');
        item.type = 'button';
        item.dataset.indice = indice;
        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        item.setAttribute('aria-label', 'Agregar ' + producto.nombre);

        var info = document.createElement('span');
        var nombre = document.createElement('strong');
        nombre.textContent = producto.nombre;
        var detalle = document.createElement('small');
        detalle.className = 'text-muted d-block';
        detalle.textContent = 'Código: ' + (producto.codigo || 'N/A') + ' · Stock actual: ' + producto.stock;
        info.appendChild(nombre);
        info.appendChild(detalle);

        var costo = document.createElement('span');
        costo.className = 'text-right';
        if (producto.stock <= producto.stock_minimo) {
            var alerta = document.createElement('span');
            alerta.className = 'badge comp-bajo-minimo d-block mb-1';
            alerta.textContent = 'Bajo mínimo';
            costo.appendChild(alerta);
        }
        var precio = document.createElement('span');
        precio.className = 'badge badge-primary badge-pill';
        precio.textContent = 'Último costo ' + parseFloat(producto.preciocompra).toFixed(2) + ' Bs.';
        costo.appendChild(precio);

        item.appendChild(info);
        item.appendChild(costo);
        return item;
    }

    /**
     * @param {{productos: Array, alElegir: Function}} opciones
     */
    function init(opciones) {
        var registros = opciones.productos.map(function (producto) {
            return {
                producto: producto,
                texto: ((producto.nombre || '') + ' ' + (producto.codigo || '')).toLowerCase()
            };
        });
        var $modal = $('#modal-productos');
        var buscador = document.getElementById('modal-buscar-producto');
        var lista = document.getElementById('lista-productos-modal');
        var sinResultados = $modal.find('[data-sin-resultados]')[0];
        var visibles = [];

        function pintar(filtrados) {
            visibles = filtrados;
            sinResultados.hidden = filtrados.length !== 0;

            var fragmento = document.createDocumentFragment();
            filtrados.forEach(function (registro, indice) {
                fragmento.appendChild(itemProducto(registro.producto, indice));
            });
            lista.replaceChildren(fragmento);
        }

        function filtrar() {
            var termino = buscador.value.trim().toLowerCase();
            pintar(termino === '' ? registros : registros.filter(function (r) {
                return r.texto.indexOf(termino) !== -1;
            }));
        }

        function elegir(producto) {
            opciones.alElegir(producto);
            $modal.modal('hide');
        }

        buscador.addEventListener('input', VentaUtil.debounce(filtrar, 120));

        buscador.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            var codigo = buscador.value.trim().toLowerCase();
            if (codigo === '') return;
            var exacto = registros.filter(function (r) {
                return r.producto.codigo && String(r.producto.codigo).toLowerCase() === codigo;
            })[0];
            if (exacto) {
                elegir(exacto.producto);
            }
        });

        lista.addEventListener('click', function (e) {
            var boton = e.target.closest('button[data-indice]');
            if (!boton) return;
            elegir(visibles[parseInt(boton.dataset.indice, 10)].producto);
        });

        // Los eventos de Bootstrap 4 se escuchan con jQuery: BS4 los dispara con $.trigger
        $modal.on('shown.bs.modal', function () {
            buscador.value = '';
            pintar(registros);
            buscador.focus();
        });

        return {
            buscarPorId: function (id) {
                var r = registros.filter(function (x) { return x.producto.idproducto === id; })[0];
                return r ? r.producto : null;
            }
        };
    }

    window.CompraSelector = { init: init };
})(window, jQuery);

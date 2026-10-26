/**
 * Modales de búsqueda de producto y de cliente del formulario de nueva venta.
 *
 * El texto de búsqueda de cada registro se normaliza una sola vez al iniciar y
 * la lista se pinta con un DocumentFragment y un único listener delegado, así
 * escribir en el buscador no recrea listeners ni recorre todos los campos.
 */
(function (window, $) {
    'use strict';

    function crearListado(opciones) {
        var registros = opciones.registros.map(function (item) {
            return { item: item, texto: opciones.textoBusqueda(item).toLowerCase() };
        });
        var $modal = $(opciones.modal);
        var buscador = $modal.find('input[type="text"]')[0];
        var lista = $modal.find('.list-group')[0];
        var sinResultados = $modal.find('[data-sin-resultados]')[0];
        var visibles = [];

        function pintar(filtrados) {
            visibles = filtrados;
            sinResultados.hidden = filtrados.length !== 0;

            var fragmento = document.createDocumentFragment();
            filtrados.forEach(function (registro, indice) {
                fragmento.appendChild(opciones.crearItem(registro.item, indice));
            });
            lista.replaceChildren(fragmento);
        }

        function filtrar() {
            var termino = buscador.value.trim().toLowerCase();
            pintar(termino === '' ? registros : registros.filter(function (r) {
                return r.texto.indexOf(termino) !== -1;
            }));
        }

        buscador.addEventListener('input', VentaUtil.debounce(filtrar, 120));

        lista.addEventListener('click', function (e) {
            var boton = e.target.closest('button[data-indice]');
            if (!boton) return;
            opciones.alElegir(visibles[parseInt(boton.dataset.indice, 10)].item);
            $modal.modal('hide');
        });

        // Los eventos de Bootstrap 4 se escuchan con jQuery: BS4 los dispara con $.trigger
        $modal.on('shown.bs.modal', function () {
            buscador.value = '';
            pintar(registros);
            buscador.focus();
        });
    }

    function nombreCompleto(cliente) {
        return (cliente.nombre + ' ' + cliente.apellidopaterno + ' ' + (cliente.apellidomaterno || '')).trim();
    }

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
        detalle.textContent = 'Código: ' + (producto.codigo || 'N/A') + ' · Stock: ' + producto.stock;
        info.appendChild(nombre);
        info.appendChild(detalle);

        var precio = document.createElement('span');
        precio.className = 'badge badge-primary badge-pill';
        precio.textContent = parseFloat(producto.precioventa).toFixed(2) + ' Bs.';

        item.appendChild(info);
        item.appendChild(precio);
        return item;
    }

    function itemCliente(cliente, indice) {
        var item = document.createElement('button');
        item.type = 'button';
        item.dataset.indice = indice;
        item.className = 'list-group-item list-group-item-action';

        var documento = document.createElement('strong');
        documento.textContent = cliente.numdocumento;
        item.appendChild(documento);
        item.appendChild(document.createTextNode(' - ' + nombreCompleto(cliente)));
        return item;
    }

    /**
     * Ficha lateral del cliente elegido. El cliente es opcional: sin elegir
     * ninguno la venta queda como "Consumidor Final".
     */
    function seleccionarCliente(cliente) {
        document.getElementById('idcliente').value = cliente.idpersona;
        document.getElementById('nombre-cliente').textContent = nombreCompleto(cliente);
        document.getElementById('documento-cliente').textContent = cliente.numdocumento;
        document.getElementById('sin-cliente-seleccionado').hidden = true;
        document.getElementById('info-cliente-seleccionado').hidden = false;
    }

    /**
     * @param {{productos: Array, clientes: Array, alElegirProducto: Function}} opciones
     * @returns {{seleccionarCliente: Function}}
     */
    function init(opciones) {
        crearListado({
            modal: '#modal-productos',
            registros: opciones.productos,
            textoBusqueda: function (p) { return (p.nombre || '') + ' ' + (p.codigo || ''); },
            crearItem: itemProducto,
            alElegir: opciones.alElegirProducto
        });

        crearListado({
            modal: '#modal-clientes',
            registros: opciones.clientes,
            textoBusqueda: function (c) {
                return [c.numdocumento, c.nombre, c.apellidopaterno, c.apellidomaterno].join(' ');
            },
            crearItem: itemCliente,
            alElegir: seleccionarCliente
        });

        document.getElementById('btn-cambiar-cliente').addEventListener('click', function () {
            $('#modal-clientes').modal('show');
        });

        return { seleccionarCliente: seleccionarCliente };
    }

    window.VentaSelectores = { init: init };
})(window, jQuery);

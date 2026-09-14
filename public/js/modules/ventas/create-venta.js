/**
 * create-venta.js - Scripts para el formulario de ventas con pago único o mixto
 *
 * Este archivo contiene las funciones y eventos para el funcionamiento
 * del formulario de creación de ventas (views/ventas/create.php), incluyendo
 * la gestión de pagos mixtos y únicos, los modales de búsqueda de producto y
 * cliente, y la validación del detalle.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Datos exportados desde PHP vía atributos data-* del formulario
    const formVenta = document.getElementById('form-venta');
    const productosDisponibles = JSON.parse(formVenta.dataset.productos || '[]');
    const clientesDisponibles = JSON.parse(formVenta.dataset.clientes || '[]');

    // Preseleccionar cliente si se llegó desde su ficha (views/clientes/show.php?id=...&cliente=idcliente)
    const idClientePreseleccionado = new URLSearchParams(window.location.search).get('cliente');
    if (idClientePreseleccionado) {
        const clientePreseleccionado = clientesDisponibles.find(
            c => String(c.idpersona) === String(idClientePreseleccionado)
        );
        if (clientePreseleccionado) {
            seleccionarCliente(clientePreseleccionado);
        }
    }

    // Referencias a elementos del DOM
    const totalVentaSpan = document.getElementById('total-venta');

    // Elementos relacionados con pagos
    const btnAgregarPago = document.getElementById('btn-agregar-pago');
    const contenedorPagos = document.getElementById('contenedor-pagos');
    const templateMetodoPago = document.getElementById('template-metodo-pago').innerHTML;
    const totalPagadoDisplay = document.getElementById('total-pagado-display');
    const diferenciaPagoElement = document.getElementById('diferencia-pago');

    // Elementos para pago único/mixto
    const btnPagoUnico = document.getElementById('btn-pago-unico');
    const btnPagoMixto = document.getElementById('btn-pago-mixto');
    const seccionPagoUnico = document.getElementById('seccion-pago-unico');
    const seccionPagoMixto = document.getElementById('seccion-pago-mixto');
    const metodoPagoUnico = document.getElementById('metodopago-unico');
    const montoUnico = document.getElementById('monto-unico');
    const pagoRecibidoUnico = document.getElementById('pago-recibido-unico');
    const divPagoRecibidoUnico = document.getElementById('div-pago-recibido-unico');
    const divCambioUnico = document.getElementById('div-cambio-unico');
    const cambioUnico = document.getElementById('cambio-unico');
    const cambioUnicoHidden = document.getElementById('cambio-unico-hidden');

    // Variables globales
    let totalVentaActual = 0;
    let totalPagado = 0;

    // Inicializar modo de pago único
    initPagoUnico();
    initializeSelect2('#metodopago-unico');

    // Estado inicial del carrito (sin productos)
    actualizarVisibilidadCarritoVacio();
    calcularTotalPagado();

    // Cambiar entre pago único y mixto
    btnPagoUnico.addEventListener('click', function () {
        seccionPagoUnico.style.display = '';
        seccionPagoMixto.style.display = 'none';
        calcularTotalPagado();
    });

    btnPagoMixto.addEventListener('click', function () {
        seccionPagoUnico.style.display = 'none';
        seccionPagoMixto.style.display = '';
        // Si no hay métodos de pago añadidos, agregar uno
        if (document.querySelectorAll('#contenedor-pagos .metodo-pago-item').length === 0) {
            agregarMetodoPago();
        }
        calcularTotalPagado();
    });

    // Configurar eventos para pago único
    metodoPagoUnico.addEventListener('change', function () {
        if (this.value === 'Efectivo') {
            divPagoRecibidoUnico.style.display = '';
            pagoRecibidoUnico.required = true;
            calcularCambioUnico();
        } else {
            divPagoRecibidoUnico.style.display = 'none';
            divCambioUnico.style.display = 'none';
            pagoRecibidoUnico.required = false;
            pagoRecibidoUnico.value = montoUnico.value;
            cambioUnico.value = '0.00';
            cambioUnicoHidden.value = '0.00';
        }
        actualizarEstadoPagoUnico();
    });

    pagoRecibidoUnico.addEventListener('input', function () {
        if (metodoPagoUnico.value === 'Efectivo') {
            calcularCambioUnico();
        }
        actualizarEstadoPagoUnico();
    });

    // Añadir método de pago al modo mixto
    btnAgregarPago.addEventListener('click', agregarMetodoPago);

    // ---- Modal de productos ----
    const modalProductos = $('#modal-productos');
    const modalBuscarProducto = document.getElementById('modal-buscar-producto');
    const listaProductosModal = document.getElementById('lista-productos-modal');
    const sinResultadosProductos = document.getElementById('sin-resultados-productos');

    modalProductos.on('shown.bs.modal', function () {
        modalBuscarProducto.value = '';
        renderizarListaProductos(productosDisponibles);
        modalBuscarProducto.focus();
    });

    modalBuscarProducto.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();
        const filtrados = termino.length === 0
            ? productosDisponibles
            : productosDisponibles.filter(p =>
                (p.nombre && p.nombre.toLowerCase().includes(termino)) ||
                (p.codigo && p.codigo.toLowerCase().includes(termino))
            );
        renderizarListaProductos(filtrados);
    });

    function renderizarListaProductos(productos) {
        listaProductosModal.innerHTML = '';
        sinResultadosProductos.style.display = productos.length === 0 ? 'block' : 'none';

        productos.forEach(producto => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            item.setAttribute('aria-label', `Agregar ${producto.nombre}`);

            const infoSpan = document.createElement('span');
            const nombreStrong = document.createElement('strong');
            nombreStrong.textContent = producto.nombre;
            const detalleSmall = document.createElement('small');
            detalleSmall.className = 'text-muted d-block';
            detalleSmall.textContent = `Código: ${producto.codigo || 'N/A'} · Stock: ${producto.stock}`;
            infoSpan.appendChild(nombreStrong);
            infoSpan.appendChild(detalleSmall);

            const precioBadge = document.createElement('span');
            precioBadge.className = 'badge badge-primary badge-pill';
            precioBadge.textContent = parseFloat(producto.precioventa).toFixed(2) + ' Bs.';

            item.appendChild(infoSpan);
            item.appendChild(precioBadge);

            item.addEventListener('click', () => {
                agregarProductoAlDetalle(producto);
                modalProductos.modal('hide');
            });
            listaProductosModal.appendChild(item);
        });
    }

    // ---- Modal de clientes ----
    const modalClientes = $('#modal-clientes');
    const modalBuscarCliente = document.getElementById('modal-buscar-cliente');
    const listaClientesModal = document.getElementById('lista-clientes-modal');
    const sinResultadosClientes = document.getElementById('sin-resultados-clientes');

    modalClientes.on('shown.bs.modal', function () {
        modalBuscarCliente.value = '';
        renderizarListaClientes(clientesDisponibles);
        modalBuscarCliente.focus();
    });

    modalBuscarCliente.addEventListener('input', function () {
        const termino = this.value.trim();
        const filtrados = termino.length === 0 ? clientesDisponibles : buscarClientes(termino);
        renderizarListaClientes(filtrados);
    });

    function renderizarListaClientes(clientes) {
        listaClientesModal.innerHTML = '';
        sinResultadosClientes.style.display = clientes.length === 0 ? 'block' : 'none';

        clientes.forEach(cliente => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';

            const numDoc = document.createElement('strong');
            numDoc.textContent = cliente.numdocumento;
            item.appendChild(numDoc);
            item.appendChild(document.createTextNode(
                ` - ${cliente.nombre} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}`
            ));

            item.addEventListener('click', () => {
                seleccionarCliente(cliente);
                modalClientes.modal('hide');
            });
            listaClientesModal.appendChild(item);
        });
    }

    document.getElementById('btn-cambiar-cliente').addEventListener('click', function () {
        modalClientes.modal('show');
    });

    /**
     * Valida cantidad/precio de cada fila de producto y marca los campos inválidos
     * (el form usa novalidate, así que esta validación reemplaza a la nativa del navegador)
     */
    function validarFilasProductos() {
        const filas = document.querySelectorAll('tr.fila-producto');
        let esValido = filas.length > 0;

        filas.forEach(fila => {
            const cantidad = fila.querySelector('.cantidad');
            const precio = fila.querySelector('.precio');

            [cantidad, precio].forEach(input => {
                const invalido = !input.checkValidity();
                input.classList.toggle('is-invalid', invalido);
                input.setAttribute('aria-invalid', invalido ? 'true' : 'false');
                if (invalido) esValido = false;
            });
        });

        return esValido;
    }

    /**
     * Verifica que los pagos registrados cubran exactamente el total de la venta
     */
    function esPagoValido() {
        if (totalVentaActual <= 0 || Math.abs(totalPagado - totalVentaActual) > 0.01) {
            return false;
        }

        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;

        if (tipoPago === 'unico') {
            if (metodoPagoUnico.value === 'Efectivo') {
                return (parseFloat(pagoRecibidoUnico.value) || 0) >= totalVentaActual;
            }
            return true;
        }

        const items = document.querySelectorAll('#contenedor-pagos .metodo-pago-item');
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            const monto = parseFloat(item.querySelector('.monto-pago').value) || 0;
            if (monto <= 0) return false;
            if (item.querySelector('.select-metodo-pago').value === 'Efectivo') {
                const recibido = parseFloat(item.querySelector('.pago-recibido').value) || 0;
                if (recibido < monto) return false;
            }
        }
        return true;
    }

    // Confirmación del formulario con SweetAlert2
    formVenta.addEventListener('submit', function (e) {
        e.preventDefault();

        // Verificar específicamente si se seleccionó un cliente
        if (!document.getElementById('idcliente').value) {
            Swal.fire({
                title: 'Cliente requerido',
                text: 'Por favor, seleccione un cliente antes de continuar',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
            document.getElementById('cliente-feedback').style.setProperty('display', 'block', 'important');
            $('#modal-clientes').modal('show');
            return false;
        }

        // Verificar fecha de venta
        const fechaventa = document.getElementById('fechaventa');
        if (!fechaventa.value) {
            fechaventa.classList.add('is-invalid');
            Swal.fire({
                title: 'Fecha requerida',
                text: 'Ingrese la fecha de la venta',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
            return false;
        }

        // Verificar cantidad/precio de los productos agregados
        if (!validarFilasProductos()) {
            Swal.fire({
                title: 'Revise los productos',
                text: 'Hay cantidades o precios inválidos en el detalle de la venta',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
            return false;
        }

        // Verificar que los pagos cubran el total
        if (!esPagoValido()) {
            Swal.fire({
                title: 'Pago incompleto',
                text: 'El total pagado no coincide con el total de la venta',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
            return false;
        }

        // Obtener el tipo de pago
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;

        // Obtener el valor de la observación
        const observacion = document.getElementById('observacion').value.trim();
        const observacionCorta = observacion.length > 50 ? observacion.substring(0, 50) + '...' : observacion;
        const observacionEscapada = document.createElement('div').appendChild(document.createTextNode(observacionCorta)).parentNode.innerHTML;
        const observacionHtml = observacion ?
            `<p><strong>Observación:</strong> ${observacionEscapada}</p>` : '';

        // Confirmación final
        Swal.fire({
            title: '¿Confirmar Venta?',
            html: `<div class="text-left">
                <p><strong>Total:</strong> ${totalVentaSpan.textContent} Bs.</p>
                <p><strong>Tipo de pago:</strong> ${tipoPago === 'unico' ? 'Pago único' : 'Pago mixto'}</p>
                ${observacionHtml}
                <p>¿Está seguro de registrar esta venta?</p>
              </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    /**
     * Inicializa el modo de pago único
     */
    function initPagoUnico() {
        montoUnico.value = (parseFloat(totalVentaSpan.textContent) || 0).toFixed(2);
        pagoRecibidoUnico.value = montoUnico.value;
    }

    /**
     * Calcula el cambio en pago único
     */
    function calcularCambioUnico() {
        const monto = parseFloat(montoUnico.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoUnico.value) || 0;

        if (monto <= 0) {
            divCambioUnico.style.display = 'none';
            cambioUnico.value = '0.00';
            cambioUnicoHidden.value = '0.00';
            return;
        }

        if (pagoRecibido >= monto) {
            const cambio = pagoRecibido - monto;
            cambioUnico.value = cambio.toFixed(2);
            cambioUnicoHidden.value = cambio.toFixed(2);
            divCambioUnico.style.display = '';
            pagoRecibidoUnico.classList.remove('is-invalid');
            pagoRecibidoUnico.setAttribute('aria-invalid', 'false');
        } else {
            cambioUnico.value = '0.00';
            cambioUnicoHidden.value = '0.00';
            divCambioUnico.style.display = '';
            pagoRecibidoUnico.classList.add('is-invalid');
            pagoRecibidoUnico.setAttribute('aria-invalid', 'true');
        }
    }

    /**
     * Actualiza visualmente el estado del pago único
     */
    function actualizarEstadoPagoUnico() {
        const metodo = metodoPagoUnico.value;
        const monto = parseFloat(montoUnico.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoUnico.value) || 0;
        const tarjetaPago = seccionPagoUnico.closest('.card-body') || seccionPagoUnico;

        tarjetaPago.classList.remove('vent-pago-correcto', 'vent-pago-incorrecto');

        if (!metodo) {
            tarjetaPago.classList.add('vent-pago-incorrecto');
            return;
        }

        if (monto <= 0) {
            return; // carrito vacío: estado neutro
        }

        if (metodo === 'Efectivo' && pagoRecibido < monto) {
            tarjetaPago.classList.add('vent-pago-incorrecto');
            return;
        }

        tarjetaPago.classList.add('vent-pago-correcto');
    }

    /**
     * Agrega un nuevo método de pago al modo mixto
     */
    function agregarMetodoPago() {
        const div = document.createElement('div');
        div.innerHTML = templateMetodoPago;
        const nuevoMetodoPago = div.firstElementChild;

        contenedorPagos.appendChild(nuevoMetodoPago);
        initializeSelect2($(nuevoMetodoPago).find('.select-metodo-pago'));
        renumerarMetodosPago();

        // Configurar eventos
        const selectMetodo = nuevoMetodoPago.querySelector('.select-metodo-pago');
        const inputMonto = nuevoMetodoPago.querySelector('.monto-pago');
        const inputPagoRecibido = nuevoMetodoPago.querySelector('.pago-recibido');
        const btnEliminar = nuevoMetodoPago.querySelector('.btn-eliminar-pago');
        const divPagoRecibido = nuevoMetodoPago.querySelector('.div-pago-recibido');
        const divCambio = nuevoMetodoPago.querySelector('.div-cambio');
        const inputCambioHidden = nuevoMetodoPago.querySelector('.cambio-hidden');

        selectMetodo.addEventListener('change', function () {
            const metodo = this.value;
            if (metodo === 'Efectivo') {
                divPagoRecibido.style.display = '';
                inputPagoRecibido.required = true;
                calcularCambio(nuevoMetodoPago);
            } else {
                divPagoRecibido.style.display = 'none';
                divCambio.style.display = 'none';
                inputPagoRecibido.required = false;
                inputPagoRecibido.value = inputMonto.value;
                inputCambioHidden.value = '0.00';
            }
            actualizarEstadoPago(nuevoMetodoPago);
        });

        inputMonto.addEventListener('input', function () {
            if (selectMetodo.value !== 'Efectivo') {
                inputPagoRecibido.value = this.value;
            } else {
                calcularCambio(nuevoMetodoPago);
            }
            calcularTotalPagado();
            actualizarEstadoPago(nuevoMetodoPago);
        });

        inputPagoRecibido.addEventListener('input', function () {
            if (selectMetodo.value === 'Efectivo') {
                calcularCambio(nuevoMetodoPago);
            }
            actualizarEstadoPago(nuevoMetodoPago);
            calcularTotalPagado();
        });

        btnEliminar.addEventListener('click', function () {
            if (document.querySelectorAll('#contenedor-pagos .metodo-pago-item').length > 1) {
                nuevoMetodoPago.remove();
                renumerarMetodosPago();
                calcularTotalPagado();
            } else {
                Swal.fire('Atención', 'Debe haber al menos un método de pago', 'warning');
            }
        });

        // Vincular cada input con su mensaje de error (aria-describedby)
        inputMonto.id = 'monto-pago-' + Date.now();
        const feedbackMonto = inputMonto.nextElementSibling;
        if (feedbackMonto) {
            feedbackMonto.id = 'feedback-monto-' + inputMonto.id;
            inputMonto.setAttribute('aria-describedby', feedbackMonto.id);
        }
        inputPagoRecibido.id = 'recibido-pago-' + Date.now();
        const feedbackRecibido = nuevoMetodoPago.querySelector('.div-pago-recibido .invalid-feedback');
        if (feedbackRecibido) {
            feedbackRecibido.id = 'feedback-recibido-' + inputPagoRecibido.id;
            inputPagoRecibido.setAttribute('aria-describedby', feedbackRecibido.id);
        }
        inputMonto.setAttribute('aria-invalid', 'false');
        inputPagoRecibido.setAttribute('aria-invalid', 'false');

        // Inicializar valores: sugerir el saldo pendiente
        const totalPendiente = totalVentaActual - totalPagado;
        inputMonto.value = totalPendiente > 0 ? totalPendiente.toFixed(2) : "0.00";
        if (selectMetodo.value !== 'Efectivo') {
            inputPagoRecibido.value = inputMonto.value;
        }

        calcularTotalPagado();
        actualizarEstadoPago(nuevoMetodoPago);
    }

    /**
     * Renumera los títulos "Método de pago #N" de las tarjetas de pago mixto
     */
    function renumerarMetodosPago() {
        document.querySelectorAll('#contenedor-pagos .metodo-pago-item').forEach((item, index) => {
            item.querySelector('.metodo-pago-titulo').textContent = `Método de pago #${index + 1}`;
        });
    }

    /**
     * Calcula el cambio para un método de pago específico
     */
    function calcularCambio(metodoPagoElement) {
        const monto = parseFloat(metodoPagoElement.querySelector('.monto-pago').value) || 0;
        const pagoRecibido = parseFloat(metodoPagoElement.querySelector('.pago-recibido').value) || 0;
        const cambioPago = metodoPagoElement.querySelector('.cambio-pago');
        const cambioHidden = metodoPagoElement.querySelector('.cambio-hidden');
        const divCambio = metodoPagoElement.querySelector('.div-cambio');
        const inputPagoRecibido = metodoPagoElement.querySelector('.pago-recibido');

        if (monto <= 0) {
            divCambio.style.display = 'none';
            cambioPago.value = '0.00';
            cambioHidden.value = '0.00';
            return;
        }

        if (pagoRecibido >= monto) {
            const cambio = pagoRecibido - monto;
            cambioPago.value = cambio.toFixed(2);
            cambioHidden.value = cambio.toFixed(2);
            divCambio.style.display = '';
            inputPagoRecibido.classList.remove('is-invalid');
            inputPagoRecibido.setAttribute('aria-invalid', 'false');
        } else {
            cambioPago.value = '0.00';
            cambioHidden.value = '0.00';
            divCambio.style.display = '';
            inputPagoRecibido.classList.add('is-invalid');
            inputPagoRecibido.setAttribute('aria-invalid', 'true');
        }
    }

    /**
     * Actualiza visualmente el estado de un método de pago
     */
    function actualizarEstadoPago(metodoPagoElement) {
        const selectMetodo = metodoPagoElement.querySelector('.select-metodo-pago');
        const inputMonto = metodoPagoElement.querySelector('.monto-pago');
        const inputPagoRecibido = metodoPagoElement.querySelector('.pago-recibido');

        const monto = parseFloat(inputMonto.value) || 0;
        const pagoRecibido = parseFloat(inputPagoRecibido.value) || 0;

        metodoPagoElement.classList.remove('vent-pago-correcto', 'vent-pago-incorrecto');

        if (monto <= 0) {
            return; // monto sin definir: estado neutro
        }

        if (selectMetodo.value === 'Efectivo' && pagoRecibido < monto) {
            metodoPagoElement.classList.add('vent-pago-incorrecto');
            return;
        }

        metodoPagoElement.classList.add('vent-pago-correcto');
    }

    /**
     * Calcula el total pagado y su diferencia con el total de la venta
     */
    function calcularTotalPagado() {
        let total = 0;

        // Verificar qué tipo de pago está activo
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;

        if (tipoPago === 'unico') {
            total = parseFloat(montoUnico.value) || 0;
        } else {
            document.querySelectorAll('#contenedor-pagos .metodo-pago-item').forEach(item => {
                total += parseFloat(item.querySelector('.monto-pago').value) || 0;
            });
        }

        totalPagado = total;
        totalPagadoDisplay.textContent = total.toFixed(2) + ' Bs.';

        // Validar diferencia con el total de la venta
        const diferencia = totalVentaActual - total;

        if (Math.abs(diferencia) > 0.01) {
            if (diferencia > 0) {
                diferenciaPagoElement.textContent = `Falta por pagar: ${diferencia.toFixed(2)} Bs.`;
                diferenciaPagoElement.classList.add('text-danger');
                diferenciaPagoElement.classList.remove('text-success');
            } else {
                diferenciaPagoElement.textContent = `Sobrepago: ${Math.abs(diferencia).toFixed(2)} Bs.`;
                diferenciaPagoElement.classList.add('text-warning');
                diferenciaPagoElement.classList.remove('text-danger', 'text-success');
            }
        } else {
            diferenciaPagoElement.textContent = 'Pago completo';
            diferenciaPagoElement.classList.remove('text-danger', 'text-warning');
            diferenciaPagoElement.classList.add('text-success');
        }
    }

    /**
     * Agrega un producto al detalle de la venta
     */
    function agregarProductoAlDetalle(producto) {
        // Verificar si el producto ya está en el detalle
        const productosEnDetalle = document.querySelectorAll('.idproducto');
        for (let i = 0; i < productosEnDetalle.length; i++) {
            if (productosEnDetalle[i].value == producto.idproducto) {
                Swal.fire('Producto ya agregado', 'Este producto ya está en el detalle de la venta', 'info');
                return;
            }
        }

        const filaBase = document.getElementById('fila-base');
        const nuevaFila = filaBase.cloneNode(true);

        // Evitar ids duplicados: la fila clonada no debe conservar el id de la plantilla
        nuevaFila.removeAttribute('id');
        nuevaFila.style.display = '';
        nuevaFila.classList.add('fila-producto');

        // Llenar datos del producto
        nuevaFila.querySelector('.idproducto').value = producto.idproducto;
        nuevaFila.querySelector('.nombre-producto').value = producto.nombre;
        nuevaFila.querySelector('.codigo-producto').value = producto.codigo;
        nuevaFila.querySelector('.cantidad').value = 1;
        nuevaFila.querySelector('.cantidad').max = producto.stock;
        nuevaFila.querySelector('.precio').value = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.descuento').value = '0.00';
        nuevaFila.querySelector('.subtotal').textContent = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.stock-disponible').textContent = `Disponible: ${producto.stock}`;

        // Vincular cada input con su mensaje de error (aria-describedby) para que
        // los lectores de pantalla anuncien la invalidez, no solo la clase visual is-invalid
        const cantidadInput = nuevaFila.querySelector('.cantidad');
        const precioInput = nuevaFila.querySelector('.precio');
        const cantidadFeedback = cantidadInput.nextElementSibling;
        const precioInputGroup = precioInput.closest('.input-group');
        const precioFeedback = precioInputGroup ? precioInputGroup.querySelector('.invalid-feedback') : null;

        cantidadFeedback.id = `feedback-cantidad-${producto.idproducto}`;
        cantidadInput.setAttribute('aria-describedby', cantidadFeedback.id);
        cantidadInput.setAttribute('aria-invalid', 'false');

        if (precioFeedback) {
            precioFeedback.id = `feedback-precio-${producto.idproducto}`;
            precioInput.setAttribute('aria-describedby', precioFeedback.id);
        }
        precioInput.setAttribute('aria-invalid', 'false');

        // Agregar eventos
        nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', eliminarFila);
        agregarEventosCalculo(nuevaFila);

        document.querySelector('#tabla-productos tbody').appendChild(nuevaFila);

        actualizarVisibilidadCarritoVacio();

        // Calcular totales
        calcularTotalesVenta();
    }

    /**
     * Elimina una fila de producto
     */
    function eliminarFila() {
        this.closest('tr').remove();
        actualizarVisibilidadCarritoVacio();
        calcularTotalesVenta();
    }

    /**
     * Muestra/oculta el mensaje de carrito vacío según haya o no productos agregados
     */
    function actualizarVisibilidadCarritoVacio() {
        const hayProductos = document.querySelectorAll('tr.fila-producto').length > 0;
        document.getElementById('carrito-vacio').style.display = hayProductos ? 'none' : 'block';
        document.getElementById('tabla-productos').closest('.table-responsive').style.display = hayProductos ? '' : 'none';
    }

    /**
     * Agrega eventos de cálculo a una fila de producto
     */
    function agregarEventosCalculo(fila) {
        const cantidad = fila.querySelector('.cantidad');
        const precio = fila.querySelector('.precio');
        const descuento = fila.querySelector('.descuento');

        cantidad.addEventListener('input', function () {
            const maxStock = parseInt(this.max) || 0;
            if (parseInt(this.value) > maxStock) {
                this.value = maxStock;
                Swal.fire('Stock insuficiente', `Máximo disponible: ${maxStock}`, 'warning');
            }
            const invalido = !this.checkValidity();
            this.classList.toggle('is-invalid', invalido);
            this.setAttribute('aria-invalid', invalido ? 'true' : 'false');
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });

        precio.addEventListener('input', function () {
            const invalido = !this.checkValidity();
            this.classList.toggle('is-invalid', invalido);
            this.setAttribute('aria-invalid', invalido ? 'true' : 'false');
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });

        descuento.addEventListener('input', () => {
            const invalido = !this.checkValidity();
            this.classList.toggle('is-invalid', invalido);
            this.setAttribute('aria-invalid', invalido ? 'true' : 'false');
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });
    }

    /**
     * Calcula el subtotal (neto) de una fila: cantidad * precio - descuento total de la línea
     */
    function calcularSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const descuento = parseFloat(fila.querySelector('.descuento').value) || 0;

        const subtotal = (cantidad * precio) - descuento;
        fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);
    }

    /**
     * Calcula los totales de la venta (subtotal, descuento total y total)
     */
    function calcularTotalesVenta() {
        let subtotal = 0,
            descuentoTotal = 0,
            total = 0;

        document.querySelectorAll('tr.fila-producto').forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio').value) || 0;
            const descuento = parseFloat(fila.querySelector('.descuento').value) || 0;

            subtotal += cantidad * precio;
            descuentoTotal += descuento;
            total += (cantidad * precio) - descuento;
        });

        document.getElementById('subtotal-venta').textContent = subtotal.toFixed(2);
        document.getElementById('descuento-total').textContent = descuentoTotal.toFixed(2);
        document.getElementById('total-venta').textContent = total.toFixed(2);
        document.getElementById('totalventa-hidden').value = total.toFixed(2);

        totalVentaActual = total;

        // Actualizar el campo de monto único
        montoUnico.value = totalVentaActual.toFixed(2);
        if (metodoPagoUnico.value !== 'Efectivo') {
            pagoRecibidoUnico.value = totalVentaActual.toFixed(2);
            cambioUnico.value = '0.00';
            cambioUnicoHidden.value = '0.00';
        } else {
            calcularCambioUnico();
        }

        // Actualizar estado visual del pago único
        actualizarEstadoPagoUnico();

        // Actualizar montos de los métodos de pago en modo mixto
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;
        if (tipoPago === 'mixto') {
            const metodosItems = document.querySelectorAll('#contenedor-pagos .metodo-pago-item');

            // Si solo hay un método de pago, actualizar automáticamente su monto
            if (metodosItems.length === 1) {
                const item = metodosItems[0];
                const selectMetodo = item.querySelector('.select-metodo-pago');
                const inputMonto = item.querySelector('.monto-pago');
                const inputPagoRecibido = item.querySelector('.pago-recibido');

                if (selectMetodo.value) {
                    inputMonto.value = total.toFixed(2);

                    if (selectMetodo.value === 'Efectivo') {
                        if (parseFloat(inputPagoRecibido.value) < total) {
                            inputPagoRecibido.value = total.toFixed(2);
                        }
                        calcularCambio(item);
                    } else {
                        inputPagoRecibido.value = total.toFixed(2);
                    }

                    actualizarEstadoPago(item);
                }
            }
        }

        // Recalcular el total pagado y verificar diferencia
        calcularTotalPagado();
    }

    /**
     * Busca clientes por número de documento, nombre o apellidos
     */
    function buscarClientes(termino) {
        termino = termino.toLowerCase();
        return clientesDisponibles.filter(cliente =>
            (cliente.numdocumento && cliente.numdocumento.toLowerCase().includes(termino)) ||
            (cliente.nombre && cliente.nombre.toLowerCase().includes(termino)) ||
            (cliente.apellidopaterno && cliente.apellidopaterno.toLowerCase().includes(termino)) ||
            (cliente.apellidomaterno && cliente.apellidomaterno.toLowerCase().includes(termino))
        );
    }

    /**
     * Selecciona un cliente y actualiza la ficha lateral
     */
    function seleccionarCliente(cliente) {
        document.getElementById('idcliente').value = cliente.idpersona;
        document.getElementById('nombre-cliente').textContent =
            `${cliente.nombre} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}`.trim();
        document.getElementById('documento-cliente').textContent = cliente.numdocumento;
        document.getElementById('sin-cliente-seleccionado').style.display = 'none';
        document.getElementById('info-cliente-seleccionado').style.display = 'block';
        document.getElementById('cliente-feedback').style.setProperty('display', 'none', 'important');
    }
});
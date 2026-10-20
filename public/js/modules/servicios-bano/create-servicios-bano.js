document.addEventListener('DOMContentLoaded', function () {
    const formServicioBano = document.getElementById('formServicioBano');
    if (!formServicioBano) {
        return;
    }

    // Mostrar aviso de stock bajo si la vista inyectó el dato
    if (formServicioBano.dataset.stockBajo !== undefined) {
        Swal.fire({
            icon: 'warning',
            confirmButtonColor: themeColor('warning-dark'),
            title: 'Stock Bajo',
            text: 'El stock de papel higiénico está bajo (' + formServicioBano.dataset.stockBajo + ' unidades). Considere reponer pronto.',
            showConfirmButton: true,
            confirmButtonText: 'Entendido'
        });
    }

    // Inicializar Select2
    initializeSelect2();

    // Declarar variables para los elementos
    const tipoClienteSelect = document.getElementById('tipo_cliente');
    const divEstancia = document.getElementById('div_estancia');
    const idRecepcionSelect = document.getElementById('idrecepcion');
    const bloquePago = document.getElementById('bloque_pago');
    const banoSelect = document.getElementById('idbano');
    const totalInput = document.getElementById('total');
    const pagoRecibidoInput = document.getElementById('pagorecibido');
    const cambioInput = document.getElementById('cambio');
    const metodoPagoSelect = document.getElementById('metodopago');
    const divCambio = document.getElementById('div_cambio');

    function modalidadActual() {
        const marcada = document.querySelector('input[name="modalidad_cobro"]:checked');
        return marcada ? marcada.value : 'contado';
    }

    // Un huésped elige estancia y modalidad; con cargo al folio no se pide el cobro
    function actualizarVisibilidadCliente() {
        const esHuesped = tipoClienteSelect.value === 'Huesped';
        divEstancia.classList.toggle('bano-oculto', !esHuesped);
        if (!esHuesped) {
            idRecepcionSelect.value = '';
            $(idRecepcionSelect).trigger('change'); // Actualizar Select2
            document.getElementById('modalidad_contado').checked = true;
        }
        actualizarVisibilidadPago();
    }

    function actualizarVisibilidadPago() {
        const folio = tipoClienteSelect.value === 'Huesped' && modalidadActual() === 'folio';
        bloquePago.classList.toggle('bano-oculto', folio);
        // Los campos ocultos no se envían ni bloquean el submit por required
        bloquePago.querySelectorAll('select, input').forEach(function (campo) {
            campo.disabled = folio;
        });
    }

    // Función para mostrar/ocultar el campo de cambio según el método de pago
    function actualizarVisibilidadCambio() {
        if (metodoPagoSelect.value === 'QR') {
            divCambio.classList.add('bano-oculto');
            // Para QR, el pago recibido es igual al total
            pagoRecibidoInput.value = totalInput.value;
            cambioInput.value = '0.00';
        } else {
            divCambio.classList.remove('bano-oculto');
            calcularCambio();
        }
    }

    // Manejar cambio en baño seleccionado - actualizar precio
    function actualizarPrecioBano() {
        const selectedOption = banoSelect.options[banoSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.precio) {
            const precio = parseFloat(selectedOption.dataset.precio);
            totalInput.value = precio.toFixed(2);

            // Actualizar pago recibido también
            pagoRecibidoInput.value = precio.toFixed(2);

            // Calcular cambio
            calcularCambio();
        }
    }

    // Calcular cambio automáticamente
    function calcularCambio() {
        const total = parseFloat(totalInput.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoInput.value) || 0;
        const cambio = Math.max(0, pagoRecibido - total);
        cambioInput.value = cambio.toFixed(2);
    }

    // Validación del formulario
    function validarFormulario(e) {
        if (tipoClienteSelect.value === 'Huesped' && !idRecepcionSelect.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Estancia requerida',
                text: 'Seleccione la estancia del huésped',
                confirmButtonColor: themeColor('primary')
            });
            return false;
        }

        if (bloquePago.classList.contains('bano-oculto')) {
            mostrarCarga('Guardando...', 'Registrando el servicio en el folio.');
            return true;
        }

        const total = parseFloat(totalInput.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoInput.value) || 0;

        if (pagoRecibido < total) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en el pago',
                text: 'El pago recibido debe ser igual o mayor al total',
                confirmButtonColor: themeColor('primary')
            });
            pagoRecibidoInput.focus();
            return false;
        }

        mostrarCarga('Guardando...', 'Registrando el servicio.');
    }

    // Inicializar los elementos con un pequeño retraso para asegurar que Select2 está listo
    setTimeout(function () {
        // Ejecutar actualizarVisibilidadCliente para configurar el estado inicial
        actualizarVisibilidadCliente();

        // Ejecutar actualizarVisibilidadCambio para configurar el estado inicial
        actualizarVisibilidadCambio();

        // Agregar listener para el cambio de tipo de cliente
        tipoClienteSelect.addEventListener('change', actualizarVisibilidadCliente);

        document.querySelectorAll('input[name="modalidad_cobro"]').forEach(function (radio) {
            radio.addEventListener('change', actualizarVisibilidadPago);
        });

        // Agregar listener para el cambio de método de pago
        metodoPagoSelect.addEventListener('change', actualizarVisibilidadCambio);

        // También agregar listener para eventos de Select2
        $(tipoClienteSelect).on('select2:select', actualizarVisibilidadCliente);
        $(metodoPagoSelect).on('select2:select', actualizarVisibilidadCambio);

        // Listener para cambio en baño seleccionado
        banoSelect.addEventListener('change', actualizarPrecioBano);

        // Listener para calcular cambio
        pagoRecibidoInput.addEventListener('input', calcularCambio);

        // Validación del formulario
        const formServicioBano = document.getElementById('formServicioBano');
        if (formServicioBano) {
            formServicioBano.addEventListener('submit', validarFormulario);
        }

        // Cargar precio inicial si hay un baño seleccionado
        if (banoSelect.selectedIndex > 0) {
            actualizarPrecioBano();
        }
    }, 200); // Retraso para asegurar que Select2 está inicializado
});
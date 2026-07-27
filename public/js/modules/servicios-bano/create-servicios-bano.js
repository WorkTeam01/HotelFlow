document.addEventListener('DOMContentLoaded', function () {
    const formServicioBano = document.getElementById('formServicioBano');
    if (!formServicioBano) {
        return;
    }

    // Mostrar aviso de stock bajo si la vista inyectó el dato
    if (formServicioBano.dataset.stockBajo !== undefined) {
        Swal.fire({
            icon: 'warning',
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
    const divCliente = document.getElementById('div_cliente');
    const idClienteSelect = document.getElementById('idcliente');
    const banoSelect = document.getElementById('idbano');
    const totalInput = document.getElementById('total');
    const pagoRecibidoInput = document.getElementById('pagorecibido');
    const cambioInput = document.getElementById('cambio');
    const metodoPagoSelect = document.getElementById('metodopago');
    const divCambio = document.getElementById('div_cambio');

    // Función para mostrar/ocultar el selector de cliente según el tipo
    function actualizarVisibilidadCliente() {
        if (tipoClienteSelect.value === 'Huesped') {
            divCliente.style.display = 'block';
        } else {
            divCliente.style.display = 'none';
            idClienteSelect.value = '';
            $(idClienteSelect).trigger('change'); // Actualizar Select2
        }
    }

    // Función para mostrar/ocultar el campo de cambio según el método de pago
    function actualizarVisibilidadCambio() {
        if (metodoPagoSelect.value === 'QR') {
            divCambio.style.display = 'none';
            // Para QR, el pago recibido es igual al total
            pagoRecibidoInput.value = totalInput.value;
            cambioInput.value = '0.00';
        } else {
            divCambio.style.display = 'block';
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
        const total = parseFloat(totalInput.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoInput.value) || 0;

        if (pagoRecibido < total) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en el pago',
                text: 'El pago recibido debe ser igual o mayor al total',
                confirmButtonColor: '#3085d6'
            });
            pagoRecibidoInput.focus();
            return false;
        }
    }

    // Inicializar los elementos con un pequeño retraso para asegurar que Select2 está listo
    setTimeout(function () {
        // Ejecutar actualizarVisibilidadCliente para configurar el estado inicial
        actualizarVisibilidadCliente();

        // Ejecutar actualizarVisibilidadCambio para configurar el estado inicial
        actualizarVisibilidadCambio();

        // Agregar listener para el cambio de tipo de cliente
        tipoClienteSelect.addEventListener('change', actualizarVisibilidadCliente);

        // Agregar listener para el cambio de método de pago
        metodoPagoSelect.addEventListener('change', actualizarVisibilidadCambio);

        // También agregar listener para eventos de Select2
        $(tipoClienteSelect).on('select2:select', actualizarVisibilidadCliente);
        $(metodoPagoSelect).on('select2:select', actualizarVisibilidadCambio);

        // Listener para cambio en baño seleccionado
        banoSelect.addEventListener('change', actualizarPrecioBano);

        // Listener para calcular cambio
        totalInput.addEventListener('input', calcularCambio);
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
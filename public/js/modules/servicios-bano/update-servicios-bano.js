document.addEventListener('DOMContentLoaded', function () {
    // Inicializar Select2
    initializeSelect2();

    // Declarar variables para los elementos
    const tipoClienteSelect = document.getElementById('tipo_cliente');
    const divCliente = document.getElementById('div_cliente');
    const idClienteSelect = document.getElementById('idcliente');
    const banoSelect = document.getElementById('idbano');
    const totalInput = document.getElementById('total');
    const pagoRecibidoInput = document.getElementById('pagorecibido');
    const cambioDisplay = document.getElementById('cambio_display');

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

    // Manejar cambio en baño seleccionado - actualizar precio
    function actualizarPrecioBano() {
        const selectedOption = banoSelect.options[banoSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.precio) {
            const precio = parseFloat(selectedOption.dataset.precio);

            // Preguntar al usuario si desea actualizar el precio
            Swal.fire({
                title: '¿Actualizar precio?',
                text: `¿Desea actualizar el precio a ${precio.toFixed(2)} Bs.?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'No, mantener'
            }).then((result) => {
                if (result.isConfirmed) {
                    totalInput.value = precio.toFixed(2);
                    calcularCambio();
                }
            });
        }
    }

    // Calcular cambio automáticamente
    function calcularCambio() {
        const total = parseFloat(totalInput.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoInput.value) || 0;
        const cambio = Math.max(0, pagoRecibido - total);

        cambioDisplay.value = cambio.toFixed(2);

        // Validar si el pago es suficiente
        if (pagoRecibido > 0 && pagoRecibido < total) {
            pagoRecibidoInput.setCustomValidity('El pago recibido debe ser mayor o igual al total');
        } else {
            pagoRecibidoInput.setCustomValidity('');
        }
    }

    // Validación del formulario
    function validarFormulario(e) {
        const total = parseFloat(totalInput.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoInput.value) || 0;

        if (total <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en el total',
                text: 'El total debe ser mayor que cero',
                confirmButtonColor: '#3085d6'
            });
            totalInput.focus();
            return false;
        }

        if (pagoRecibido < total) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Pago insuficiente',
                text: 'El pago recibido debe ser mayor o igual al total',
                confirmButtonColor: '#3085d6'
            });
            pagoRecibidoInput.focus();
            return false;
        }

        // Confirmar la actualización
        if (!confirm('¿Está seguro de actualizar este servicio?')) {
            e.preventDefault();
            return false;
        }
    }

    // Inicializar los elementos con un pequeño retraso para asegurar que Select2 está listo
    setTimeout(function () {
        // Ejecutar actualizarVisibilidadCliente para configurar el estado inicial
        actualizarVisibilidadCliente();

        // Agregar listener para el cambio de tipo de cliente
        tipoClienteSelect.addEventListener('change', actualizarVisibilidadCliente);

        // También agregar listener para eventos de Select2
        $(tipoClienteSelect).on('select2:select', actualizarVisibilidadCliente);

        // Listener para cambio en baño seleccionado
        banoSelect.addEventListener('change', actualizarPrecioBano);

        // Listener para calcular cambio
        totalInput.addEventListener('input', calcularCambio);
        pagoRecibidoInput.addEventListener('input', calcularCambio);

        // Validación del formulario
        const formEditarServicio = document.getElementById('formEditarServicio');
        if (formEditarServicio) {
            formEditarServicio.addEventListener('submit', validarFormulario);
        }
    }, 200); // Retraso para asegurar que Select2 está inicializado
});
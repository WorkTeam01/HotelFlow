$(document).ready(function () {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una opción'
    });

    // Mostrar info adicional del cliente seleccionado
    function mostrarInfoCliente() {
        var selectedOption = $('#idcliente option:selected');
        if (selectedOption.val()) {
            var nombre = selectedOption.data('nombre');
            var tipoDoc = selectedOption.data('tipodoc');
            var numDoc = selectedOption.data('numdoc');

            $('#cliente-documento').text(tipoDoc + ': ' + numDoc);
            $('#cliente-nombre').text(nombre);
            $('#cliente-info').show();

            // Actualizar resumen
            $('#resumen-cliente').text(nombre);
        } else {
            $('#cliente-info').hide();
        }
    }

    // Mostrar info adicional de la habitación seleccionada
    function mostrarInfoHabitacion() {
        var selectedOption = $('#idhabitacion option:selected');
        if (selectedOption.val()) {
            var numero = selectedOption.data('numero');
            var tipo = selectedOption.data('tipo');
            var piso = selectedOption.data('piso');

            $('#habitacion-numero').text('N° ' + numero);
            $('#habitacion-tipo').text(tipo);
            $('#habitacion-piso').text('Piso ' + piso);
            $('#habitacion-info').show();

            // Actualizar resumen
            $('#resumen-habitacion').text(numero + ' - ' + tipo);
        } else {
            $('#habitacion-info').hide();
        }
    }

    // Mostrar info adicional de la tarifa seleccionada
    function mostrarInfoTarifa() {
        var selectedOption = $('#idtarifa option:selected');
        if (selectedOption.val()) {
            var precio = parseFloat(selectedOption.data('precio')) || 0;
            var tipoNombre = selectedOption.data('tipo-nombre');
            var tipoEstancia = selectedOption.data('estancia');
            var duracion = parseInt(selectedOption.data('duracion')) || 0;

            var duracionTexto = tipoEstancia === 'horas' ?
                duracion + ' hora(s)' :
                duracion + ' día(s)';

            $('#tarifa-tipo').text(tipoNombre);
            $('#tarifa-duracion').text(duracionTexto);
            $('#tarifa-precio').text('Bs ' + precio.toFixed(2));
            $('#tarifa-info').show();

            // Actualizar monto total
            $('#montototal').val(precio.toFixed(2));

            // Actualizar resumen
            $('#resumen-tarifa').text(tipoNombre + ' - ' + duracionTexto);
            $('#resumen-monto').text('Bs ' + precio.toFixed(2));

            // Actualizar fecha de salida prevista basada en la tarifa
            actualizarFechaSalida();
        } else {
            $('#tarifa-info').hide();
        }
    }

    // Actualizar fecha de salida basada en la tarifa
    function actualizarFechaSalida() {
        var selectedOption = $('#idtarifa option:selected');
        if (selectedOption.val()) {
            var tipoEstancia = selectedOption.data('estancia');
            var duracion = parseInt(selectedOption.data('duracion')) || 1;
            var fechaEntrada = new Date($('#fechaentrada').val());

            if (!isNaN(fechaEntrada.getTime())) {
                var fechaSalida = new Date(fechaEntrada);

                if (tipoEstancia === 'horas') {
                    fechaSalida.setHours(fechaSalida.getHours() + duracion);
                } else { // días
                    fechaSalida.setDate(fechaSalida.getDate() + duracion);
                }

                // Formatear la fecha para el input datetime-local
                var year = fechaSalida.getFullYear();
                var month = String(fechaSalida.getMonth() + 1).padStart(2, '0');
                var day = String(fechaSalida.getDate()).padStart(2, '0');
                var hours = String(fechaSalida.getHours()).padStart(2, '0');
                var minutes = String(fechaSalida.getMinutes()).padStart(2, '0');

                var fechaFormateada = `${year}-${month}-${day}T${hours}:${minutes}`;
                $('#fechasalida_prevista').val(fechaFormateada);
            }
        }
    }

    // Calcular cambio para pagos en efectivo
    function calcularCambio() {
        var montoPagado = parseFloat($('#montopagado').val()) || 0;
        var montoRecibido = parseFloat($('#monto_recibido').val()) || 0;

        // El cambio es la diferencia entre lo recibido y lo pagado (no puede ser negativo)
        var cambio = Math.max(0, montoRecibido - montoPagado);

        // Actualizar el campo de cambio
        $('#cambio').val(cambio.toFixed(2));
    }

    // Actualizar resumen financiero
    function actualizarResumenFinanciero() {
        var montoTotal = parseFloat($('#montototal').val()) || 0;
        var montoPagado = parseFloat($('#montopagado').val()) || 0;
        var saldo = Math.max(0, montoTotal - montoPagado);

        $('#resumen-total').text('Bs ' + montoTotal.toFixed(2));
        $('#resumen-pagado').text('Bs ' + montoPagado.toFixed(2));
        $('#resumen-saldo').text('Bs ' + saldo.toFixed(2));

        // Cambiar clase según si hay saldo pendiente
        if (saldo > 0) {
            $('#resumen-saldo').removeClass('text-success').addClass('text-danger');
        } else {
            $('#resumen-saldo').removeClass('text-danger').addClass('text-success');
        }

        // Actualizar barra de progreso
        var porcentaje = montoTotal > 0 ? (montoPagado / montoTotal) * 100 : 0;
        porcentaje = Math.min(100, porcentaje);

        $('.progress-bar').css('width', porcentaje + '%');
        $('.progress-bar').text(porcentaje.toFixed(1) + '% pagado');

        if (porcentaje >= 100) {
            $('.progress-bar').removeClass('bg-warning').addClass('bg-success');
        } else {
            $('.progress-bar').removeClass('bg-success').addClass('bg-warning');
        }
    }

    // Actualizar las fechas en el resumen
    function actualizarFechasResumen() {
        var fechaEntrada = $('#fechaentrada').val();
        var fechaSalida = $('#fechasalida_prevista').val();

        if (fechaEntrada) {
            var fecha = new Date(fechaEntrada);
            var dia = fecha.getDate().toString().padStart(2, '0');
            var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = fecha.getHours().toString().padStart(2, '0');
            var minutos = fecha.getMinutes().toString().padStart(2, '0');

            $('#resumen-entrada').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
        }

        if (fechaSalida) {
            var fecha = new Date(fechaSalida);
            var dia = fecha.getDate().toString().padStart(2, '0');
            var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = fecha.getHours().toString().padStart(2, '0');
            var minutos = fecha.getMinutes().toString().padStart(2, '0');

            $('#resumen-salida').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
        }
    }

    // Actualizar resumen de estado
    function actualizarResumenEstado() {
        var estado = $('#estado').val();
        var claseEstado = '';
        var textoEstado = '';

        switch (estado) {
            case 'reservado':
                claseEstado = 'badge-info';
                textoEstado = 'Reservado';
                break;
            case 'en_curso':
                claseEstado = 'badge-warning';
                textoEstado = 'En curso (Check-in)';
                break;
        }

        $('#resumen-estado').html(`<span class="badge ${claseEstado} px-3 py-2">${textoEstado}</span>`);
    }

    // Actualizar resumen de observaciones
    function actualizarResumenObservaciones() {
        var observaciones = $('#observaciones').val();

        if (observaciones && observaciones.trim() !== '') {
            $('#resumen-observaciones').text(observaciones);
            $('#resumen-observaciones-container').show();
        } else {
            $('#resumen-observaciones-container').hide();
        }
    }

    // Manejadores de eventos

    // Cliente
    $('#idcliente').on('change', function () {
        mostrarInfoCliente();
    });

    // Habitación
    $('#idhabitacion').on('change', function () {
        mostrarInfoHabitacion();
    });

    // Tarifa
    $('#idtarifa').on('change', function () {
        mostrarInfoTarifa();
        actualizarResumenFinanciero();
    });

    // Fechas
    $('#fechaentrada, #fechasalida_prevista').on('change', function () {
        actualizarFechasResumen();
    });

    // Estado
    $('#estado').on('change', function () {
        actualizarResumenEstado();
    });

    // Observaciones
    $('#observaciones').on('input', function () {
        actualizarResumenObservaciones();
    });

    // Montos
    $('#montototal, #montopagado').on('input', function () {
        actualizarResumenFinanciero();

        // Si cambia el monto pagado, actualizar el monto recibido en efectivo
        if ($(this).attr('id') === 'montopagado' && $('#metodopago').val() === 'Efectivo') {
            var montoPagado = parseFloat($(this).val()) || 0;
            $('#monto_recibido').val(montoPagado.toFixed(2));
            calcularCambio();
        }
    });

    // Actualizar el cambio cuando cambia el monto pagado
    $('#montopagado').on('input', function () {
        // Actualizar el resumen financiero
        actualizarResumenFinanciero();

        // Si es efectivo, recalcular el cambio pero NO modificar el monto recibido
        // para mantener independientes ambos valores
        if ($('#metodopago').val() === 'Efectivo') {
            calcularCambio();
        }
    });

    // Método de pago
    $('#metodopago').on('change', function () {
        if ($(this).val() === 'Efectivo') {
            $('#seccion-cambio').slideDown();

            // Inicializar el monto recibido con el monto que ha pagado el cliente
            // sin modificar el monto pagado
            var montoPagado = parseFloat($('#montopagado').val()) || 0;
            $('#monto_recibido').val(montoPagado.toFixed(2));

            // Calcular el cambio inicial
            calcularCambio();
        } else {
            $('#seccion-cambio').slideUp();
        }
    });

    // Actualizar el cambio cuando cambia el monto recibido
    $('#monto_recibido').on('input', function () {
        if ($('#metodopago').val() === 'Efectivo') {
            calcularCambio();
        }
    });

    // Validación del formulario antes de enviar
    $('#formEditarRecepcion').on('submit', function (e) {
        e.preventDefault();

        // Validar que todos los campos requeridos estén completos
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');

            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Por favor complete todos los campos requeridos correctamente.'
            });

            return false;
        }

        // Validaciones adicionales
        var montoTotal = parseFloat($('#montototal').val());
        var montoPagado = parseFloat($('#montopagado').val());

        if (isNaN(montoTotal) || montoTotal <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El monto total debe ser mayor que cero.'
            });
            return false;
        }

        if (isNaN(montoPagado) || montoPagado < 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El monto pagado no puede ser negativo.'
            });
            return false;
        }

        // Si se seleccionó pago en efectivo, validar el monto recibido
        if ($('#metodopago').val() === 'Efectivo') {
            var montoRecibido = parseFloat($('#monto_recibido').val());
            if (isNaN(montoRecibido) || montoRecibido < montoPagado) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El monto recibido debe ser igual o mayor al monto pagado.'
                });
                return false;
            }

            // Asegurar que el cambio sea correcto antes de enviar
            var cambio = montoRecibido - montoPagado;
            $('#cambio').val(cambio.toFixed(2));
        }

        // Confirmar antes de enviar
        Swal.fire({
            title: '¿Confirmar actualización?',
            html: `
        <div class="text-left">
            <p><strong>Cliente:</strong> ${$('#resumen-cliente').text()}</p>
            <p><strong>Habitación:</strong> ${$('#resumen-habitacion').text()}</p>
            <p><strong>Monto total:</strong> Bs ${montoTotal.toFixed(2)}</p>
            <p><strong>Monto pagado:</strong> Bs ${montoPagado.toFixed(2)}</p>
            ${$('#metodopago').val() === 'Efectivo' ?
                    `<p><strong>Dinero recibido:</strong> Bs ${parseFloat($('#monto_recibido').val()).toFixed(2)}</p>
               <p><strong>Cambio:</strong> Bs ${parseFloat($('#cambio').val()).toFixed(2)}</p>` : ''}
            <p><strong>Estado:</strong> ${$('#estado option:selected').text()}</p>
        </div>
    `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar el formulario
                this.submit();
            }
        });
    });

    // Inicializar todo al cargar la página
    mostrarInfoCliente();
    mostrarInfoHabitacion();
    mostrarInfoTarifa();
    actualizarResumenFinanciero();
    actualizarResumenEstado();
    actualizarResumenObservaciones();

    // Efectos visuales
    $('.card').addClass('card-animation');
});

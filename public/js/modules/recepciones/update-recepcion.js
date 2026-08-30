/**
 * Edición de recepción — SOLO datos de estancia.
 * El dinero se gestiona en el folio (ver show.php) y las transiciones de estado
 * en cambiar_estado.php / checkout_ajax.php: este formulario ya no toca montos,
 * método de pago, cambio ni estado.
 */
$(document).ready(function () {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Seleccione una opción'
    });

    // Al cambiar la tarifa se recalcula la fecha de salida prevista.
    function actualizarFechaSalida() {
        var opt = $('#idtarifa option:selected');
        if (!opt.val()) return;
        var tipoEstancia = opt.data('estancia');
        var duracion = parseInt(opt.data('duracion')) || 1;
        var fechaEntrada = new Date($('#fechaentrada').val());
        if (isNaN(fechaEntrada.getTime())) return;

        var fechaSalida = new Date(fechaEntrada);
        if (tipoEstancia === 'horas') {
            fechaSalida.setHours(fechaSalida.getHours() + duracion);
        } else {
            fechaSalida.setDate(fechaSalida.getDate() + duracion);
        }
        var p = function (n) { return String(n).padStart(2, '0'); };
        $('#fechasalida_prevista').val(
            fechaSalida.getFullYear() + '-' + p(fechaSalida.getMonth() + 1) + '-' + p(fechaSalida.getDate()) +
            'T' + p(fechaSalida.getHours()) + ':' + p(fechaSalida.getMinutes())
        );
    }

    $('#idtarifa').on('change', actualizarFechaSalida);

    $('#formEditarRecepcion').on('submit', function (e) {
        e.preventDefault();
        var form = this;

        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            Swal.fire({ icon: 'error', title: 'Error de validación', text: 'Por favor complete todos los campos requeridos.' });
            return false;
        }

        var entrada = new Date($('#fechaentrada').val());
        var salida = new Date($('#fechasalida_prevista').val());
        if (!isNaN(entrada.getTime()) && !isNaN(salida.getTime()) && salida <= entrada) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'La fecha de salida prevista debe ser posterior a la de entrada.' });
            return false;
        }

        Swal.fire({
            title: '¿Confirmar actualización?',
            html: '<div class="text-left">' +
                '<p><strong>Cliente:</strong> ' + ($('#idcliente option:selected').data('nombre') || '—') + '</p>' +
                '<p><strong>Tarifa:</strong> ' + ($('#idtarifa option:selected').data('tipo-nombre') || '—') + '</p>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

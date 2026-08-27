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

    function mostrarInfoCliente() {
        var opt = $('#idcliente option:selected');
        if (opt.val()) {
            var nombre = opt.data('nombre');
            $('#cliente-documento').text(opt.data('tipodoc') + ': ' + opt.data('numdoc'));
            $('#cliente-nombre').text(nombre);
            $('#cliente-info').show();
            $('#resumen-cliente').text(nombre);
        } else {
            $('#cliente-info').hide();
        }
    }

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

    function mostrarInfoTarifa() {
        var opt = $('#idtarifa option:selected');
        if (opt.val()) {
            var precio = parseFloat(opt.data('precio')) || 0;
            var tipoNombre = opt.data('tipo-nombre');
            var tipoEstancia = opt.data('estancia');
            var duracion = parseInt(opt.data('duracion')) || 0;
            var duracionTexto = (tipoEstancia === 'horas' ? duracion + ' hora(s)' : duracion + ' día(s)');

            $('#tarifa-tipo').text(tipoNombre);
            $('#tarifa-duracion').text(duracionTexto);
            $('#tarifa-precio').text('Bs ' + precio.toFixed(2));
            $('#tarifa-info').show();
            $('#resumen-tarifa').text(tipoNombre + ' - ' + duracionTexto);
            $('#resumen-monto').text('Bs ' + precio.toFixed(2));
            actualizarFechaSalida();
        } else {
            $('#tarifa-info').hide();
        }
    }

    function actualizarFechasResumen() {
        var fmt = function (val, target) {
            if (!val) return;
            var f = new Date(val);
            var p = function (n) { return String(n).padStart(2, '0'); };
            $(target).text(p(f.getDate()) + '/' + p(f.getMonth() + 1) + '/' + f.getFullYear() + ' ' + p(f.getHours()) + ':' + p(f.getMinutes()));
        };
        fmt($('#fechaentrada').val(), '#resumen-entrada');
        fmt($('#fechasalida_prevista').val(), '#resumen-salida');
    }

    function actualizarResumenObservaciones() {
        var obs = $('#observaciones').val();
        if (obs && obs.trim() !== '') {
            $('#resumen-observaciones').text(obs);
            $('#resumen-observaciones-container').show();
        } else {
            $('#resumen-observaciones-container').hide();
        }
    }

    $('#idcliente').on('change', mostrarInfoCliente);
    $('#idtarifa').on('change', mostrarInfoTarifa);
    $('#fechaentrada, #fechasalida_prevista').on('change', actualizarFechasResumen);
    $('#observaciones').on('input', actualizarResumenObservaciones);

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
                '<p><strong>Cliente:</strong> ' + $('#resumen-cliente').text() + '</p>' +
                '<p><strong>Tarifa:</strong> ' + $('#resumen-tarifa').text() + '</p>' +
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

    mostrarInfoCliente();
    mostrarInfoTarifa();
    actualizarResumenObservaciones();
});

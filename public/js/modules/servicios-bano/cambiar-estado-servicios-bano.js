/**
 * Flujo único de cambio de estado de un servicio de baño.
 *
 * Compartido por el listado (index) y el detalle (show): handler delegado sobre
 * .cambiar-estado-servicio (data-id / data-estado) → confirmación SweetAlert con motivo
 * obligatorio → POST con CSRF → recarga de la vista. El servidor vuelve a validar la transición.
 */
(function (window, $) {
    'use strict';

    var META = {
        finalizado: {
            titulo: '¿Finalizar este servicio?',
            texto: 'Se registrará la duración y el baño quedará libre.',
            icono: 'question',
            color: 'success',
            boton: 'Sí, finalizar',
            pideMotivo: false
        },
        cancelado: {
            titulo: '¿Cancelar este servicio?',
            texto: 'Se revertirá el cobro en el folio y no se podrá reactivar.',
            icono: 'warning',
            color: 'danger',
            boton: 'Sí, cancelar',
            pideMotivo: true
        }
    };

    function cambiarEstadoServicio(opts) {
        var meta = META[opts.estado];
        if (!meta) {
            return;
        }

        var dialogo = {
            title: meta.titulo,
            text: meta.texto,
            icon: meta.icono,
            showCancelButton: true,
            confirmButtonColor: themeColor(meta.color),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: meta.boton,
            cancelButtonText: 'No, volver',
            allowOutsideClick: false
        };

        if (meta.pideMotivo) {
            dialogo.input = 'text';
            dialogo.inputLabel = 'Motivo';
            dialogo.inputAttributes = { maxlength: 255, autocomplete: 'off' };
            dialogo.inputValidator = function (valor) {
                return valor && valor.trim() !== '' ? null : 'Indique el motivo de la cancelación';
            };
        }

        Swal.fire(dialogo).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            mostrarCarga('Procesando...', 'Espere un momento mientras se actualiza el estado.');

            $.ajax({
                url: BASE_URL + 'controllers/servicios-bano/cambiar_estado_servicio.php',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: { id: opts.id, nuevo_estado: opts.estado, motivo: meta.pideMotivo ? result.value : '', csrf_token: CSRF_TOKEN },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Estado actualizado',
                            text: response.message,
                            timer: 1600,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', confirmButtonColor: themeColor('danger'), title: 'Error', text: response.message });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        confirmButtonColor: themeColor('danger'),
                        title: 'Error',
                        text: 'Ocurrió un error en la comunicación con el servidor'
                    });
                }
            });
        });
    }

    $(document).on('click', '.cambiar-estado-servicio', function (e) {
        e.preventDefault();

        var $btn = $(this);
        cambiarEstadoServicio({ id: $btn.data('id'), estado: $btn.data('estado') });
    });
})(window, jQuery);

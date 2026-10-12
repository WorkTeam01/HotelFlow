/**
 * Flujo único de cambio de estado de un equipaje.
 *
 * Compartido por el listado (index) y el detalle (show): handler delegado sobre
 * .cambiar-estado (data-id / data-estado) → confirmación SweetAlert → POST con
 * CSRF → recarga de la vista. El nuevo estado se valida de nuevo en el servidor.
 */
(function (window, $) {
    'use strict';

    var META = {
        retirado: {
            titulo: '¿Marcar como Retirado?',
            texto: 'Esta acción registrará que el equipaje ha sido entregado al cliente y no se podrá deshacer.',
            icono: 'question',
            color: 'success'
        },
        perdido: {
            titulo: '¿Marcar como Perdido?',
            texto: 'Esta acción registrará que el equipaje ha sido perdido o no puede ser localizado.',
            icono: 'warning',
            color: 'danger'
        },
        'dañado': {
            titulo: '¿Marcar como Dañado?',
            texto: 'Esta acción registrará que el equipaje ha sufrido daños.',
            icono: 'warning',
            color: 'dark'
        }
    };

    function cambiarEstadoEquipaje(opts) {
        var meta = META[opts.estado] || {
            titulo: '¿Cambiar estado?',
            texto: 'Esta acción cambiará el estado del equipaje.',
            icono: 'question',
            color: 'primary'
        };

        Swal.fire({
            title: meta.titulo,
            text: meta.texto,
            icon: meta.icono,
            showCancelButton: true,
            confirmButtonColor: themeColor(meta.color),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            mostrarCarga('Procesando...', 'Espere un momento mientras se actualiza el estado.');

            $.ajax({
                url: BASE_URL + 'controllers/almacenamiento-equipaje/cambiar_estado.php',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: { id: opts.id, nuevo_estado: opts.estado, csrf_token: CSRF_TOKEN },
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

    $(document).on('click', '.cambiar-estado', function (e) {
        e.preventDefault();

        var $btn = $(this);
        cambiarEstadoEquipaje({ id: $btn.data('id'), estado: $btn.data('estado') });
    });
})(window, jQuery);

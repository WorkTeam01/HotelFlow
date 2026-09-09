/**
 * Flujo único de cambio de estado de una habitación.
 *
 * Usado tanto por el listado (index) como por el detalle (show) para que la
 * confirmación, la llamada AJAX y el manejo de errores sean idénticos en ambos.
 * El llamador pasa un callback onSuccess para refrescar su propia vista.
 */
(function (window, $) {
    'use strict';

    var META = {
        disponible: { label: 'Disponible', color: '#28a745', badge: 'badge-success' },
        ocupada: { label: 'Ocupada', color: '#ffc107', badge: 'badge-warning' },
        limpieza: { label: 'Por limpiar', color: '#6c757d', badge: 'badge-secondary' },
        mantenimiento: { label: 'Mantenimiento', color: '#dc3545', badge: 'badge-danger' }
    };

    function metaEstado(estado) {
        return META[estado] || { label: estado, color: '#6c757d', badge: 'badge-secondary' };
    }

    /**
     * @param {{id:(number|string), estado:string, estadoActual:string, onSuccess?:Function}} opts
     */
    function cambiarEstadoHabitacion(opts) {
        var destino = metaEstado(opts.estado);
        var origen = metaEstado(opts.estadoActual);

        Swal.fire({
            title: '¿Cambiar a "' + destino.label + '"?',
            text: 'La habitación pasará de "' + origen.label + '" a "' + destino.label + '".',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: destino.color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Procesando...',
                text: 'Espere un momento mientras se actualiza el estado.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: BASE_URL + 'controllers/habitaciones/cambiar_estado.php',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: { id: opts.id, estado: opts.estado, csrf_token: CSRF_TOKEN },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Estado actualizado',
                            text: response.message,
                            timer: 1600,
                            showConfirmButton: false
                        }).then(function () {
                            if (typeof opts.onSuccess === 'function') {
                                opts.onSuccess(response);
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la comunicación con el servidor'
                    });
                }
            });
        });
    }

    window.HabitacionEstado = { meta: metaEstado, cambiar: cambiarEstadoHabitacion };

    // Enlace único para todos los botones .cambiar-estado (listado y detalle).
    // Tras confirmar, se recarga la vista para reflejar el nuevo estado, las
    // acciones disponibles y los contadores sin reconstruir el DOM a mano.
    $(document).on('click', '.cambiar-estado', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        cambiarEstadoHabitacion({
            id: $btn.data('id'),
            estado: $btn.data('estado'),
            estadoActual: $btn.data('estado-actual'),
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
})(window, jQuery);

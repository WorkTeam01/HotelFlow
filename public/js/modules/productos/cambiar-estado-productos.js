/**
 * Flujo único de activación / desactivación de un producto.
 *
 * Compartido por el listado (index), el detalle (show) y las acciones rápidas
 * (update) para que la confirmación, la llamada AJAX y el manejo de errores
 * sean idénticos en las tres vistas. El llamador puede pasar un callback
 * onSuccess; por defecto se recarga la vista para reflejar el nuevo estado.
 */
(function (window, $) {
    'use strict';

    var META = {
        activo: { label: 'Activo', color: themeColor('success'), badge: 'badge-success' },
        inactivo: { label: 'Inactivo', color: themeColor('secondary'), badge: 'badge-secondary' }
    };

    function metaEstado(estado) {
        var clave = parseInt(estado, 10) === 1 ? 'activo' : 'inactivo';
        return META[clave];
    }

    /**
     * @param {{id:(number|string), estadoActual:*, nombre:string, onSuccess?:Function}} opts
     */
    function cambiarEstadoProducto(opts) {
        var activo = parseInt(opts.estadoActual, 10) === 1;

        Swal.fire({
            title: activo ?
                '¿Desactivar producto "' + opts.nombre + '"?' :
                '¿Activar producto "' + opts.nombre + '"?',
            text: activo ?
                'El producto no estará disponible para venta hasta que sea activado nuevamente.' :
                'El producto estará disponible nuevamente para venta.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: activo ? themeColor('danger') : themeColor('success'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: activo ? 'Sí, desactivar' : 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            mostrarCarga('Procesando...', 'Espere un momento mientras se actualiza el estado.');

            $.ajax({
                url: BASE_URL + 'controllers/productos/desactivar_producto.php',
                type: 'POST',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: { id: opts.id, csrf_token: CSRF_TOKEN },
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

    window.ProductoEstado = { meta: metaEstado, cambiar: cambiarEstadoProducto };

    // Enlace único para todos los disparadores (listado, detalle y update).
    $(document).on('click', '.cambiar-estado-producto', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        cambiarEstadoProducto({
            id: $btn.data('id'),
            estadoActual: $btn.data('estado'),
            nombre: $btn.data('nombre'),
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
})(window, jQuery);

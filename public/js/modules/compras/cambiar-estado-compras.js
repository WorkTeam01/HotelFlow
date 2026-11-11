/**
 * Flujo único de completar / cancelar una compra.
 *
 * Compartido por el listado (index) y el detalle (show): confirmación con el texto de
 * cada transición, POST con CSRF en el cuerpo y recarga al terminar. El handler es
 * delegado para que funcione en las filas de otras páginas del DataTable.
 */
(function (window, $) {
    'use strict';

    // El efecto sobre el inventario depende de la transición: solo completar suma
    // stock, y solo cancelar una compra ya completada lo resta.
    var META = {
        'pendiente>completada': {
            titulo: '¿Completar la compra?',
            texto: 'La mercadería se registra como recibida y el stock de los productos se sumará al inventario.',
            boton: 'Sí, completar',
            tono: 'success'
        },
        'pendiente>cancelada': {
            titulo: '¿Cancelar la compra?',
            texto: 'La compra quedará cancelada. No afecta el inventario porque aún no se había recibido.',
            boton: 'Sí, cancelar',
            tono: 'danger'
        },
        'completada>cancelada': {
            titulo: '¿Cancelar la compra completada?',
            texto: 'Se restará del inventario lo que ingresó con esta compra. Si ya se vendieron unidades, no se podrá cancelar.',
            boton: 'Sí, cancelar',
            tono: 'danger'
        }
    };

    function cambiarEstadoCompra(opts) {
        var meta = META[opts.estadoActual + '>' + opts.estado];
        if (!meta) {
            return;
        }

        Swal.fire({
            title: meta.titulo,
            html: '<p class="mb-1"><strong>' + escapeHtml(opts.codigo) + '</strong></p><p class="mb-0">' + escapeHtml(meta.texto) + '</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: themeColor(meta.tono),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: meta.boton,
            cancelButtonText: 'Volver',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            mostrarCarga('Procesando...', 'Actualizando la compra');

            $.ajax({
                url: BASE_URL + 'controllers/compras/cambiar_estado_compra.php',
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
                        Swal.fire({ icon: 'error', confirmButtonColor: themeColor('danger'), title: 'No se pudo actualizar', text: response.message });
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

    $(document).on('click', '.cambiar-estado-compra', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        $btn.tooltip('hide');
        cambiarEstadoCompra({
            id: $btn.data('id'),
            estado: String($btn.data('estado')),
            estadoActual: String($btn.data('estado-actual')),
            codigo: String($btn.data('codigo'))
        });
    });
})(window, jQuery);

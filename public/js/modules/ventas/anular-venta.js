/**
 * Handler unificado de anulación de ventas.
 * Compartido por index.php (listado) y show.php (detalle).
 * Delegado a nivel documento para sobrevivir a los redraws de DataTable.
 * El endpoint recibe solo id + csrf_token (sin parámetro accion).
 */
document.addEventListener('click', function (event) {
    var botonAnular = event.target.closest('.btn-anular-venta');
    if (!botonAnular) {
        return;
    }

    var ventaId = botonAnular.dataset.id;
    var tituloVenta = botonAnular.dataset.titulo;

    // Ocultar cualquier tooltip activo antes del diálogo
    $('[data-toggle="tooltip"]').tooltip('hide');

    Swal.fire({
        title: '¿Anular venta ' + tituloVenta + '?',
        text: 'La venta será anulada y el stock de productos será revertido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then(function (result) {
        if (result.isConfirmed) {
            window.location.href = BASE_URL + 'controllers/ventas/anular_venta.php?id=' + ventaId + '&csrf_token=' + CSRF_TOKEN;
        }
    });
});

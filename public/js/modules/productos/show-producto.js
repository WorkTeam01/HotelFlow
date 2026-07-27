$(document).ready(function () {
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastProductDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del producto con SweetAlert2
    $('#btnCambiarEstado').on('click', function () {
        const productoId = this.dataset.id;
        const estadoActual = parseInt(this.dataset.estado, 10);
        const nombreProducto = this.dataset.nombre;

        const tituloAlerta = estadoActual == 1 ?
            `¿Desactivar producto "${nombreProducto}"?` :
            `¿Activar producto "${nombreProducto}"?`;

        const textoAlerta = estadoActual == 1 ?
            'El producto no estará disponible para venta hasta que sea activado nuevamente.' :
            'El producto estará disponible nuevamente para venta.';

        const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
        const cancelButtonText = 'Cancelar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a la acción de cambio de estado
                window.location.href = `${BASE_URL}controllers/productos/desactivar_producto.php?id=${productoId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });
});

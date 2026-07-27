document.addEventListener('DOMContentLoaded', function () {
    // Configuración del botón de anulación
    const btnAnular = document.querySelector('.btn-anular-venta');

    if (btnAnular) {
        btnAnular.addEventListener('click', function () {
            const ventaId = this.dataset.id;
            const nombreVenta = this.dataset.nombre;

            Swal.fire({
                title: `¿Anular ${nombreVenta}?`,
                text: "Esta acción restaurará el stock de los productos y no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${BASE_URL}controllers/ventas/anular_venta.php?id=${ventaId}&accion=anular&csrf_token=${CSRF_TOKEN}`;
                }
            });
        });
    }
});

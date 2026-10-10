$(document).ready(function () {
    // Inicializar DataTable
    initDataTable('#tablaVentas', {
        titulo: 'Ventas del Sistema',
        archivo: 'ventas_sistema',
        subtitulo: 'Ventas registradas',
        descripcionExcel: 'Registro de ventas del sistema',
        entidad: 'Ventas',
        columnas: [0, 1, 2, 3, 4, 5, 6],
        columnasTexto: [5, 6],
        personalizarPdf: function (doc) {
            doc.content[3].table.body.forEach(function (row) {
                if (row[4]) { // Total
                    row[4].alignment = 'right';
                }
            });
        },
        menuLongitud: [3, 5, 10, 25, 50],
        dt: {
            drawCallback: function () {
                // DataTable recrea las filas al paginar/buscar; re-vincular tooltips
                // sin destroy previo (dispose en elementos sin instancia corrompe Bootstrap 4)
                $('[data-toggle="tooltip"]').each(function () {
                    if (!$(this).data('bs.tooltip')) {
                        $(this).tooltip({ placement: 'top', trigger: 'hover focus', container: 'body' });
                    }
                });
            }
        }
    });
});

// El recibo de una venta anulada queda deshabilitado (aria-disabled): no navegar.
// La anulación de ventas vive en anular-venta.js (compartido con show.php).
document.addEventListener('click', function (event) {
    var enlaceImpresion = event.target.closest('a.disabled[aria-disabled="true"]');
    if (enlaceImpresion) {
        event.preventDefault();
    }
});
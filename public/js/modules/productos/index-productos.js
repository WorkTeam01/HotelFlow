$(document).ready(function () {
    // Inicializar DataTable para productos
    initDataTable('#tablaProductos', {
        titulo: 'Inventario de Productos',
        archivo: 'inventario_productos',
        subtitulo: 'Productos registrados en el sistema',
        descripcionExcel: 'Registro de productos en inventario',
        entidad: 'productos',
        columnas: [0, 1, 2, 3, 5, 6, 7],
        columnasTexto: [7],
        formatoCelda: function (data, row, column) {
            // Precio de venta: solo el número
            if (column === 5) {
                return data.replace(/[^\d.-]/g, '');
            }
        },
        personalizarPdf: function (doc) {
            doc.content[3].table.body.forEach(function (row) {
                // Precio y stock (índices 4 y 5 del PDF: Imagen no se exporta)
                if (row[4]) row[4].text = row[4].text.replace(/[^\d.-]/g, '');
                if (row[5]) row[5].text = row[5].text.replace(/[^\d.-]/g, '');
            });
        },
        personalizarImpresion: function (win) {
            $(win.document.body).find('table')
                .addClass('table-striped')
                .css('font-size', '12px');

            $(win.document.body).prepend(
                '<h3>Inventario de Productos - ' + APP_NAME + '</h3>' +
                '<p>Reporte generado el ' + new Date().toLocaleDateString('es-BO') + '</p>'
            );
        },
        longitudPagina: 5,
        menuLongitud: [3, 5, 10, 25, 50],
        dt: {
            // En viewports estrechos se colapsan primero Código/Categoría/Precio/Imagen;
            // Nombre, Acciones, Stock y Estado se mantienen visibles el mayor tiempo posible.
            columnDefs: [
                { "responsivePriority": 1, "targets": 2 },
                { "responsivePriority": 2, "targets": 8 },
                { "responsivePriority": 3, "targets": [6, 7] },
                { "responsivePriority": 10, "targets": [0, 1, 3, 4, 5] },
                { "orderable": false, "targets": [4, 8] }
            ]
        }
    });
});

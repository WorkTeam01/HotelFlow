$(document).ready(function () {
    // Columnas: 0 Código · 1 Fecha · 2 Usuario · 3 Total · 4 Estado · 5 Acciones
    initDataTable('#tablaCompras', {
        titulo: 'Registro de Compras',
        archivo: 'compras_registro',
        subtitulo: 'Historial de compras del sistema',
        descripcionExcel: 'Historial de compras del sistema',
        entidad: 'compras',
        columnas: [0, 1, 2, 3, 4],
        columnasTexto: [4],
        formatoCelda: function (data, row, column) {
            // Total: sin separador de miles para que Excel lo lea como número
            if (column === 3) {
                return String(data).replace(/,/g, '');
            }
        },
        personalizarPdf: function (doc) {
            doc.content[3].table.body.forEach(function (fila, i) {
                if (i > 0 && fila[3]) { // cabecera excluida
                    fila[3].alignment = 'right';
                }
            });
        },
        personalizarImpresion: function (win) {
            $(win.document.body).find('table')
                .addClass('table-striped')
                .css('font-size', '12px');

            $(win.document.body).prepend(
                '<h3 class="text-center">Registro de Compras</h3>' +
                '<p class="text-center">' + escapeHtml(APP_NAME) + '</p>' +
                '<hr>'
            );
        },
        menuLongitud: [5, 10, 25, 50, 100],
        textoColumnas: 'Columnas visibles',
        dt: {
            // Más recientes primero; la fecha ordena por data-order (Y-m-d), no por d/m/Y
            order: [[1, 'desc']],
            language: {
                sZeroRecords: 'No se encontraron compras',
                sEmptyTable: 'No hay compras registradas',
                sInfo: 'Mostrando compras del _START_ al _END_ de un total de _TOTAL_',
                sInfoEmpty: 'Mostrando compras del 0 al 0 de un total de 0'
            },
            columnDefs: [
                // A 390px Acciones y Estado deben quedar visibles sin abrir el "+"
                { responsivePriority: 1, targets: 5 },
                { responsivePriority: 2, targets: 0 },
                { responsivePriority: 3, targets: 4 },
                { type: 'num-fmt', targets: 3 },
                { orderable: false, targets: 5 }
            ],
            drawCallback: function () {
                // DataTable recrea las filas al paginar/buscar; re-vincular tooltips sin
                // destroy previo (dispose en elementos sin instancia corrompe Bootstrap 4)
                $('[data-toggle="tooltip"]').each(function () {
                    if (!$(this).data('bs.tooltip')) {
                        $(this).tooltip({ placement: 'top', trigger: 'hover focus', container: 'body' });
                    }
                });
            }
        }
    });
});

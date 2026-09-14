$(document).ready(function () {
    // Inicializar tooltips (se re-inicializan en cada redraw de la tabla)
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar DataTable
    $("#tablaVentas").DataTable({
        "responsive": true,
        "autoWidth": false,
        "drawCallback": function () {
            // DataTable recrea las filas al paginar/buscar; re-vincular tooltips
            $('[data-toggle="tooltip"]').tooltip();
        },
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                extend: 'pdf',
                title: 'Ventas del Sistema' + ' - ' + APP_NAME,
                filename: 'ventas_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'VENTAS DEL SISTEMA' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar titulo
                    let tituloTexto = 'Ventas registradas';

                    doc.content.splice(1, 0, {
                        text: tituloTexto,
                        style: {
                            fontSize: 11,
                            alignment: 'center',
                            italic: true,
                            margin: [0, 0, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: {
                            fontSize: 9,
                            alignment: 'right',
                            margin: [0, 0, 0, 10]
                        }
                    });

                    // Formatear columnas numéricas
                    doc.content[3].table.body.forEach(function (row) {
                        if (row[4]) { // Columna de Total
                            row[4].alignment = 'right';
                        }
                    });

                    // Pie de página
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [{
                                text: 'Sistema de Gestión' + ' - ' + APP_NAME,
                                alignment: 'left',
                                fontSize: 8
                            },
                            {
                                text: 'Página ' + currentPage + ' de ' + pageCount,
                                alignment: 'center',
                                fontSize: 8
                            },
                            {
                                text: 'Confidencial',
                                alignment: 'right',
                                fontSize: 8
                            }
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: 'Ventas del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Registro de ventas del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 5 || column === 6) { // Método Pago / Estado: solo texto visible
                                return $(node).find('span').text().trim();
                            }
                            return data;
                        }
                    }
                }
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Ventas del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 5 || column === 6) { // Método Pago / Estado: solo texto visible
                                return $(node).find('span').text().trim();
                            }
                            return data;
                        }
                    }
                },
                customize: function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                }
            }]
        },
        {
            extend: 'colvis',
            text: 'Visualización de columnas'
        }
        ],
        "pageLength": 10,
        lengthMenu: [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Ventas",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Ventas",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Ventas)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).buttons().container().appendTo('#tablaVentas_wrapper .col-md-6:eq(0)');
});

document.addEventListener('click', function (event) {
    // El recibo de una venta anulada queda deshabilitado (aria-disabled): no navegar.
    const enlaceImpresion = event.target.closest('a.disabled[aria-disabled="true"]');
    if (enlaceImpresion) {
        event.preventDefault();
        return;
    }

    // Confirmación de anulación: delegado para sobrevivir a los redraws de DataTable.
    const botonAnular = event.target.closest('.btn-anular-venta');
    if (!botonAnular) {
        return;
    }

    const ventaId = botonAnular.dataset.id;
    const tituloVenta = botonAnular.dataset.titulo;

    // Ocultar cualquier tooltip activo antes del diálogo
    $('[data-toggle="tooltip"]').tooltip('hide');

    Swal.fire({
        title: `¿Anular venta ${tituloVenta}?`,
        text: 'La venta será anulada y el stock de productos será revertido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `${BASE_URL}controllers/ventas/anular_venta.php?id=${ventaId}&csrf_token=${CSRF_TOKEN}`;
        }
    });
});
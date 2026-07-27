$(document).ready(function () {
    // Inicializar DataTable para tarifas
    $("#tablaTarifas").DataTable({
        "responsive": true,
        "autoWidth": false,
        "buttons": [{
            "extend": 'collection',
            "text": 'Reportes',
            "orientation": 'landscape',
            "buttons": [{
                "text": 'Copiar',
                "extend": 'copy',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5]
                }
            }, {
                "extend": 'pdf',
                "title": 'Listado de Tarifas' + ' - ' + APP_NAME,
                "filename": 'tarifas_' + new Date().toISOString().slice(0, 10),
                "pageSize": 'LETTER',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5]
                },
                "customize": function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        "text": 'LISTADO DE TARIFAS' + ' - ' + APP_NAME.toUpperCase(),
                        "style": {
                            "fontSize": 16,
                            "alignment": 'center',
                            "bold": true,
                            "margin": [0, 10, 0, 10]
                        }
                    });

                    // Agregar subtítulo
                    doc.content.splice(1, 0, {
                        "text": 'Tarifas registradas en el sistema',
                        "style": {
                            "fontSize": 11,
                            "alignment": 'center',
                            "italic": true,
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(2, 0, {
                        "text": 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        "style": {
                            "fontSize": 9,
                            "alignment": 'right',
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Formatear columna de precio
                    doc.content[3].table.body.forEach(function(row) {
                        if (row[4]) row[4].text = row[4].text.replace(/[^\d.-]/g, '');
                    });

                    // Pie de página
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            "columns": [{
                                "text": 'Sistema de Gestión' + ' - ' + APP_NAME,
                                "alignment": 'left',
                                "fontSize": 8
                            },
                            {
                                "text": 'Página ' + currentPage + ' de ' + pageCount,
                                "alignment": 'center',
                                "fontSize": 8
                            },
                            {
                                "text": 'Confidencial',
                                "alignment": 'right',
                                "fontSize": 8
                            }
                            ],
                            "margin": [40, 0]
                        };
                    };
                }
            }, {
                "extend": 'excel',
                "title": 'Listado de Tarifas' + ' - ' + APP_NAME,
                "messageTop": 'Registro de tarifas del sistema',
                "messageBottom": 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear columna de estado
                            if (column === 6) {
                                return $(node).find('span').text();
                            }
                            // Formatear columna de precio
                            if (column === 4) {
                                return data.replace(/[^\d.-]/g, '');
                            }
                            return data;
                        }
                    }
                }
            }, {
                "extend": 'csv',
                "text": 'CSV',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5]
                }
            }, {
                "extend": 'print',
                "text": 'Imprimir',
                "title": 'Listado de Tarifas' + ' - ' + APP_NAME,
                "messageTop": 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5]
                },
                "customize": function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                    
                    // Agregar título personalizado
                    $(win.document.body).prepend(
                        '<h3>Listado de Tarifas - ' + APP_NAME + '</h3>' +
                        '<p>Reporte generado el ' + new Date().toLocaleDateString('es-BO') + '</p>'
                    );
                }
            }]
        },
        {
            "extend": 'colvis',
            "text": 'Visualización de columnas'
        }
        ],
        "pageLength": 5,
        "lengthMenu": [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ tarifas",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 tarifas",
            "sInfoFiltered": "(filtrado de un total de _MAX_ tarifas)",
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
        },
        "columnDefs": [
            {
                "targets": [4], // Columna de precio
                "render": function(data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        return '$ ' + parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            {
                "targets": [3], // Columna de duración
                "className": "text-center"
            },
            {
                "targets": [6], // Columna de acciones
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[0, 'asc']] // Ordenar por la primera columna (Nro)
    }).buttons().container().appendTo('#tablaTarifas_wrapper .col-md-6:eq(0)');
});

document.addEventListener('DOMContentLoaded', function() {
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

    botonesCambiarEstado.forEach(boton => {
        boton.addEventListener('click', function() {
            const tarifaId = this.dataset.id;
            const estadoActual = this.dataset.estado;
            const nombreTarifa = this.dataset.nombre;

            const tituloAlerta = estadoActual == 1 ? `¿Desactivar ${nombreTarifa}?` : `¿Activar ${nombreTarifa}?`;
            const textoAlerta = estadoActual == 1 ? 'La tarifa no estará disponible para asignación.' : 'La tarifa estará disponible para asignación.';
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
                    window.location.href = `${BASE_URL}controllers/tarifas/desactivar_tarifa.php?id=${tarifaId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
                }
            });
        });
    });
});
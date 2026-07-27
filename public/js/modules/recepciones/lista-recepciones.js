document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable
    $("#tablaRecepciones").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
                extend: 'collection',
                text: 'Reportes',
                orientation: 'landscape',
                buttons: [{
                    text: 'Copiar',
                    extend: 'copy',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    }
                }, {
                    extend: 'pdf',
                    title: 'Recepciones del Sistema' + ' - ' + APP_NAME,
                    filename: 'recepciones_sistema_' + new Date().toISOString().slice(0, 10),
                    pageSize: 'LETTER',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                    customize: function (doc) {
                        // Estilo básico
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 11;
                        doc.styles.tableHeader.fillColor = '#4b545c';
                        doc.styles.tableHeader.color = '#ffffff';

                        // Agregar título con fecha
                        doc.content.splice(0, 1, {
                            text: 'RECEPCIONES DEL SISTEMA' + ' - ' + APP_NAME.toUpperCase(),
                            style: {
                                fontSize: 16,
                                alignment: 'center',
                                bold: true,
                                margin: [0, 10, 0, 10]
                            }
                        });

                        // Agregar subtítulo
                        let tituloTexto = 'Registro de recepciones y reservas';

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
                    title: 'Recepciones del Sistema' + ' - ' + APP_NAME,
                    messageTop: 'Registro de recepciones y reservas del sistema',
                    messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                            body: function (data, row, column, node) {
                                if (column === 7) { // Columna de estado
                                    return $(node).find('span').text();
                                }
                                return data;
                            }
                        }
                    }
                }, {
                    extend: 'csv',
                    text: 'CSV',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    }
                }, {
                    extend: 'print',
                    text: 'Imprimir',
                    title: 'Recepciones del Sistema' + ' - ' + APP_NAME,
                    messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
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
        "pageLength": 5,
        lengthMenu: [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Recepciones",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Recepciones",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Recepciones)",
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
    }).buttons().container().appendTo('#tablaRecepciones_wrapper .col-md-6:eq(0)');

    // Manejar check-in
    $(document).on('click', '.btn-checkin', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: '¿Realizar Check-in?',
            text: 'Se cambiará el estado de la reserva a "En curso"',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, realizar check-in',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${BASE_URL}controllers/recepcion/cambiar_estado.php?id=${id}&nuevo_estado=en_curso&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });

    // Manejar check-out
    $(document).on('click', '.btn-checkout', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: '¿Realizar Check-out?',
            text: 'Se finalizará la estancia del huésped',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, realizar check-out',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${BASE_URL}controllers/recepcion/cambiar_estado.php?id=${id}&nuevo_estado=finalizado&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });

    // Manejar cancelación
    $(document).on('click', '.btn-cancelar', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: '¿Cancelar reserva?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar reserva',
            cancelButtonText: 'No cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${BASE_URL}controllers/recepcion/cambiar_estado.php?id=${id}&nuevo_estado=cancelado&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });
});

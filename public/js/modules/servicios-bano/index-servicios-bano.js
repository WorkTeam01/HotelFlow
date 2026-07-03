document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable
    try {
        const tabla = $("#tablaServicios").DataTable({
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
                    title: 'Servicios de Baños' + ' - ' + APP_NAME,
                    filename: 'servicios_banos_' + new Date().toISOString().slice(0, 10),
                    pageSize: 'LETTER',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                    customize: function (doc) {
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 11;
                        doc.styles.tableHeader.fillColor = '#4b545c';
                        doc.styles.tableHeader.color = '#ffffff';
                        doc.content.splice(0, 1, {
                            text: 'SERVICIOS DE BAÑOS' + ' - ' + APP_NAME.toUpperCase(),
                            style: {
                                fontSize: 16,
                                alignment: 'center',
                                bold: true,
                                margin: [0, 10, 0, 10]
                            }
                        });
                        doc.content.splice(1, 0, {
                            text: 'Servicios registrados',
                            style: {
                                fontSize: 11,
                                alignment: 'center',
                                italic: true,
                                margin: [0, 0, 0, 10]
                            }
                        });
                        doc.content.splice(2, 0, {
                            text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                            style: {
                                fontSize: 9,
                                alignment: 'right',
                                margin: [0, 0, 0, 10]
                            }
                        });
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
                    title: 'Servicios de Baños' + ' - ' + APP_NAME,
                    messageTop: 'Registro de servicios de baños del sistema',
                    messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                            body: function (data, row, column, node) {
                                if (column === 7) {
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
                    title: 'Servicios de Baños' + ' - ' + APP_NAME,
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
            "pageLength": 10,
            lengthMenu: [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "Todos"]
            ],
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ servicios",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 servicios",
                "sInfoFiltered": "(filtrado de un total de _MAX_ servicios)",
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
        });

        if (tabla) {
            tabla.buttons().container().appendTo('#tablaServicios_wrapper .col-md-6:eq(0)');
        }
    } catch (e) {
        console.error('Error al inicializar DataTable:', e);
    }

    // Función para mostrar diálogo de confirmación
    function mostrarDialogoConfirmacion(id, estado, accion) {
        let tituloAlerta = '';
        let textoAlerta = '';
        let confirmButtonText = '';
        let confirmButtonColor = '';

        if (accion === '1' || accion === 1) {
            tituloAlerta = '¿Activar este servicio?';
            textoAlerta = 'El servicio se marcará como activo.';
            confirmButtonText = 'Sí, activar';
            confirmButtonColor = '#28a745';
        } else {
            tituloAlerta = '¿Desactivar este servicio?';
            textoAlerta = 'El servicio se marcará como inactivo.';
            confirmButtonText = 'Sí, desactivar';
            confirmButtonColor = '#dc3545';
        }

        // Asegurarnos que Swal esté definido
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 no está disponible');
            if (confirm(textoAlerta)) {
                window.location.href = `${baseUrl}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&estado_actual=${estado}&nuevo_estado=${accion}`;
            }
            return;
        }

        // Usar setTimeout para evitar problemas
        setTimeout(function () {
            try {
                Swal.fire({
                    title: tituloAlerta,
                    text: textoAlerta,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: 'No, volver',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `${baseUrl}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&estado_actual=${estado}&nuevo_estado=${accion}`;
                    }
                });
            } catch (error) {
                console.error('Error al mostrar SweetAlert:', error);
                // Fallback al diálogo nativo en caso de error
                if (confirm(textoAlerta)) {
                    window.location.href = `${baseUrl}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&estado_actual=${estado}&nuevo_estado=${accion}`;
                }
            }
        }, 100);
    }

    // Manejar cambio de estado de servicio
    // Usar delegación de eventos para mejor compatibilidad
    $(document).on('click', '.btn-cambiar-estado', function (e) {
        e.preventDefault();

        const servicioId = $(this).data('id');
        const estadoActual = $(this).data('estado');
        const accion = $(this).data('accion');

        mostrarDialogoConfirmacion(servicioId, estadoActual, accion);
    });
});
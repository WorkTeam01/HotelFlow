$(document).ready(function () {
    // Inicializar DataTable
    $("#tablaUsuarios").DataTable({
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
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'pdf',
                title: 'Usuarios del Sistema' + ' - ' + APP_NAME,
                filename: 'usuarios_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                customize: function (doc) {

                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'USUARIOS DEL SISTEMA' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar titulo
                    let tituloTexto = 'Usuarios registrados';

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
                title: 'Usuarios del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Registro de usuarios del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 5) {
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
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Usuarios del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1]
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Usuarios",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Usuarios",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Usuarios)",
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
    }).buttons().container().appendTo('#tablaUsuarios_wrapper .col-md-6:eq(0)');

    // Cambiar estado (activo/inactivo) de un usuario sin recargar la página.
    // Delegado sobre la tabla: los botones sobreviven al reordenamiento/paginado de DataTables.
    $('#tablaUsuarios').on('click', '.btn-cambiar-estado', function () {
        const boton = $(this);
        const usuarioId = boton.data('id');
        const estadoActual = boton.data('estado');
        const nombreUsuario = boton.data('nombre');

        const tituloAlerta = estadoActual == 1 ? `¿Desactivar a ${nombreUsuario}?` : `¿Activar a ${nombreUsuario}?`;
        const textoAlerta = estadoActual == 1 ? 'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' : 'El usuario podrá acceder nuevamente al sistema.';
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
            if (!result.isConfirmed) return;

            boton.prop('disabled', true);

            ajaxRequest(
                `${BASE_URL}controllers/usuarios/desactivar_usuario.php`,
                'GET',
                {
                    id: usuarioId,
                    estado: estadoActual,
                    csrf_token: CSRF_TOKEN
                },
                function (data) {
                    boton.prop('disabled', false);

                    if (!data.success) {
                        Swal.fire('Error', data.message, 'error');
                        return;
                    }

                    // Actualizar la fila en el DOM, sin recargar la página
                    // (conserva página/orden/filtro de la tabla)
                    const fila = boton.closest('tr');
                    const badgeEstado = fila.find('.badge');
                    const activo = data.nuevoEstado == 1;

                    badgeEstado.text(activo ? 'Activo' : 'Inactivo');
                    badgeEstado.toggleClass('badge-success', activo).toggleClass('badge-danger', !activo);

                    boton.data('estado', data.nuevoEstado);
                    boton.toggleClass('btn-danger', activo).toggleClass('btn-success', !activo);
                    boton.attr('aria-label', activo ? 'Desactivar usuario' : 'Activar usuario');
                    boton.find('i').toggleClass('fa-user-slash', activo).toggleClass('fa-user-check', !activo);

                    Swal.fire({
                        icon: 'success',
                        title: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                function () {
                    boton.prop('disabled', false);
                    Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
                }
            );
        });
    });
});
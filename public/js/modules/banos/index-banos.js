$(document).ready(function () {
    // Inicializar DataTable
    $("#tablaBanos").DataTable({
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
                    columns: [0, 1, 2, 3, 4]
                }
            }, {
                extend: 'pdf',
                title: 'Baños del Sistema' + ' - ' + APP_NAME,
                filename: 'banos_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'BAÑOS DEL SISTEMA' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar titulo
                    let tituloTexto = 'Baños registrados';

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
                title: 'Baños del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Registro de baños del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 4) {
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
                    columns: [0, 1, 2, 3, 4]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Baños del Sistema' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Baños",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Baños",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Baños)",
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
    }).buttons().container().appendTo('#tablaBanos_wrapper .col-md-6:eq(0)');

    // Inicializar Select2
    initializeSelect2();

    // Botón para crear nuevo baño
    $(document).on('click', '#btnNuevoBano', function () {
        // Configurar para creación
        $('#banoAction').val('create');
        $('#idBano').val('');
        $('#nombreBano').val('');
        $('#ubicacionBano').val('');
        $('#precioBano').val('0.00');
        $('#estadoBano').val('disponible');

        // Cambiar apariencia del modal
        $('#modalBanoHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalBanoLabel').text('Crear Nuevo Baño');
        $('#btnGuardarBano').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarBano').html('<i class="fas fa-check"></i> Guardar Baño');

        // Mostrar modal
        $('#modalBano').modal('show');
    });

    // Botón para editar baño
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const ubicacion = $(this).data('ubicacion');
        const precio = $(this).data('precio');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#banoAction').val('edit');
        $('#idBano').val(id);
        $('#nombreBano').val(nombre);
        $('#ubicacionBano').val(ubicacion);
        $('#precioBano').val(precio);
        $('#estadoBano').val(estado);

        $('#estadoBano').trigger('change');

        // Cambiar apariencia del modal
        $('#modalBanoHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalBanoLabel').text('Editar Baño');
        $('#btnGuardarBano').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarBano').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalBano').modal('show');
    });

    // Procesar formulario
    $('#formBano').on('submit', function (e) {
        e.preventDefault();

        const action = $('#banoAction').val();
        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = baseUrl + 'controllers/banos/crear_bano_ajax.php';
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-check"></i> Guardar Baño';
        } else {
            url = baseUrl + 'controllers/banos/actualizar_bano_ajax.php';
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarBano').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarBano').prop('disabled', false).html(successBtn);
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarBano').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $('.cambiar-estado').on('click', function (e) {
        e.preventDefault();

        const banoId = $(this).data('id');
        const estadoActual = $(this).data('estado');
        const nuevoEstado = $(this).data('nuevo-estado');
        const nombre = $(this).data('nombre');

        let textoEstado = '';
        let iconoAlerta = '';

        switch (nuevoEstado) {
            case 'disponible':
                textoEstado = 'marcar como disponible';
                iconoAlerta = 'info';
                break;
            case 'mantenimiento':
                textoEstado = 'poner en mantenimiento';
                iconoAlerta = 'warning';
                break;
            case 'fuera_servicio':
                textoEstado = 'marcar fuera de servicio';
                iconoAlerta = 'error';
                break;
        }

        Swal.fire({
            title: `¿Desea ${textoEstado} este baño?`,
            text: `Baño: ${nombre}`,
            icon: iconoAlerta,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: baseUrl + 'controllers/banos/cambiar_estado_bano_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: banoId,
                        nuevo_estado: nuevoEstado
                    },
                    success: function (response) {
                        if (response.success) {
                            // Recargar la página para mostrar el mensaje con mensajes.php
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor'
                        });
                    }
                });
            }
        });
    });

    // Limpiar modal al cerrarlo
    $('#modalBano').on('hidden.bs.modal', function () {
        $('#formBano')[0].reset();
    });
});
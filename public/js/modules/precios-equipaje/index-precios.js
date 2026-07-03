/**
 * Script para la gestión de precios de equipaje
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con los precios de equipaje (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    // Inicializar Select2
    initializeSelect2();

    // Inicializar DataTable
    const tabla = $("#tablaPrecios").DataTable({
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
                title: 'Precios de Equipaje' + ' - ' + APP_NAME,
                filename: 'precios_equipaje_' + new Date().toISOString().slice(0, 10),
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
                        text: 'PRECIOS DE EQUIPAJE' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar título
                    let tituloTexto = 'Precios registrados';

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
                title: 'Precios de Equipaje' + ' - ' + APP_NAME,
                messageTop: 'Registro de precios de equipaje del sistema',
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
                title: 'Precios de Equipaje' + ' - ' + APP_NAME,
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ precios",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 precios",
            "sInfoFiltered": "(filtrado de un total de _MAX_ precios)",
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

    // Agregar botones a la tabla
    tabla.buttons().container().appendTo('#tablaPrecios_wrapper .col-md-6:eq(0)');

    // Botón para crear nuevo precio
    $('#btnNuevoPrecio').on('click', function () {
        // Limpiar formulario
        $('#formPrecio')[0].reset();

        // Configurar para creación
        $('#precioAction').val('create');
        $('#idPrecio').val('');
        $('#tamano').val('');
        $('#descripcion').val('');
        $('#precio').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalPrecioHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalPrecioLabel').text('Crear Nuevo Precio de Equipaje');
        $('#btnGuardarPrecio').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarPrecio').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalPrecio').modal('show');
    });

    // Botón para editar precio
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const tamano = $(this).data('tamano');
        const descripcion = $(this).data('descripcion') || ''; // Manejar caso de descripción null o undefined
        const precio = $(this).data('precio');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#precioAction').val('edit');
        $('#idPrecio').val(id);
        $('#tamano').val(tamano);
        $('#descripcion').val(descripcion);
        $('#precio').val(precio);
        $('#estado').val(estado);

        // Cambiar apariencia del modal
        $('#modalPrecioHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalPrecioLabel').text('Editar Precio de Equipaje');
        $('#btnGuardarPrecio').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarPrecio').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalPrecio').modal('show');
    });

    // Procesar formulario
    $('#formPrecio').on('submit', function (e) {
        e.preventDefault();

        const action = $('#precioAction').val();

        // Manejar descripción vacía para asegurar que se guarde como NULL
        if ($('#descripcion').val().trim() === '') {
            $('#descripcion').val(''); // Asegurarse de que esté vacío sin espacios
        }

        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/precios-equipaje/crear_precio_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/precios-equipaje/actualizar_precio_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarPrecio').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalPrecio').modal('hide');

                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarPrecio').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarPrecio').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $('.cambiar-estado').on('click', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const boton = $(this);

        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const textoEstado = estadoActual == 1 ? 'desactivar' : 'activar';
        const textoEstadoCapitalizado = textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1);

        Swal.fire({
            title: `¿${textoEstadoCapitalizado} este precio?`,
            text: `El precio de equipaje será ${textoEstado}do.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/precios-equipaje/cambiar_estado_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado_actual: estadoActual
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
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
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

    // Limpiar modal al cerrarlo para evitar problemas en dispositivos móviles
    $('#modalPrecio').on('hidden.bs.modal', function () {
        $('#formPrecio')[0].reset();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formPrecio').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalPrecio').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#tamano').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalPrecio').data('backdrop', 'static');
    $('#modalPrecio').data('keyboard', false);
});
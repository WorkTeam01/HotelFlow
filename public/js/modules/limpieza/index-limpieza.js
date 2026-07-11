/**
 * Script para la gestión de asignaciones de limpieza
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con las asignaciones de limpieza (crear, editar, cambiar estado, eliminar)
 */

$(document).ready(function () {
    // Inicializar tooltips con la nueva función de utilidad
    initializeTooltips();

    // Inicializar select2 para los selectores de habitación y usuario
    initializeSelect2();

    // Establecer fecha de hoy por defecto en el campo fecha
    const hoy = new Date().toISOString().split('T')[0];
    $('#fecha').val(hoy);

    // Establecer hora actual por defecto en el campo hora
    const ahora = new Date();
    const hora = ('0' + ahora.getHours()).slice(-2) + ':' + ('0' + ahora.getMinutes()).slice(-2);
    $('#hora').val(hora);

    // Inicializar DataTable
    const tabla = $("#tablaAsignaciones").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
            extend: 'collection',
            text: 'Exportar',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'pdf',
                title: 'Asignaciones de Limpieza' + ' - ' + APP_NAME,
                filename: 'asignaciones_limpieza_' + new Date().toISOString().slice(0, 10),
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
                        text: 'ASIGNACIONES DE LIMPIEZA' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar titulo
                    let tituloTexto = 'Asignaciones registradas';

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
                title: 'Asignaciones de Limpieza' + ' - ' + APP_NAME,
                messageTop: 'Registro de asignaciones de limpieza del sistema',
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
                title: 'Asignaciones de Limpieza' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ asignaciones",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 asignaciones",
            "sInfoFiltered": "(filtrado de un total de _MAX_ asignaciones)",
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
        "order": [
            [3], [4] // Ordenar por fecha y hora descendente
        ]
    });

    // Agregar botones a la tabla
    tabla.buttons().container().appendTo('#tablaAsignaciones_wrapper .col-md-6:eq(0)');

    // Botón para crear nueva asignación
    $('#btnNuevaAsignacion').on('click', function () {
        // Asegurarse de limpiar completamente el formulario y los selects
        $('#formAsignacion')[0].reset();

        // Limpiar explícitamente los selects y sus datos
        $('#idhabitacion').empty().append('<option value="">Seleccione una habitación</option>');
        $('#idusuario').val('').trigger('change');

        // Cargar habitaciones disponibles para nueva asignación
        $.ajax({
            url: `${baseUrl}controllers/limpieza/obtener_habitaciones_ajax.php`,
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                id_asignacion: null // No hay asignación actual
            },
            success: function (response) {
                if (response.success) {
                    // Establecer fecha y hora actuales
                    const hoy = new Date().toISOString().split('T')[0];
                    $('#fecha').val(hoy);

                    const ahora = new Date();
                    const hora = ('0' + ahora.getHours()).slice(-2) + ':' + ('0' + ahora.getMinutes()).slice(-2);
                    $('#hora').val(hora);

                    // Configurar para creación
                    $('#asignacionAction').val('create');
                    $('#idasignacion').val('');
                    $('#estado').val('pendiente');

                    // Limpiar otra vez y actualizar el select de habitaciones (por seguridad)
                    $('#idhabitacion').empty().append('<option value="">Seleccione una habitación</option>');

                    // Agregar las habitaciones disponibles
                    $.each(response.habitaciones, function (index, hab) {
                        $('#idhabitacion').append(`<option value="${hab.id_habitacion}">${hab.numero} - ${hab.tipo}</option>`);
                    });

                    // Cambiar apariencia del modal
                    $('#modalAsignacionHeader').removeClass('bg-warning').addClass('bg-primary');
                    $('#modalAsignacionLabel').text('Crear Nueva Asignación de Limpieza');
                    $('#btnGuardarAsignacion').removeClass('btn-warning').addClass('btn-primary');
                    $('#btnGuardarAsignacion').html('<i class="fas fa-save"></i> Guardar');

                    // Reinicializar Select2 para que refleje los cambios
                    if ($.fn.select2) {
                        $('#idhabitacion').select2('destroy');
                        $('#idusuario').select2('destroy');

                        initializeSelect2('#idhabitacion');
                        initializeSelect2('#idusuario');
                    }

                    // Mostrar modal
                    $('#modalAsignacion').modal('show');
                } else {
                    showToast(response.message || 'Error al cargar las habitaciones disponibles', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                showToast('Error en la comunicación con el servidor', 'error');
            }
        });
    });

    // Botón para editar asignación
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const usuario = $(this).data('usuario');
        const habitacion = $(this).data('habitacion');
        const fecha = $(this).data('fecha');
        const hora = $(this).data('hora');
        const estado = $(this).data('estado');
        const observaciones = $(this).data('observaciones') || '';

        // Si el estado es "verificada", mostrar un mensaje y no permitir la edición
        if (estado === 'verificada') {
            showToast('No se puede editar una asignación que ya ha sido verificada', 'warning');
            return;
        }

        // Limpiar completamente el formulario y los selects antes de cargar nuevos datos
        $('#formAsignacion')[0].reset();
        $('#idhabitacion').empty().append('<option value="">Seleccione una habitación</option>');
        $('#idusuario').val('');

        // Cargar habitaciones disponibles incluyendo la habitación actual
        $.ajax({
            url: `${baseUrl}controllers/limpieza/obtener_habitaciones_ajax.php`,
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                id_asignacion: id // Pasar el ID de la asignación
            },
            success: function (response) {
                if (response.success) {
                    // Configurar para edición
                    $('#asignacionAction').val('edit');
                    $('#idasignacion').val(id);
                    $('#fecha').val(fecha);
                    $('#hora').val(hora);
                    $('#estado').val(estado);
                    $('#observaciones').val(observaciones);

                    // Limpiar y actualizar el select de habitaciones
                    $('#idhabitacion').empty().append('<option value="">Seleccione una habitación</option>');

                    // Agregar las habitaciones disponibles
                    $.each(response.habitaciones, function (index, hab) {
                        const selected = parseInt(hab.id_habitacion) === parseInt(habitacion);
                        $('#idhabitacion').append(`<option value="${hab.id_habitacion}" ${selected ? 'selected' : ''}>${hab.numero} - ${hab.tipo}</option>`);
                    });

                    // Seleccionar el usuario
                    $('#idusuario').val(usuario);

                    // Cambiar apariencia del modal
                    $('#modalAsignacionHeader').removeClass('bg-primary').addClass('bg-warning');
                    $('#modalAsignacionLabel').text('Editar Asignación de Limpieza');
                    $('#btnGuardarAsignacion').removeClass('btn-primary').addClass('btn-warning');
                    $('#btnGuardarAsignacion').html('<i class="fas fa-save"></i> Actualizar');

                    // Reinicializar Select2 para que refleje los cambios
                    if ($.fn.select2) {
                        $('#idhabitacion').select2('destroy');
                        $('#idusuario').select2('destroy');

                        initializeSelect2('#idhabitacion');
                        initializeSelect2('#idusuario');
                    }

                    // Mostrar modal
                    $('#modalAsignacion').modal('show');
                } else {
                    showToast(response.message || 'Error al cargar las habitaciones', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                showToast('Error en la comunicación con el servidor', 'error');
            }
        });
    });

    // Procesar formulario
    $('#formAsignacion').on('submit', function (e) {
        e.preventDefault();

        const action = $('#asignacionAction').val();
        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/limpieza/crear_asignacion_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/limpieza/actualizar_asignacion_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarAsignacion').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalAsignacion').modal('hide');

                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    showToast(response.message, 'error');
                    $('#btnGuardarAsignacion').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                showToast('Ocurrió un error en la comunicación con el servidor', 'error');
                $('#btnGuardarAsignacion').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $('.cambiar-estado').on('click', function () {
        const id = $(this).data('id');
        const estado = $(this).data('estado');
        const boton = $(this);

        // Textos para diferentes estados
        const textos = {
            'pendiente': 'pendiente',
            'enprogreso': 'en progreso',
            'completada': 'completada',
            'verificada': 'verificada'
        };

        // Colores para diferentes estados
        const colores = {
            'pendiente': '#ffc107',
            'enprogreso': '#007bff',
            'completada': '#28a745',
            'verificada': '#6c757d'
        };

        Swal.fire({
            title: `¿Marcar como ${textos[estado]}?`,
            text: `La asignación de limpieza se marcará como ${textos[estado]}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: colores[estado],
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, marcar como ${textos[estado]}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest(
                    `${baseUrl}controllers/limpieza/cambiar_estado_ajax.php`,
                    'POST',
                    {
                        id: id,
                        estado: estado,
                        csrf_token: CSRF_TOKEN
                    },
                    function (response) {
                        if (response.success) {
                            // Recargar la página para mostrar el mensaje con mensajes.php
                            location.reload();
                        } else {
                            showToast(response.message, 'error');
                        }
                    }
                );
            }
        });
    });

    // Manejar eliminación de asignación
    $('.btn-eliminar').on('click', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar esta asignación?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest(
                    `${baseUrl}controllers/limpieza/eliminar_asignacion_ajax.php`,
                    'POST',
                    { id: id },
                    function (response) {
                        if (response.success) {
                            // Recargar la página para mostrar el mensaje con mensajes.php
                            location.reload();
                        } else {
                            showToast(response.message, 'error');
                        }
                    }
                );
            }
        });
    });

    // Limpiar modal al cerrarlo para evitar problemas
    $('#modalAsignacion').on('hidden.bs.modal', function () {
        // Resetear el formulario
        $('#formAsignacion')[0].reset();

        // Limpiar clases de validación
        $('.is-invalid').removeClass('is-invalid');

        // Restaurar estilos CSS
        $('#formAsignacion').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Ocultar tooltips
        $('[data-toggle="tooltip"]').tooltip('hide');

        // Limpiar completamente los selects
        $('#idhabitacion').empty().append('<option value="">Seleccione una habitación</option>');
        $('#idusuario').val('');

        // Destruir y reinicializar Select2 si existe
        if ($.fn.select2) {
            $('#idhabitacion').select2('destroy');
            $('#idusuario').select2('destroy');

            initializeSelect2('#idhabitacion');
            initializeSelect2('#idusuario');
        }

        // Limpiar campos ocultos
        $('#idasignacion').val('');
        $('#asignacionAction').val('');
    });

    // Configurar estado correcto al mostrar el modal
    $('#modalAsignacion').on('shown.bs.modal', function () {
        // Aplicar scroll solo al cuerpo del modal, no a todo el modal
        $('.modal-body').css('max-height', 'calc(100vh - 200px)');
        $('.modal-body').css('overflow-y', 'auto');

        // Verificar si estamos en modo creación o edición
        const action = $('#asignacionAction').val();

        if (action === 'create') {
            // Asegurarse de que el título y los botones sean correctos
            $('#modalAsignacionHeader').removeClass('bg-warning').addClass('bg-primary');
            $('#modalAsignacionLabel').text('Crear Nueva Asignación de Limpieza');
            $('#btnGuardarAsignacion').removeClass('btn-warning').addClass('btn-primary');
            $('#btnGuardarAsignacion').html('<i class="fas fa-save"></i> Guardar');

            // Establecer el enfoque en el primer campo
            $('#idhabitacion').trigger('focus');
        } else if (action === 'edit') {
            // Asegurarse de que el título y los botones sean correctos
            $('#modalAsignacionHeader').removeClass('bg-primary').addClass('bg-warning');
            $('#modalAsignacionLabel').text('Editar Asignación de Limpieza');
            $('#btnGuardarAsignacion').removeClass('btn-primary').addClass('btn-warning');
            $('#btnGuardarAsignacion').html('<i class="fas fa-save"></i> Actualizar');

            // Establecer el enfoque en el primer campo
            $('#idhabitacion').trigger('focus');
        }

        // Asegurarse de que Select2 esté inicializado correctamente
        if ($.fn.select2) {
            refreshSelect2('#idhabitacion');
            refreshSelect2('#idusuario');
        }
    });

    // Manejar eventos para las asignaciones de hoy
    $('.btn-hoy').on('click', function () {
        // Filtrar la tabla para mostrar solo asignaciones de hoy
        const hoy = new Date().toISOString().split('T')[0];
        const tabla = $('#tablaAsignaciones').DataTable();
        tabla.columns(3).search(hoy).draw(); // Columna 3 es la fecha
    });

    // Manejar eventos para filtrar por estado
    $('.btn-filtro-estado').on('click', function () {
        const estado = $(this).data('estado');
        const tabla = $('#tablaAsignaciones').DataTable();
        tabla.columns(5).search(estado).draw(); // Columna 5 es el estado
    });

    // Mostrar todas las asignaciones
    $('.btn-mostrar-todas').on('click', function () {
        const tabla = $('#tablaAsignaciones').DataTable();
        tabla.columns().search('').draw();
    });

    // Validar formulario
    $('#formAsignacion').on('submit', function (e) {
        let isValid = true;

        // Validar habitación
        if (!$('#idhabitacion').val()) {
            $('#idhabitacion').addClass('is-invalid');
            isValid = false;
        } else {
            $('#idhabitacion').removeClass('is-invalid');
        }

        // Validar usuario
        if (!$('#idusuario').val()) {
            $('#idusuario').addClass('is-invalid');
            isValid = false;
        } else {
            $('#idusuario').removeClass('is-invalid');
        }

        // Validar fecha
        if (!$('#fecha').val()) {
            $('#fecha').addClass('is-invalid');
            isValid = false;
        } else {
            $('#fecha').removeClass('is-invalid');
        }

        // Validar hora
        if (!$('#hora').val()) {
            $('#hora').addClass('is-invalid');
            isValid = false;
        } else {
            $('#hora').removeClass('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            showToast('Por favor complete todos los campos obligatorios', 'error');
        }

        return isValid;
    });
});
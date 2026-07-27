/**
 * Script para la gestión de habitaciones con filtrado del lado del cliente
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar Select2
    initializeSelect2();

    // Inicializar DataTable
    const tabla = $("#tablaHabitaciones").DataTable({
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
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                extend: 'pdf',
                title: 'Habitaciones' + ' - ' + APP_NAME,
                filename: 'habitaciones_' + new Date().toISOString().slice(0, 10),
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
                        text: 'HABITACIONES' + ' - ' + APP_NAME.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(1, 0, {
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
                title: 'Habitaciones' + ' - ' + APP_NAME,
                messageTop: 'Registro de habitaciones del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 6) {
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
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Habitaciones' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ habitaciones",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 habitaciones",
            "sInfoFiltered": "(filtrado de un total de _MAX_ habitaciones)",
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
    tabla.buttons().container().appendTo('#tablaHabitaciones_wrapper .col-md-6:eq(0)');

    // Filtros personalizados para DataTables
    // Esta es una personalización para buscar en toda la tabla
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            const $row = $(tabla.row(dataIndex).node());

            // Filtro de estado
            const estadoFiltro = $('#filtro-estado').val();
            const estadoRow = $row.data('estado');

            if (estadoFiltro && estadoFiltro !== estadoRow) {
                return false;
            }

            // Filtro de tipo
            const tipoFiltro = $('#filtro-tipo').val();
            const tipoRow = data[2]; // Índice de la columna de tipo

            if (tipoFiltro && !tipoRow.includes(tipoFiltro)) {
                return false;
            }

            // Filtro de piso
            const pisoFiltro = $('#filtro-piso').val();
            const pisoRow = data[3]; // Índice de la columna de piso

            if (pisoFiltro && !pisoRow.includes(pisoFiltro)) {
                return false;
            }

            // Filtro de precio
            const precioFiltro = parseFloat($('#filtro-precio').val()) || 0;
            const precioRow = parseFloat(data[5].replace(/[^\d.-]/g, '')) || 0;

            if (precioFiltro > 0 && precioRow < precioFiltro) {
                return false;
            }

            // Si pasó todos los filtros, mostrar la fila
            return true;
        }
    );

    // Eventos para los botones de filtro rápido
    $('#btn-filtro-todos').on('click', function () {
        $('#filtro-estado').val('').trigger('change');
        tabla.draw();
    });

    $('#btn-filtro-disponible').on('click', function () {
        $('#filtro-estado').val('disponible').trigger('change');
        tabla.draw();
    });

    $('#btn-filtro-ocupada').on('click', function () {
        $('#filtro-estado').val('ocupada').trigger('change');
        tabla.draw();
    });

    $('#btn-filtro-mantenimiento').on('click', function () {
        $('#filtro-estado').val('mantenimiento').trigger('change');
        tabla.draw();
    });

    $('#btn-filtro-limpieza').on('click', function () {
        $('#filtro-estado').val('limpieza').trigger('change');
        tabla.draw();
    });

    // Botón de aplicar filtros
    $('#btn-aplicar-filtros').on('click', function () {
        tabla.draw();
    });

    // Botón de limpiar filtros
    $('#btn-limpiar-filtros').on('click', function () {
        $('#filtro-estado').val('').trigger('change');
        $('#filtro-tipo').val('').trigger('change');
        $('#filtro-piso').val('').trigger('change');
        $('#filtro-precio').val('');

        // Desactivar todos los botones de filtro rápido y activar "Todos"
        $('.btn-group-toggle label').removeClass('active');
        $('#btn-filtro-todos').addClass('active');

        tabla.draw();
    });

    // Usar delegación de eventos para manejar los botones de cambio de estado
    // Esto permite que los botones agregados dinámicamente funcionen correctamente
    $(document).on('click', '.cambiar-estado', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const id = $(this).data('id');
        const estado = $(this).data('estado');
        const estadoActual = $(this).data('estado-actual');
        const $row = $(this).closest('tr');

        // Mapeo de estados para mostrar mensajes amigables
        const estadosTexto = {
            'disponible': 'Disponible',
            'ocupada': 'Ocupada',
            'mantenimiento': 'Mantenimiento',
            'limpieza': 'Limpieza'
        };

        // Mapeo de colores para los estados
        const estadosColor = {
            'disponible': '#28a745',  // Verde
            'ocupada': '#ffc107',     // Amarillo
            'mantenimiento': '#dc3545', // Rojo
            'limpieza': '#007bff'      // Azul
        };

        // Mapeo de clases para los badges
        const estadosClase = {
            'disponible': 'badge-success',
            'ocupada': 'badge-warning',
            'mantenimiento': 'badge-danger',
            'limpieza': 'badge-primary'
        };

        Swal.fire({
            title: `¿Cambiar a estado ${estadosTexto[estado]}?`,
            text: `La habitación pasará de "${estadosTexto[estadoActual]}" a "${estadosTexto[estado]}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadosColor[estado],
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Espere un momento mientras se actualiza el estado.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar solicitud AJAX
                $.ajax({
                    url: `${BASE_URL}controllers/habitaciones/cambiar_estado.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado: estado,
                        csrf_token: CSRF_TOKEN
                    },
                    success: function (response) {
                        if (response.success) {
                            // Mostrar toast de éxito y esperar a que se complete
                            Toast.fire({
                                icon: 'success',
                                title: response.message
                            }).then(() => {
                                // Actualizar la fila en lugar de recargar toda la página
                                // 1. Actualizar el estado en el atributo de la fila
                                $row.attr('data-estado', estado);

                                // 2. Actualizar el badge de estado
                                const $badge = $row.find('td:eq(6) span.badge');
                                $badge.removeClass().addClass(`badge ${estadosClase[estado]} p-2`);
                                $badge.text(estadosTexto[estado]);

                                // 3. Actualizar los botones de acción
                                const $btnGroup = $row.find('.btn-group');

                                // Primero, mantener solo los botones permanentes (ver y editar)
                                const $permanentButtons = $btnGroup.find('a').clone();
                                $btnGroup.empty().append($permanentButtons);

                                // Luego, agregar los botones según el nuevo estado
                                if (estado !== 'disponible') {
                                    // Agregar botón de disponible
                                    const $btnDisponible = $('<button type="button" class="btn btn-success btn-sm cambiar-estado" title="Marcar como Disponible"><i class="fas fa-check-circle"></i></button>');
                                    $btnDisponible.attr('data-id', id);
                                    $btnDisponible.attr('data-estado', 'disponible');
                                    $btnDisponible.attr('data-estado-actual', estado);
                                    $btnGroup.append($btnDisponible);
                                }

                                if (estado !== 'limpieza') {
                                    // Agregar botón de limpieza
                                    const $btnLimpieza = $('<button type="button" class="btn btn-primary btn-sm cambiar-estado" title="Marcar para Limpieza"><i class="fas fa-broom"></i></button>');
                                    $btnLimpieza.attr('data-id', id);
                                    $btnLimpieza.attr('data-estado', 'limpieza');
                                    $btnLimpieza.attr('data-estado-actual', estado);
                                    $btnGroup.append($btnLimpieza);
                                }

                                if (estado !== 'mantenimiento') {
                                    // Agregar botón de mantenimiento
                                    const $btnMantenimiento = $('<button type="button" class="btn btn-danger btn-sm cambiar-estado" title="Marcar para Mantenimiento"><i class="fas fa-tools"></i></button>');
                                    $btnMantenimiento.attr('data-id', id);
                                    $btnMantenimiento.attr('data-estado', 'mantenimiento');
                                    $btnMantenimiento.attr('data-estado-actual', estado);
                                    $btnGroup.append($btnMantenimiento);
                                }

                                if (estado === 'disponible') {
                                    // Agregar botón de ocupada (solo si está disponible)
                                    const $btnOcupada = $('<button type="button" class="btn btn-warning btn-sm cambiar-estado" title="Marcar como Ocupada"><i class="fas fa-user"></i></button>');
                                    $btnOcupada.attr('data-id', id);
                                    $btnOcupada.attr('data-estado', 'ocupada');
                                    $btnOcupada.attr('data-estado-actual', estado);
                                    $btnGroup.append($btnOcupada);
                                }

                                // Actualizar los contadores en la vista rápida
                                actualizarContadores();
                            });
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

    // Función para actualizar los contadores en la vista rápida
    function actualizarContadores() {
        // Contar habitaciones por estado
        const total = tabla.rows().count();
        let disponibles = 0;
        let ocupadas = 0;
        let mantenimiento = 0;
        let limpieza = 0;

        tabla.rows().every(function () {
            const estado = $(this.node()).data('estado');
            switch (estado) {
                case 'disponible':
                    disponibles++;
                    break;
                case 'ocupada':
                    ocupadas++;
                    break;
                case 'mantenimiento':
                    mantenimiento++;
                    break;
                case 'limpieza':
                    limpieza++;
                    break;
            }
        });

        // Actualizar los contadores en la vista rápida
        $('#btn-filtro-todos .badge').text(total);
        $('#btn-filtro-disponible .badge').text(disponibles);
        $('#btn-filtro-ocupada .badge').text(ocupadas);
        $('#btn-filtro-mantenimiento .badge').text(mantenimiento);
        $('#btn-filtro-limpieza .badge').text(limpieza);
    }

    // Configuración para SweetAlert Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Función para manejar eventos de filtros select2
    $('.select2').on('change', function () {
        // Si es un cambio manual (no programático), actualizar estado de los botones
        if (!$(this).data('programmatic')) {
            if ($(this).attr('id') === 'filtro-estado') {
                const valor = $(this).val();
                // Actualizar los botones de filtro rápido
                $('.btn-group-toggle label').removeClass('active');

                if (valor === '') {
                    $('#btn-filtro-todos').addClass('active');
                } else if (valor === 'disponible') {
                    $('#btn-filtro-disponible').addClass('active');
                } else if (valor === 'ocupada') {
                    $('#btn-filtro-ocupada').addClass('active');
                } else if (valor === 'mantenimiento') {
                    $('#btn-filtro-mantenimiento').addClass('active');
                } else if (valor === 'limpieza') {
                    $('#btn-filtro-limpieza').addClass('active');
                }
            }
        }
    });

    // Ajuste para vista móvil
    function ajustarVistaMovil() {
        if (window.innerWidth < 768) {
            // Reducir el tamaño de los botones en móvil
            $('.btn-group .btn').addClass('btn-xs');

            // Ajustar la tabla para mostrar menos columnas en móvil
            tabla.columns([3, 4]).visible(false);

            // Hacer más pequeños los botones de filtro
            $('.btn-group-toggle .btn').addClass('btn-sm');
        } else {
            // Restaurar tamaño normal de botones
            $('.btn-group .btn').removeClass('btn-xs');

            // Mostrar todas las columnas en desktop
            tabla.columns([3, 4]).visible(true);

            // Restaurar tamaño de botones de filtro
            $('.btn-group-toggle .btn').removeClass('btn-sm');
        }
    }

    // Ejecutar ajuste inicial y cuando cambia el tamaño de ventana
    ajustarVistaMovil();
    $(window).resize(ajustarVistaMovil);

    // Detectar cambios en los filtros para marcarlos visualmente
    $('#filtro-precio').on('input', function () {
        if ($(this).val()) {
            $(this).addClass('border-primary');
        } else {
            $(this).removeClass('border-primary');
        }
    });

    // Iniciar con filtros limpios
    $('#btn-limpiar-filtros').trigger('click');
});
/**
 * Listado de habitaciones: DataTable, filtros del lado del cliente y cambio de
 * estado en línea. El estado se filtra desde las tarjetas de resumen
 * (#resumen-estados); tipo, piso y precio desde la tarjeta de filtros avanzados.
 */

$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    initializeSelect2();

    const tabla = $("#tablaHabitaciones").DataTable({
        "responsive": true,
        "autoWidth": false,
        // En viewports estrechos se colapsan primero Tipo/Piso/Capacidad/Precio;
        // Habitación, Estado y Acciones se mantienen visibles el mayor tiempo posible.
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: 5 },
            { responsivePriority: 3, targets: 6 },
            { responsivePriority: 10, targets: [2, 3, 4] }
        ],
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            }, {
                extend: 'pdf',
                title: 'Habitaciones' + ' - ' + APP_NAME,
                filename: 'habitaciones_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: 'HABITACIONES' + ' - ' + APP_NAME.toUpperCase(),
                        style: { fontSize: 16, alignment: 'center', bold: true, margin: [0, 10, 0, 10] }
                    });

                    doc.content.splice(1, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: { fontSize: 9, alignment: 'right', margin: [0, 0, 0, 10] }
                    });

                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [
                                { text: 'Sistema de Gestión' + ' - ' + APP_NAME, alignment: 'left', fontSize: 8 },
                                { text: 'Página ' + currentPage + ' de ' + pageCount, alignment: 'center', fontSize: 8 },
                                { text: 'Confidencial', alignment: 'right', fontSize: 8 }
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
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 5) {
                                return $(node).find('span').text().trim();
                            }
                            return data;
                        }
                    }
                }
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Habitaciones' + ' - ' + APP_NAME,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] },
                customize: function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                }
            }]
        }, {
            extend: 'colvis',
            text: 'Visualización de columnas'
        }],
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
            "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    });

    tabla.buttons().container().appendTo('#tablaHabitaciones_wrapper .col-md-6:eq(0)');

    // ── Filtro combinado del lado del cliente ────────────────────────────
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const $row = $(tabla.row(dataIndex).node());

        const estadoFiltro = $('#filtro-estado').val();
        if (estadoFiltro && estadoFiltro !== $row.data('estado')) {
            return false;
        }

        const tipoFiltro = $('#filtro-tipo').val();
        if (tipoFiltro && !data[1].includes(tipoFiltro)) {
            return false;
        }

        const pisoFiltro = $('#filtro-piso').val();
        if (pisoFiltro && !data[2].includes(pisoFiltro)) {
            return false;
        }

        const precioFiltro = parseFloat($('#filtro-precio').val()) || 0;
        const precioRow = parseFloat(data[4].replace(/[^\d.-]/g, '')) || 0;
        if (precioFiltro > 0 && precioRow < precioFiltro) {
            return false;
        }

        return true;
    });

    // ── Tarjetas de resumen como filtro de estado ───────────────────────
    function marcarTarjetaActiva(valor) {
        $('#resumen-estados .filtro-estado').each(function () {
            const activa = String($(this).attr('data-filtro-estado') || '') === valor;
            $(this).toggleClass('filtro-estado--activo', activa)
                .attr('aria-pressed', activa ? 'true' : 'false');
        });
    }

    function aplicarFiltroEstado(elemento) {
        const valor = String($(elemento).attr('data-filtro-estado') || '');
        $('#filtro-estado').val(valor);
        marcarTarjetaActiva(valor);
        tabla.draw();
    }

    $(document).on('click', '#resumen-estados .filtro-estado', function (e) {
        e.preventDefault();
        aplicarFiltroEstado(this);
    });

    // Las tarjetas son <a role="button">: Enter ya activa de forma nativa,
    // Espacio no. Se replica el comportamiento de un botón real.
    $(document).on('keydown', '#resumen-estados .filtro-estado', function (e) {
        if (e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            aplicarFiltroEstado(this);
        }
    });

    // ── Contadores por estado ───────────────────────────────────────────
    function actualizarContadores() {
        const conteo = { '': tabla.rows().count(), disponible: 0, ocupada: 0, limpieza: 0, mantenimiento: 0 };

        tabla.rows().every(function () {
            const estado = $(this.node()).data('estado');
            if (conteo[estado] !== undefined) {
                conteo[estado]++;
            }
        });

        $('[data-contador-estado]').each(function () {
            const clave = $(this).data('contador-estado') || '';
            if (conteo[clave] !== undefined) {
                $(this).text(conteo[clave]);
            }
        });
    }

    // ── Filtros avanzados ───────────────────────────────────────────────
    $('#btn-aplicar-filtros').on('click', function () {
        tabla.draw();
    });

    $('#btn-limpiar-filtros').on('click', function () {
        $('#filtro-estado').val('');
        $('#filtro-tipo').val('').trigger('change');
        $('#filtro-piso').val('').trigger('change');
        $('#filtro-precio').val('').removeClass('border-primary');
        marcarTarjetaActiva('');
        tabla.draw();
    });

    $('#filtro-precio').on('input', function () {
        $(this).toggleClass('border-primary', Boolean($(this).val()));
    });

    // El cambio de estado en línea lo maneja cambiar-estado-habitaciones.js
    // (enlace único a los botones .cambiar-estado).

    actualizarContadores();
});

/**
 * Inicialización común de DataTables con el menú "Reportes" (copiar, PDF, Excel, CSV, imprimir)
 * y "Visualización de columnas". Cada módulo solo declara lo que cambia: títulos, columnas
 * exportadas y formato de celdas.
 *
 * Requiere DataTables + Buttons + pdfmake cargados por footer.php, y APP_NAME / themeColor()
 * (common-utils.js / header.php).
 */

const DT_SUSTANTIVO_POR_DEFECTO = 'registros';

function dtIdioma(entidad) {
    return {
        sProcessing: 'Procesando...',
        sLengthMenu: 'Mostrar _MENU_ registros',
        sZeroRecords: 'No se encontraron resultados',
        sEmptyTable: 'Ningún dato disponible en esta tabla',
        sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ ' + entidad,
        sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 ' + entidad,
        sInfoFiltered: '(filtrado de un total de _MAX_ ' + entidad + ')',
        sInfoPostFix: '',
        sSearch: 'Buscar:',
        sUrl: '',
        sInfoThousands: ',',
        sLoadingRecords: 'Cargando...',
        oPaginate: {
            sFirst: 'Primero',
            sLast: 'Último',
            sNext: 'Siguiente',
            sPrevious: 'Anterior'
        },
        oAria: {
            sSortAscending: ': Activar para ordenar la columna de manera ascendente',
            sSortDescending: ': Activar para ordenar la columna de manera descendente'
        }
    };
}

function dtMenuLongitud(valores) {
    return [valores, valores.map(function (n) { return n === -1 ? 'Todos' : n; })];
}

/**
 * @param {string} selector Selector de la tabla (ej. '#tablaPisos')
 * @param {object} opciones
 * @param {string} opciones.titulo Título del reporte (PDF, Excel, impresión); el PDF lo muestra en mayúsculas
 * @param {string} opciones.archivo Prefijo del nombre del PDF; se le añade la fecha
 * @param {string} opciones.subtitulo Línea bajo el título del PDF
 * @param {string} opciones.descripcionExcel Texto superior del Excel
 * @param {string} opciones.entidad Sustantivo plural para el pie de la tabla ("pisos")
 * @param {number[]} opciones.columnas Índices de columna exportados
 * @param {number[]} [opciones.columnasImpresion] Columnas de la impresión (por defecto, las exportadas)
 * @param {number[]} [opciones.columnasTexto] Columnas con badge/span: se exporta solo su texto visible
 * @param {function} [opciones.formatoCelda] (data, row, column, node) => valor | undefined, para Excel
 * @param {function} [opciones.personalizarPdf] (doc) => void, tras el diseño común (la tabla es doc.content[3])
 * @param {function} [opciones.personalizarImpresion] (win) => void, reemplaza el estilo base de impresión
 * @param {string} [opciones.orientacion='landscape'] Orientación del PDF
 * @param {number} [opciones.tamanoLetra=10] Tamaño base de letra del PDF
 * @param {number} [opciones.longitudPagina=10]
 * @param {number[]} [opciones.menuLongitud=[5, 10, 25, 50, -1]]
 * @param {string} [opciones.textoReportes='Reportes']
 * @param {string} [opciones.textoColumnas='Visualización de columnas']
 * @param {object} [opciones.dt] Opciones extra de DataTables (columnDefs, order, drawCallback...); se mezclan en profundidad
 * @returns {object} La instancia de DataTable
 */
function initDataTable(selector, opciones) {
    const o = Object.assign({
        orientacion: 'landscape',
        tamanoLetra: 10,
        longitudPagina: 10,
        menuLongitud: [5, 10, 25, 50, -1],
        textoReportes: 'Reportes',
        textoColumnas: 'Visualización de columnas',
        entidad: DT_SUSTANTIVO_POR_DEFECTO,
        columnasTexto: [],
        dt: {}
    }, opciones);

    const columnasImpresion = o.columnasImpresion || o.columnas;
    const tituloCompleto = o.titulo + ' - ' + APP_NAME;

    const formatoExcel = function (data, row, column, node) {
        if (o.columnasTexto.indexOf(column) !== -1) {
            return $(node).find('span').text().trim() || data;
        }
        if (o.formatoCelda) {
            const valor = o.formatoCelda(data, row, column, node);
            if (valor !== undefined) {
                return valor;
            }
        }
        return data;
    };

    const base = {
        responsive: true,
        autoWidth: false,
        buttons: [{
            extend: 'collection',
            text: o.textoReportes,
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: { columns: o.columnas }
            }, {
                extend: 'pdf',
                title: tituloCompleto,
                filename: o.archivo + '_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                orientation: o.orientacion,
                exportOptions: { columns: o.columnas },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = o.tamanoLetra;
                    doc.styles.tableHeader.fontSize = o.tamanoLetra + 1;
                    doc.styles.tableHeader.fillColor = themeColor('dark');
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: o.titulo.toUpperCase() + ' - ' + APP_NAME.toUpperCase(),
                        style: { fontSize: 16, alignment: 'center', bold: true, margin: [0, 10, 0, 10] }
                    });
                    doc.content.splice(1, 0, {
                        text: o.subtitulo,
                        style: { fontSize: 11, alignment: 'center', italic: true, margin: [0, 0, 0, 10] }
                    });
                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: { fontSize: 9, alignment: 'right', margin: [0, 0, 0, 10] }
                    });

                    if (o.personalizarPdf) {
                        o.personalizarPdf(doc);
                    }

                    doc.footer = function (paginaActual, totalPaginas) {
                        return {
                            columns: [
                                { text: 'Sistema de Gestión - ' + APP_NAME, alignment: 'left', fontSize: 8 },
                                { text: 'Página ' + paginaActual + ' de ' + totalPaginas, alignment: 'center', fontSize: 8 },
                                { text: 'Confidencial', alignment: 'right', fontSize: 8 }
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: tituloCompleto,
                messageTop: o.descripcionExcel,
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: o.columnas,
                    format: { body: formatoExcel }
                }
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: { columns: o.columnas }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: tituloCompleto,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: { columns: columnasImpresion },
                customize: o.personalizarImpresion || function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                }
            }]
        }, {
            extend: 'colvis',
            text: o.textoColumnas
        }],
        pageLength: o.longitudPagina,
        lengthMenu: dtMenuLongitud(o.menuLongitud),
        language: dtIdioma(o.entidad)
    };

    const tabla = $(selector).DataTable($.extend(true, base, o.dt));
    tabla.buttons().container().appendTo(selector + '_wrapper .col-md-6:eq(0)');
    return tabla;
}

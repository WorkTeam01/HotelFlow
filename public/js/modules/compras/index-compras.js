$(document).ready(function () {
    // Inicializar DataTable para compras
    initDataTable('#tablaCompras', {
        titulo: 'Registro de Compras',
        archivo: 'compras_registro',
        subtitulo: 'Historial de compras del sistema',
        descripcionExcel: 'Historial de compras del sistema',
        entidad: 'compras',
        columnas: [0, 1, 2, 3, 4, 5],
        columnasTexto: [5],
        formatoCelda: function (data, row, column, node) {
            // Total: separador decimal con punto
            if (column === 4) {
                return data.replace(/\./g, '').replace(',', '.');
            }
        },
        personalizarPdf: function (doc) {
            doc.content[3].table.body.forEach(function (row) {
                row[4].text = row[4].text.replace(/\./g, ',');
            });
        },
        personalizarImpresion: function (win) {
            $(win.document.body).find('table')
                .addClass('table-striped')
                .css('font-size', '12px');

            $(win.document.body).prepend(
                '<h3 class="text-center">Registro de Compras</h3>' +
                '<p class="text-center">' + APP_NAME + '</p>' +
                '<hr>'
            );
        },
        menuLongitud: [5, 10, 25, 50, 100],
        textoColumnas: 'Columnas visibles',
        dt: {
            language: {
                sZeroRecords: 'No se encontraron compras',
                sEmptyTable: 'No hay compras registradas',
                sInfo: 'Mostrando compras del _START_ al _END_ de un total de _TOTAL_',
                sInfoEmpty: 'Mostrando compras del 0 al 0 de un total de 0'
            },
            columnDefs: [
                { "type": "num", "targets": 0 }, // Columna Nro
                { "type": "string", "targets": 1 }, // Código
                { "type": "date", "targets": 2 }, // Fecha
                { "type": "string", "targets": 3 }, // Usuario
                { "type": "num-fmt", "targets": 4 }, // Total
                { "type": "string", "targets": 5 }, // Estado
                { "orderable": false, "targets": 6 } // Acciones
            ]
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Delegado: las filas de otras páginas del DataTable no existen al cargar el documento
    $(document).on('click', '.btn-cambiar-estado', function() {
        const compraId = this.getAttribute('data-id');
        const accion = this.getAttribute('data-accion');
        const tituloCompra = this.getAttribute('data-titulo');

        let tituloAlerta, textoAlerta, confirmButtonText, confirmButtonColor;

        if (accion === 'completar') {
            tituloAlerta = `¿Marcar compra ${tituloCompra} como completada?`;
            textoAlerta = 'La compra se marcará como finalizada y no podrá ser modificada.';
            confirmButtonText = 'Sí, completar';
            confirmButtonColor = themeColor('success');
        } else if (accion === 'cancelar') {
            tituloAlerta = `¿Cancelar compra ${tituloCompra}?`;
            textoAlerta = 'La compra será cancelada y el stock de productos será revertido.';
            confirmButtonText = 'Sí, cancelar';
            confirmButtonColor = themeColor('danger');
        }

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Agregamos un parámetro adicional para evitar caché
                mostrarCarga('Procesando...', 'Actualizando la compra');
                window.location.href = `${BASE_URL}controllers/compras/cambiar_estado_compra.php?id=${compraId}&accion=${accion}&t=${Date.now()}&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });
});
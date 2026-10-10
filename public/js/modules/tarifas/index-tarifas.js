$(document).ready(function () {
    // Inicializar DataTable para tarifas
    initDataTable('#tablaTarifas', {
        titulo: 'Listado de Tarifas',
        archivo: 'tarifas',
        subtitulo: 'Tarifas registradas en el sistema',
        descripcionExcel: 'Registro de tarifas del sistema',
        entidad: 'tarifas',
        columnas: [0, 1, 2, 3, 4, 5],
        formatoCelda: function (data, row, column) {
            // Precio: solo el número
            if (column === 4) {
                return data.replace(/[^\d.-]/g, '');
            }
        },
        personalizarPdf: function (doc) {
            doc.content[3].table.body.forEach(function (row) {
                if (row[4]) row[4].text = row[4].text.replace(/[^\d.-]/g, '');
            });
        },
        personalizarImpresion: function (win) {
            $(win.document.body).find('table')
                .addClass('table-striped')
                .css('font-size', '12px');

            $(win.document.body).prepend(
                '<h3>Listado de Tarifas - ' + APP_NAME + '</h3>' +
                '<p>Reporte generado el ' + new Date().toLocaleDateString('es-BO') + '</p>'
            );
        },
        longitudPagina: 5,
        menuLongitud: [3, 5, 10, 25, 50],
        dt: {
            columnDefs: [
                {
                    "targets": [4], // Columna de precio
                    "render": function(data, type, row) {
                        if (type === 'display' || type === 'filter') {
                            return '$ ' + parseFloat(data).toFixed(2);
                        }
                        return data;
                    }
                },
                {
                    "targets": [3], // Columna de duración
                    "className": "text-center"
                },
                {
                    "targets": [6], // Columna de acciones
                    "orderable": false,
                    "searchable": false
                }
            ],
            order: [[0, 'asc']] // Ordenar por la primera columna (Nro)
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

    botonesCambiarEstado.forEach(boton => {
        boton.addEventListener('click', function() {
            const tarifaId = this.dataset.id;
            const estadoActual = this.dataset.estado;
            const nombreTarifa = this.dataset.nombre;

            const tituloAlerta = estadoActual == 1 ? `¿Desactivar ${escapeHtml(nombreTarifa)}?` : `¿Activar ${escapeHtml(nombreTarifa)}?`;
            const textoAlerta = estadoActual == 1 ? 'La tarifa no estará disponible para asignación.' : 'La tarifa estará disponible para asignación.';
            const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
            const cancelButtonText = 'Cancelar';

            Swal.fire({
                title: tituloAlerta,
                text: textoAlerta,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
                cancelButtonColor: themeColor('secondary'),
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCarga('Procesando...', 'Actualizando el estado de la tarifa');
                    window.location.href = `${BASE_URL}controllers/tarifas/desactivar_tarifa.php?id=${tarifaId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
                }
            });
        });
    });
});
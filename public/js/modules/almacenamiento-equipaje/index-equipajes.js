$(document).ready(function () {
    // Inicializar DataTable
    initDataTable('#tablaEquipajes', {
        titulo: 'Almacenamiento de Equipaje',
        archivo: 'equipaje_almacenamiento',
        subtitulo: 'Registro de equipajes almacenados',
        descripcionExcel: 'Registro de equipajes almacenados',
        entidad: 'equipajes',
        columnas: [0, 1, 2, 3, 4, 5, 6],
        formatoCelda: function (data, row, column, node) {
            // Estado: solo el texto del badge
            if (column === 6) {
                return $(node).find('.badge').text() || data;
            }
        },
        personalizarImpresion: function (win) {
            $(win.document.body).find('table')
                .addClass('table-striped')
                .css('font-size', '11px');
        },
        tamanoLetra: 9,
        dt: {
            // En móvil se colapsan primero Nro/Piezas/Fecha/Monto; Acciones y Estado quedan a la vista
            columnDefs: [
                { responsivePriority: 1, targets: 7 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 6 },
                { responsivePriority: 4, targets: 2 }
            ]
        }
    });

    // Inicializar Select2 para el filtro de clientes
    initializeSelect2();
});

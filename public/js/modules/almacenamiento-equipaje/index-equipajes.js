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
        tamanoLetra: 9
    });

    // Manejar cambios de estado con SweetAlert2
    $(document).on('click', '.cambiar-estado', function (e) {
        e.preventDefault();

        var url = $(this).data('url');
        var accion = $(this).data('accion');
        var icono = $(this).data('icono');

        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción marcará el equipaje como " + accion,
            icon: icono,
            showCancelButton: true,
            confirmButtonColor: themeColor('primary'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Inicializar Select2 para el filtro de clientes
    initializeSelect2();

    // Limpiar fechas cuando se hace clic en limpiar filtros
    $('.btn-secondary').on('click', function () {
        $('#fecha_inicio, #fecha_fin').val('');
    });
});
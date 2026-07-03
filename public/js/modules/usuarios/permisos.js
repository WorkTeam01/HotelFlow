// Modificación de permisos.js
$(document).ready(function () {
    // Función para seleccionar permisos según el cargo
    function seleccionarPermisosPorCargo(cargo) {
        // Primero desmarcamos todos los permisos
        $('.permiso-checkbox').prop('checked', false);

        // Luego marcamos los permisos correspondientes al cargo seleccionado
        $('.permiso-checkbox[data-cargo="' + cargo + '"]').prop('checked', true);

        // Activamos la pestaña correspondiente al cargo
        $('#' + cargo.toLowerCase().replace(' ', '-') + '-tab').tab('show');
    }

    // Seleccionar/deseleccionar todos los permisos visibles (solo en la pestaña activa)
    $('#selectAllPermissions').click(function () {
        $('.tab-pane.active .permiso-checkbox').prop('checked', true);
    });

    $('#deselectAllPermissions').click(function () {
        $('.tab-pane.active .permiso-checkbox').prop('checked', false);
    });

    // Evento al cambiar el cargo
    $('#cargo').change(function () {
        var cargoSeleccionado = $(this).val();
        if (cargoSeleccionado) {
            seleccionarPermisosPorCargo(cargoSeleccionado);
        }
    });

    // Inicializar con el cargo actual (para edición)
    var cargoInicial = $('#cargo').val();
    if (cargoInicial) {
        seleccionarPermisosPorCargo(cargoInicial);
    }
});
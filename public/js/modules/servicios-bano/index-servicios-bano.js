document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable
    try {
        initDataTable('#tablaServicios', {
            titulo: 'Servicios de Baños',
            archivo: 'servicios_banos',
            subtitulo: 'Servicios registrados',
            descripcionExcel: 'Registro de servicios de baños del sistema',
            entidad: 'servicios',
            columnas: [0, 1, 2, 3, 4, 5, 6, 7],
            columnasTexto: [7]
        });

    } catch (e) {
        console.error('Error al inicializar DataTable:', e);
    }
});

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
            columnasTexto: [7],
            dt: {
                // Acciones y Estado siempre visibles: sin responsivePriority el "+" las esconde a 390px
                columnDefs: [
                    { responsivePriority: 1, targets: 8 },
                    { responsivePriority: 2, targets: 7 },
                    { responsivePriority: 3, targets: 1 },
                    { responsivePriority: 4, targets: 4 },
                    { responsivePriority: 10, targets: [0, 2, 3, 5, 6] }
                ]
            }
        });

    } catch (e) {
        console.error('Error al inicializar DataTable:', e);
    }
});

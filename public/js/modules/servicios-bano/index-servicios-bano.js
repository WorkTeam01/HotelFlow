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

    // Función para mostrar diálogo de confirmación
    function mostrarDialogoConfirmacion(id, estado, accion) {
        let tituloAlerta = '';
        let textoAlerta = '';
        let confirmButtonText = '';
        let confirmButtonColor = '';

        if (accion === 'iniciado') {
            tituloAlerta = '¿Reactivar este servicio?';
            textoAlerta = 'El servicio se marcará como iniciado.';
            confirmButtonText = 'Sí, reactivar';
            confirmButtonColor = themeColor('success');
        } else {
            tituloAlerta = '¿Cancelar este servicio?';
            textoAlerta = 'El servicio se marcará como cancelado.';
            confirmButtonText = 'Sí, cancelar';
            confirmButtonColor = themeColor('danger');
        }

        // Asegurarnos que Swal esté definido
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 no está disponible');
            if (confirm(textoAlerta)) {
                mostrarCarga('Procesando...', 'Actualizando el estado del servicio');
                window.location.href = `${BASE_URL}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&nuevo_estado=${accion}&csrf_token=${CSRF_TOKEN}`;
            }
            return;
        }

        // Usar setTimeout para evitar problemas
        setTimeout(function () {
            try {
                Swal.fire({
                    title: tituloAlerta,
                    text: textoAlerta,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: themeColor('secondary'),
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: 'No, volver',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        mostrarCarga('Procesando...', 'Actualizando el estado del servicio');
                        window.location.href = `${BASE_URL}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&nuevo_estado=${accion}&csrf_token=${CSRF_TOKEN}`;
                    }
                });
            } catch (error) {
                console.error('Error al mostrar SweetAlert:', error);
                // Fallback al diálogo nativo en caso de error
                if (confirm(textoAlerta)) {
                    mostrarCarga('Procesando...', 'Actualizando el estado del servicio');
                    window.location.href = `${BASE_URL}controllers/servicios-bano/cambiar_estado_servicio.php?id=${id}&nuevo_estado=${accion}&csrf_token=${CSRF_TOKEN}`;
                }
            }
        }, 100);
    }

    // Manejar cambio de estado de servicio
    // Usar delegación de eventos para mejor compatibilidad
    $(document).on('click', '.btn-cambiar-estado', function (e) {
        e.preventDefault();

        const servicioId = $(this).data('id');
        const estadoActual = $(this).data('estado');
        const accion = $(this).data('accion');

        mostrarDialogoConfirmacion(servicioId, estadoActual, accion);
    });
});
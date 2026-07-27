/**
 * Función para cambiar el estado del tipo de habitación
 * @param {number} tipoId - ID del tipo
 * @param {number} estadoActual - Estado actual (1: activo, 0: inactivo)
 */
function cambiarEstado(tipoId, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const accion = estadoActual == 1 ? 'desactivar' : 'activar';
    const titulo = estadoActual == 1 ? '¿Desactivar Tipo de Habitación?' : '¿Activar Tipo de Habitación?';
    const texto = estadoActual == 1 ?
        'Este tipo de habitación no podrá ser seleccionado para nuevas habitaciones.' :
        'Este tipo de habitación podrá ser utilizado nuevamente.';

    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: estadoActual == 1 ? '#d33' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redireccionar a la URL de cambio de estado
            window.location.href = `${BASE_URL}controllers/tipohabitaciones/desactivar_tipo_habitacion.php?id=${tipoId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
        }
    });
}

/**
 * Función para cambiar el estado del cliente (activar/desactivar)
 * @param {number} clienteId - ID del cliente
 * @param {number} estadoActual - Estado actual (1: activo, 0: inactivo)
 */
function cambiarEstado(clienteId, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const accion = estadoActual == 1 ? 'desactivar' : 'activar';
    const titulo = estadoActual == 1 ? '¿Desactivar Cliente?' : '¿Activar Cliente?';
    const texto = estadoActual == 1 ?
        'El cliente no podrá realizar reservas mientras esté desactivado.' :
        'El cliente podrá realizar reservas nuevamente.';

    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redireccionar a la URL de cambio de estado
            window.location.href = `${BASE_URL}controllers/personas/desactivar_persona.php?id=${clienteId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
        }
    });
}

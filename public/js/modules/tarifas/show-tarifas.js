/**
 * Función para cambiar el estado de la tarifa (activar/desactivar)
 * @param {number} tarifaId - ID de la tarifa
 * @param {number} estadoActual - Estado actual (1: activo, 0: inactivo)
 */
function cambiarEstado(tarifaId, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const accion = estadoActual == 1 ? 'desactivar' : 'activar';
    const titulo = estadoActual == 1 ? '¿Desactivar Tarifa?' : '¿Activar Tarifa?';
    const texto = estadoActual == 1 ?
        'Esta tarifa no podrá ser seleccionada para nuevas reservas.' :
        'Esta tarifa podrá ser utilizada nuevamente.';

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
            window.location.href = `${BASE_URL}controllers/tarifas/desactivar_tarifa.php?id=${tarifaId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
        }
    });
}

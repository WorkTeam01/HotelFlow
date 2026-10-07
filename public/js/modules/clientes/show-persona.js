/**
 * Cambio de estado del cliente (activar/desactivar). El botón expone data-id y data-estado
 * (1: activo, 0: inactivo).
 */
document.addEventListener('click', function (e) {
    const boton = e.target.closest('.cambiar-estado-persona');
    if (boton) {
        cambiarEstado(boton.dataset.id, parseInt(boton.dataset.estado, 10));
    }
});

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
        confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
        cancelButtonColor: themeColor('secondary'),
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarCarga('Procesando...', 'Actualizando el estado del cliente');
            window.location.href = `${BASE_URL}controllers/personas/desactivar_persona.php?id=${clienteId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
        }
    });
}

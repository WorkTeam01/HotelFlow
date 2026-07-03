document.addEventListener('DOMContentLoaded', function () {
    // Manejar cambio de estado de servicio
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');
    botonesCambiarEstado.forEach(boton => {
        boton.addEventListener('click', function () {
            const servicioId = this.dataset.id;
            const estadoActual = this.dataset.estado;
            const accion = this.dataset.accion;

            let tituloAlerta = '';
            let textoAlerta = '';
            let confirmButtonText = '';
            let confirmButtonColor = '';

            if (accion === '1' || accion === 1) {
                tituloAlerta = '¿Activar este servicio?';
                textoAlerta = 'El servicio se marcará como activo.';
                confirmButtonText = 'Sí, activar';
                confirmButtonColor = '#28a745';
            } else {
                tituloAlerta = '¿Desactivar este servicio?';
                textoAlerta = 'El servicio se marcará como inactivo.';
                confirmButtonText = 'Sí, desactivar';
                confirmButtonColor = '#dc3545';
            }

            Swal.fire({
                title: tituloAlerta,
                text: textoAlerta,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'No, volver'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${baseUrl}controllers/servicios-bano/cambiar_estado_servicio.php?id=${servicioId}&estado_actual=${estadoActual}&nuevo_estado=${accion}`;
                }
            });
        });
    });
});
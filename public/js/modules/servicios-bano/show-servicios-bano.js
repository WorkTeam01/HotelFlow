document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-cambiar-estado').forEach(boton => {
        boton.addEventListener('click', function () {
            const servicioId = this.dataset.id;
            const accion = this.dataset.accion;
            const nombreServicio = this.dataset.nombre || 'este servicio';

            const reactivar = accion === 'iniciado';

            const tituloAlerta = reactivar
                ? `¿Reactivar ${nombreServicio}?`
                : `¿Cancelar ${nombreServicio}?`;
            const textoAlerta = reactivar
                ? 'El servicio se marcará como iniciado.'
                : 'El servicio se marcará como cancelado.';
            const confirmButtonText = reactivar ? 'Sí, reactivar' : 'Sí, cancelar';
            const confirmButtonColor = reactivar ? themeColor('success') : themeColor('danger');

            const url = `${BASE_URL}controllers/servicios-bano/cambiar_estado_servicio.php`
                + `?id=${servicioId}&nuevo_estado=${encodeURIComponent(accion)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`;

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
                    window.location.href = url;
                }
            });
        });
    });
});

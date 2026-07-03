/**
 * Script para el registro rápido de servicios de baños
 */

document.addEventListener('DOMContentLoaded', function () {
    // Manejar clic en botones de registro rápido
    document.querySelectorAll('.btn-registro-rapido').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const idBano = this.dataset.id;
            const nombreBano = this.dataset.nombre;
            const precioBano = this.dataset.precio;

            // Mostrar confirmación
            Swal.fire({
                title: 'Confirmar Servicio',
                html: `¿Registrar uso de baño <strong>${nombreBano}</strong>?<br>Precio: <strong>Bs. ${precioBano}</strong>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carga con toast
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                            Swal.showLoading();
                        }
                    });

                    Toast.fire({
                        icon: 'info',
                        title: 'Registrando servicio...'
                    });

                    // Deshabilitar botón mientras se procesa
                    this.disabled = true;

                    // Registrar el servicio
                    registrarServicioRapido(idBano, nombreBano, this);
                }
            });
        });
    });

    /**
     * Registra un servicio de baño de forma rápida
     * 
     * @param {number} idBano ID del baño
     * @param {string} nombreBano Nombre del baño para mostrar en la notificación
     * @param {HTMLElement} boton Elemento botón para manejar estado de carga
     */
    function registrarServicioRapido(idBano, boton) {
        $.ajax({
            url: baseUrl + 'controllers/servicios-bano/crear_servicio_rapido.php',
            type: 'POST',
            data: { idbano: idBano },
            dataType: 'json',
            success: function (response) {
                // Restaurar botón
                boton.disabled = false;

                if (response.success) {
                    // Mostrar toast de éxito
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });

                    Toast.fire({
                        icon: 'success',
                        title: 'Servicio registrado correctamente'
                    }).then(() => {
                        // Recargar la página para actualizar la lista
                        location.reload();
                    });
                } else {
                    // Mostrar error con toast
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });

                    Toast.fire({
                        icon: 'error',
                        title: response.message || 'No se pudo registrar el servicio'
                    });
                }
            },
            error: function () {
                // Restaurar botón
                boton.disabled = false;

                // Mostrar error con toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });

                Toast.fire({
                    icon: 'error',
                    title: 'Error de conexión con el servidor'
                });
            }
        });
    }
});
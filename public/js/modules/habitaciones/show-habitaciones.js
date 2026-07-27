/**
 * Script para la vista de detalle de habitación
 */

$(document).ready(function() {
    // Mostrar modal para cambiar estado
    $('#btnCambiarEstado').click(function() {
        $('#modalCambiarEstado').modal('show');
    });

    // Procesar cambio de estado
    $('#btnGuardarEstado').click(function() {
        const id = $('#id_habitacion').val();
        const estadoActual = $('#estado_actual').val();
        const nuevoEstado = $('#nuevo_estado').val();

        // Verificar que el estado haya cambiado
        if (estadoActual === nuevoEstado) {
            Swal.fire({
                icon: 'info',
                title: 'Sin cambios',
                text: 'No ha seleccionado un estado diferente.'
            });
            return;
        }

        // Confirmar cambio de estado
        Swal.fire({
            title: '¿Cambiar estado?',
            text: `¿Está seguro de cambiar el estado de la habitación de "${estadoActual}" a "${nuevoEstado}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Espere un momento mientras se actualiza el estado.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar solicitud AJAX
                $.ajax({
                    url: `${BASE_URL}controllers/habitaciones/cambiar_estado.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado: nuevoEstado,
                        csrf_token: CSRF_TOKEN
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Estado actualizado',
                                text: response.message
                            }).then(() => {
                                // Recargar la página para reflejar los cambios
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor'
                        });
                    }
                });
            }
        });
    });
});

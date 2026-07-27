/**
 * Script para el panel de habitaciones del dashboard de Recepcionista
 */
$(document).ready(function() {
    // Búsqueda de habitación
    $("#buscarHabitacion").on("keyup", function() {
        var value = $(this).val().toString();
        $(".habitacion-item").each(function() {
            var numero = $(this).data("numero").toString();
            $(this).toggle(numero.indexOf(value) > -1);
        });
    });

    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Búsqueda de baño
    $("#buscarBano").on("keyup", function() {
        var value = $(this).val().toString();
        $(".bano-item").each(function() {
            var numero = $(this).data("numero").toString();
            $(this).toggle(numero.indexOf(value) > -1);
        });
    });

    // Filtrado de habitaciones
    $(".filtro-habitacion").on("click", function() {
        // Actualizar botón activo
        $(".filtro-habitacion").removeClass("active");
        $(this).addClass("active");

        // Obtener el estado seleccionado
        var estado = $(this).data("estado");

        // Filtrar las tarjetas
        if (estado === "todos") {
            $(".habitacion-item").show();
        } else {
            $(".habitacion-item").hide();
            $(".habitacion-item[data-estado='" + estado + "']").show();
        }
    });

    // Manejar asignación de limpieza
    $('.btn-asignar-limpieza').on('click', function() {
        const id = $(this).data('id');
        window.location.href = `${BASE_URL}views/limpieza`;
    });

    // Manejar cambio de estado de asignaciones
    $('.cambiar-estado-asignacion').on('click', function() {
        const id = $(this).data('id');
        const estado = $(this).data('estado');

        Swal.fire({
            title: `¿Verificar esta limpieza?`,
            text: `Confirme que ha revisado y aprueba la limpieza realizada.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#dc3545',
            confirmButtonText: `Sí, verificar`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                const button = $(this);
                const originalText = button.html();
                button.html('<i class="fas fa-spinner fa-spin"></i>');
                button.prop('disabled', true);

                // Realizar la solicitud AJAX para cambiar el estado
                $.ajax({
                    url: `${BASE_URL}controllers/limpieza/cambiar_estado_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado: estado,
                        csrf_token: CSRF_TOKEN
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                // Recargar la página para reflejar los cambios
                                location.reload();
                            });
                        } else {
                            button.html(originalText);
                            button.prop('disabled', false);

                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error AJAX:", status, error);
                        button.html(originalText);
                        button.prop('disabled', false);

                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});

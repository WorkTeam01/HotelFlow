/**
 * Script para filtrado y cambio de estado en el dashboard de Limpieza
 */
$(document).ready(function() {
    // Búsqueda de habitación pendiente - corregido para números
    $("#buscarHabitacionPendiente").on("keyup", function() {
        var value = $(this).val().toString();
        $(".habitacion-card-pendiente").each(function() {
            var numero = $(this).data("numero").toString();
            $(this).toggle(numero.indexOf(value) > -1);
        });
    });

    // Búsqueda de habitación completada - corregido para números
    $("#buscarHabitacionCompletada").on("keyup", function() {
        var value = $(this).val().toString();
        $(".habitacion-card-completada").each(function() {
            var numero = $(this).data("numero").toString();
            $(this).toggle(numero.indexOf(value) > -1);
        });
    });

    // Filtrado por estado para pendientes
    $(".filtro-estado-pendiente").on("click", function() {
        // Actualizar botón activo
        $(".filtro-estado-pendiente").removeClass("active");
        $(this).addClass("active");

        // Obtener el estado seleccionado
        var estado = $(this).data("estado");

        // Filtrar las tarjetas
        if (estado === "todos") {
            $(".habitacion-card-pendiente").show();
        } else {
            $(".habitacion-card-pendiente").hide();
            $(".habitacion-card-pendiente[data-estado='" + estado + "']").show();
        }
    });

    // Manejar cambio de estado de asignaciones
    $('.cambiar-estado').on('click', function() {
        const id = $(this).data('id');
        const estado = $(this).data('estado');

        // Textos para diferentes estados
        const textos = {
            'pendiente': 'pendiente',
            'enprogreso': 'en progreso',
            'completada': 'completada',
            'verificada': 'verificada'
        };

        // Colores para diferentes estados
        const colores = {
            'pendiente': '#ffc107',
            'enprogreso': '#17a2b8',
            'completada': '#28a745',
            'verificada': '#6c757d'
        };

        Swal.fire({
            title: `¿Marcar como ${textos[estado]}?`,
            text: `La asignación se marcará como ${textos[estado]}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: colores[estado],
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, marcar como ${textos[estado]}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                const button = $(this);
                const originalText = button.html();
                button.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
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
                    error: function() {
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

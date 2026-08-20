$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Animación de las tarjetas al cargar la página
    $('.card').addClass('card-animation');

    function enviarMovimientoFolio(form, tituloExito) {
        const $form = $(form);
        const $boton = $form.find('button[type="submit"]');
        $boton.prop('disabled', true);

        $.ajax({
            url: $form.data('accion'),
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    $boton.prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo registrar el movimiento',
                        text: response.message
                    });
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                $boton.prop('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
            }
        });
    }

    $('.form-folio-pago').on('submit', function (e) {
        e.preventDefault();
        enviarMovimientoFolio(this, 'Pago registrado');
    });

    $('.form-folio-cargo').on('submit', function (e) {
        e.preventDefault();
        enviarMovimientoFolio(this, 'Cargo registrado');
    });

    $('#form-cambio-habitacion').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $boton = $form.find('button[type="submit"]');

        Swal.fire({
            title: '¿Confirmar cambio de habitación?',
            text: 'La habitación actual quedará en limpieza y la nueva pasará a ocupada.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $boton.prop('disabled', true);

            $.ajax({
                url: $form.data('accion'),
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        $boton.prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo cambiar de habitación',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    $boton.prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en la comunicación con el servidor'
                    });
                }
            });
        });
    });
});

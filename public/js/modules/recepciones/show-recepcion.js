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

    // Check-out: valida el saldo del folio antes de finalizar (reemplaza el link roto a checkout.php)
    $('#btn-checkout').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const id = $btn.data('id');
        const saldo = parseFloat($btn.data('saldo')) || 0;
        const endpoint = $btn.data('endpoint');
        const csrf = $btn.data('csrf');
        const cliente = $btn.data('cliente') || 'Huésped';
        const habitacion = $btn.data('habitacion') || '';

        function enviarCheckout(payload) {
            return $.ajax({
                url: endpoint,
                type: 'POST',
                dataType: 'json',
                data: Object.assign({ csrf_token: csrf, idrecepcion: id }, payload),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        }

        function manejarRespuesta(res) {
            if (res.success) {
                window.location.reload();
                return;
            }
            Swal.fire({ icon: 'error', title: 'No se pudo hacer el check-out', text: res.message });
        }

        if (saldo > 0) {
            Swal.fire({
                title: 'Cobrar saldo y hacer check-out',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cobrar y finalizar',
                cancelButtonText: 'Cancelar',
                html: '<div class="text-left">' +
                    '<p><strong>' + cliente + '</strong>' + (habitacion ? ' &middot; Hab. ' + habitacion : '') + '</p>' +
                    '<p>Saldo pendiente: <strong>Bs ' + saldo.toFixed(2) + '</strong></p>' +
                    '<div class="form-group"><label for="swal-checkout-metodo">Método de pago</label>' +
                    '<select id="swal-checkout-metodo" class="form-control">' +
                    '<option value="Efectivo">Efectivo</option><option value="QR">QR</option><option value="OTROS">Otros</option>' +
                    '</select></div>' +
                    '<div class="form-group"><label for="swal-checkout-monto">Monto recibido (Bs)</label>' +
                    '<input type="number" id="swal-checkout-monto" class="form-control" step="0.01" min="' + saldo.toFixed(2) + '" value="' + saldo.toFixed(2) + '"></div>' +
                    '</div>',
                preConfirm: function () {
                    const monto = parseFloat(document.getElementById('swal-checkout-monto').value);
                    const metodo = document.getElementById('swal-checkout-metodo').value;
                    if (isNaN(monto) || monto + 0.01 < saldo) {
                        Swal.showValidationMessage('El monto debe cubrir el saldo (Bs ' + saldo.toFixed(2) + ')');
                        return false;
                    }
                    return { monto: monto, metodopago: metodo };
                }
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $btn.addClass('disabled');
                enviarCheckout(result.value)
                    .done(manejarRespuesta)
                    .fail(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de comunicación con el servidor' }); })
                    .always(function () { $btn.removeClass('disabled'); });
            });
        } else {
            Swal.fire({
                title: '¿Confirmar check-out?',
                html: '<p><strong>' + cliente + '</strong>' + (habitacion ? ' &middot; Hab. ' + habitacion : '') + '</p><p>El folio está saldado.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $btn.addClass('disabled');
                enviarCheckout({})
                    .done(manejarRespuesta)
                    .fail(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de comunicación con el servidor' }); })
                    .always(function () { $btn.removeClass('disabled'); });
            });
        }
    });
});

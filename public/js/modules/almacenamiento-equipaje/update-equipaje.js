$(document).ready(function () {
    // Inicializar Select2 para el selector de clientes
    initializeSelect2();

    // Mostrar información del cliente cuando se selecciona
    function actualizarInfoCliente() {
        var selectedOption = $('#idcliente').find('option:selected');

        if (selectedOption.val()) {
            var tipodoc = selectedOption.attr('data-tipodoc') || '';
            var numdoc = selectedOption.attr('data-numdoc') || '';
            var telefono = selectedOption.attr('data-telefono') || '';
            var nombreCliente = $.trim(selectedOption.text());

            $('#cliente-tipodoc').text(tipodoc || 'N/A');
            $('#cliente-numdoc').text(numdoc || 'N/A');
            $('#cliente-telefono').text(telefono || 'N/A');
            $('#info-cliente').fadeIn();

            $('#resumen-cliente').text(nombreCliente);
        } else {
            $('#info-cliente').fadeOut();
            $('#resumen-cliente').text('No seleccionado');
        }
    }

    $('#idcliente').on('change', actualizarInfoCliente);

    // El cliente ya viene preseleccionado al cargar el formulario de edición
    if ($('#idcliente').val()) {
        actualizarInfoCliente();
    }

    // Actualizar resumen cuando cambia la descripción
    $('#descripcion').on('input', function () {
        var descripcion = $(this).val();
        $('#resumen-descripcion').text(descripcion ? descripcion : '-');
    });

    // Actualizar resumen cuando cambia el estado
    $('#estado').on('change', function () {
        var estadoValor = $(this).val();
        var estadoTexto = $(this).find('option:selected').text();

        $('#resumen-estado')
            .removeClass('badge-success badge-warning badge-danger badge-dark badge-secondary')
            .addClass('badge-' + $(this).find('option:selected').data('clase'))
            .text(estadoTexto);

        // 'retirado' no se edita desde este formulario
        if (estadoValor === 'retirado') {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: 'Para marcar como retirado, use las opciones de cambio de estado en la página de detalles',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#3085d6'
            });
            $(this).val($(this).data('original-value'));
            $('#resumen-estado')
                .text($(this).find('option:selected').text());
        }
    });

    // Validar formulario antes de enviar (solo campos editables: cliente, descripción, estado)
    $('#formActualizarEquipaje').on('submit', function (e) {
        e.preventDefault();

        var idCliente = $('#idcliente').val();

        if (!idCliente) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Debe seleccionar un cliente',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        var clienteNombre = $('#idcliente option:selected').text();
        var estado = $('#estado option:selected').text();
        var descripcion = $('#descripcion').val() || '-';

        var mensaje = `
            <div class="text-left">
                <p><strong><i class="fas fa-user"></i> Cliente:</strong> ${clienteNombre}</p>
                <p><strong><i class="fas fa-sticky-note"></i> Descripción:</strong> ${descripcion}</p>
                <p><strong><i class="fas fa-tag"></i> Estado:</strong> ${estado}</p>
            </div>
        `;

        Swal.fire({
            title: '¿Confirmar actualización?',
            html: mensaje,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Actualizando información del equipaje',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $(this)[0].submit();
            }
        });
    });

    // Guardar el valor original del estado
    $('#estado').data('original-value', $('#estado').val());
});

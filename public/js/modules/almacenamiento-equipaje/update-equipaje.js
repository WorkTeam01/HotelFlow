$(document).ready(function () {
    // Inicializar Select2 para el selector de clientes
    initializeSelect2();

    // Mostrar información del cliente cuando se selecciona
    $('#idcliente').on('change', function () {
        var selectedOption = $(this).find('option:selected');

        if (selectedOption.val()) {
            var tipodoc = selectedOption.data('tipodoc');
            var numdoc = selectedOption.data('numdoc');
            var telefono = selectedOption.data('telefono');
            var nombreCliente = selectedOption.text();

            $('#cliente-tipodoc').text(tipodoc || 'N/A');
            $('#cliente-numdoc').text(numdoc || 'N/A');
            $('#cliente-telefono').text(telefono || 'N/A');

            // Actualizar resumen
            $('#resumen-cliente').text(nombreCliente);
        } else {
            $('#resumen-cliente').text('No seleccionado');
        }

        actualizarResumen();
    });

    // Actualizar monto cuando cambia el tipo de equipaje o cantidad
    $('#idpequipaje, #cantidad_piezas').on('change', function () {
        calcularMonto();
        actualizarResumen();
    });

    // Actualizar resumen cuando cambia la descripción
    $('#descripcion').on('input', function () {
        actualizarResumen();
    });

    // Actualizar resumen cuando cambia la fecha
    $('#fechaentrada').on('change', function () {
        actualizarResumen();
    });

    // Actualizar resumen cuando cambia el estado
    $('#estado').on('change', function () {
        var estadoValor = $(this).val();
        var estadoTexto = $(this).find('option:selected').text();

        // Actualizar el badge del estado en el resumen
        $('#resumen-estado').removeClass('badge-warning badge-danger badge-dark');

        switch (estadoValor) {
            case 'almacenado':
                $('#resumen-estado').addClass('badge-warning');
                break;
            case 'perdido':
                $('#resumen-estado').addClass('badge-danger');
                break;
            case 'dañado':
                $('#resumen-estado').addClass('badge-dark');
                break;
        }

        $('#resumen-estado').text(estadoTexto);

        // Verificar si se intenta seleccionar "retirado"
        if (estadoValor === 'retirado') {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: 'Para marcar como retirado, use las opciones de cambio de estado en la página de detalles',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#3085d6'
            });
            // Revertir la selección
            $(this).val($(this).data('original-value'));
            $('#resumen-estado').text($(this).find('option:selected').text());
        }
    });

    // Función para calcular el monto total
    function calcularMonto() {
        var selectedOption = $('#idpequipaje').find('option:selected');
        var precioBase = parseFloat(selectedOption.data('precio')) || 0;
        var cantidad = parseInt($('#cantidad_piezas').val()) || 1;

        var montoTotal = precioBase * cantidad;
        $('#monto').val(montoTotal.toFixed(2));

        // Actualizar el resumen
        $('#resumen-tipo').text(selectedOption.text() || 'No seleccionado');
        $('#resumen-cantidad').text(cantidad);
        $('#resumen-monto').text('Bs. ' + montoTotal.toFixed(2));
    }

    // Función para actualizar el resumen
    function actualizarResumen() {
        // Cliente - ya se actualiza en el cambio de cliente

        // Descripción
        var descripcion = $('#descripcion').val();
        $('#resumen-descripcion').text(descripcion ? descripcion : '-');

        // Fecha de entrada
        var fechaInput = $('#fechaentrada').val();
        if (fechaInput) {
            var fecha = new Date(fechaInput);
            var dia = fecha.getDate().toString().padStart(2, '0');
            var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = fecha.getHours().toString().padStart(2, '0');
            var minutos = fecha.getMinutes().toString().padStart(2, '0');

            $('#resumen-fecha').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
        }
    }

    // Validar formulario antes de enviar
    $('#formActualizarEquipaje').on('submit', function (e) {
        e.preventDefault();

        var monto = parseFloat($('#monto').val()) || 0;
        var cantidad = parseInt($('#cantidad_piezas').val()) || 0;
        var idCliente = $('#idcliente').val();
        var idPEquipaje = $('#idpequipaje').val();

        // Validar todos los campos
        let errores = [];

        if (!idCliente) {
            errores.push('Debe seleccionar un cliente');
        }

        if (!idPEquipaje) {
            errores.push('Debe seleccionar un tipo de equipaje');
        }

        if (cantidad < 1 || cantidad > 50) {
            errores.push('La cantidad de piezas debe estar entre 1 y 50');
        }

        if (monto <= 0) {
            errores.push('El monto debe ser mayor que cero');
        }

        // Mostrar errores si existen
        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>'),
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        // Confirmar la actualización
        var clienteNombre = $('#idcliente option:selected').text();
        var tipoEquipaje = $('#idpequipaje option:selected').text();
        var estado = $('#estado option:selected').text();

        var mensaje = `
            <div class="text-left">
                <p><strong><i class="fas fa-user"></i> Cliente:</strong> ${clienteNombre}</p>
                <p><strong><i class="fas fa-luggage-cart"></i> Tipo de equipaje:</strong> ${tipoEquipaje}</p>
                <p><strong><i class="fas fa-box"></i> Cantidad:</strong> ${cantidad} pieza(s)</p>
                <p><strong><i class="fas fa-tag"></i> Estado:</strong> ${estado}</p>
                <p><strong><i class="fas fa-money-bill"></i> Monto:</strong> Bs. ${monto.toFixed(2)}</p>
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
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Actualizando información del equipaje',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario
                $(this)[0].submit();
            }
        });
    });

    // Guardar el valor original del estado
    $('#estado').data('original-value', $('#estado').val());

    // Calcular monto inicial
    calcularMonto();

    // Inicializar el formulario con los valores actuales
    actualizarResumen();
});
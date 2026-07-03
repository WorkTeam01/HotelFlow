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
            $('#info-cliente').fadeIn();

            // Actualizar resumen
            $('#resumen-cliente').text(nombreCliente);
        } else {
            $('#info-cliente').fadeOut();
            $('#resumen-cliente').text('No seleccionado');
        }

        actualizarResumen();
    });

    // Actualizar monto cuando cambia el tipo de equipaje o cantidad
    $('#idpequipaje, #cantidad_piezas').on('change', function () {
        calcularMonto();
        actualizarResumen();
    });

    // También calcular cuando se carga la página si ya hay valores
    if ($('#idpequipaje').val() && $('#cantidad_piezas').val()) {
        calcularMonto();
    }

    // Actualizar resumen cuando cambia la descripción
    $('#descripcion').on('input', function () {
        actualizarResumen();
    });

    // Actualizar resumen cuando cambia la fecha
    $('#fechaentrada').on('change', function () {
        actualizarResumen();
    });

    // Función para calcular el monto total
    function calcularMonto() {
        var selectedOption = $('#idpequipaje').find('option:selected');
        var precioBase = parseFloat(selectedOption.data('precio')) || 0;
        var cantidad = parseInt($('#cantidad_piezas').val()) || 1;

        var montoTotal = precioBase * cantidad;
        $('#monto').val(montoTotal.toFixed(2));

        // Actualizar resumen
        $('#resumen-monto').text('Bs. ' + montoTotal.toFixed(2));
    }

    // Función para actualizar el resumen
    function actualizarResumen() {
        // Tipo de equipaje
        var tipoEquipaje = $('#idpequipaje option:selected').text();
        $('#resumen-tipo').text(tipoEquipaje !== '' ? tipoEquipaje : 'No seleccionado');

        // Cantidad
        var cantidad = parseInt($('#cantidad_piezas').val()) || 1;
        $('#resumen-cantidad').text(cantidad);

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

        // Monto
        var monto = parseFloat($('#monto').val()) || 0;
        $('#resumen-monto').text(`Bs. ${monto.toFixed(2)}`);
    }

    // Validar formulario antes de enviar
    $('#formRegistroEquipaje').on('submit', function (e) {
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

        // Confirmar el registro
        var clienteNombre = $('#idcliente option:selected').text();
        var tipoEquipaje = $('#idpequipaje option:selected').text();

        var mensaje = `
            <div class="text-left">
                <p><strong><i class="fas fa-user"></i> Cliente:</strong> ${clienteNombre}</p>
                <p><strong><i class="fas fa-luggage-cart"></i> Tipo de equipaje:</strong> ${tipoEquipaje}</p>
                <p><strong><i class="fas fa-box"></i> Cantidad:</strong> ${cantidad} pieza(s)</p>
                <p><strong><i class="fas fa-money-bill"></i> Monto total:</strong> Bs. ${monto.toFixed(2)}</p>
                <p><strong><i class="fas fa-ticket-alt"></i> Código de ticket:</strong> ${$('#codigo_ticket').val()}</p>
            </div>
        `;

        Swal.fire({
            title: '¿Confirmar registro?',
            html: mensaje,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Registrando almacenamiento de equipaje',
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

    // Establecer la fecha y hora actual por defecto si no se ha establecido
    if (!$('#fechaentrada').val()) {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');

        var datetime = `${year}-${month}-${day}T${hours}:${minutes}`;
        $('#fechaentrada').val(datetime);
    }

    // Enfocar el selector de cliente al cargar la página
    setTimeout(function () {
        $('#idcliente').select2('open');
    }, 500);

    // Inicializar el resumen
    actualizarResumen();
});
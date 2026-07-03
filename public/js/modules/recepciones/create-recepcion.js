/**
 * JavaScript del módulo recepción/create
 * Maneja tanto la selección de habitación como el formulario de reserva
 * Versión simplificada sin búsqueda
 * 
 * @module RecepcionModule
 * @version 1.0
 */

// Namespace para evitar conflictos
window.RecepcionModule = window.RecepcionModule || {};

window.RecepcionModule.Create = (function ($) {
    'use strict';

    // Configuración del módulo
    var config = {
        selectors: {
            // Selección de habitación
            habitacionCards: '.module-recepcion-create .habitacion-selectable',
            filterTipoButtons: '.module-recepcion-create .btn-filter-tipo',
            pisoButtons: '.module-recepcion-create .btn-piso',
            pisoSections: '.module-recepcion-create .piso-section',
            btnSelectRoom: '.btn-select-room',
            totalDisponibles: '#total-disponibles',

            // Formulario de reserva
            form: '#formCheckin',
            clienteSelect: '#idcliente',
            tarifaSelect: '#idtarifa',
            fechaEntrada: '#fechaentrada',
            fechaSalida: '#fechasalida_prevista',
            montoTotal: '#montototal',
            montoPagado: '#montopagado',
            pagoMetodo: '#pago_metodo',
            pagoRecibido: '#pago_recibido',
            cambio: '#cambio',
            estado: '#estado',
            observaciones: '#observaciones',
            btnSubmit: '#btn-submit',

            // Modal
            modal: '#modalConfirmRoom',
            modalRoomNumber: '#modal-room-number',
            modalRoomType: '#modal-room-type',
            modalRoomPrice: '#modal-room-price',
            btnConfirmRoom: '#btn-confirm-room'
        },
        storage: {
            selectedRoom: 'recepcion_create_selected_room'
        }
    };

    var elements = {};
    var state = {
        selectedRoomData: null,
        isFormValid: false
    };

    /**
     * Inicializar elementos del DOM
     */
    function initElements() {
        // Cachear elementos del DOM
        Object.keys(config.selectors).forEach(function (key) {
            elements[key] = $(config.selectors[key]);
        });
    }

    /**
     * Filtrar habitaciones por tipo
     * @param {string} tipo - Tipo de habitación a filtrar
     */
    function filterByTipo(tipo) {
        // Actualizar botones
        elements.filterTipoButtons
            .removeClass('active btn-primary btn-info btn-success btn-warning')
            .addClass('btn-outline-secondary');

        var activeButton = elements.filterTipoButtons.filter('[data-tipo="' + tipo + '"]');
        activeButton.removeClass('btn-outline-secondary');

        switch (tipo) {
            case 'todos':
                activeButton.addClass('btn-primary active');
                break;
            case 'individual':
                activeButton.addClass('btn-info active');
                break;
            case 'doble':
                activeButton.addClass('btn-success active');
                break;
            case 'compartida':
                activeButton.addClass('btn-warning active');
                break;
        }

        // Filtrar habitaciones
        if (tipo === 'todos') {
            elements.habitacionCards.show();
        } else {
            elements.habitacionCards.each(function () {
                var cardTipo = $(this).data('tipo').toString().toLowerCase();
                var match = cardTipo.includes(tipo.toLowerCase());
                $(this).toggle(match);
            });
        }

        updateVisibleCount();
        hideEmptySections();
    }

    /**
     * Filtrar por piso (reutiliza lógica del index)
     * @param {string} piso - Piso a filtrar
     */
    function filterByPiso(piso) {
        elements.pisoButtons
            .removeClass('active btn-info btn-secondary')
            .addClass('btn-outline-info');

        var activeButton = elements.pisoButtons.filter('[data-piso="' + piso + '"]');
        if (piso === 'todos') {
            activeButton.removeClass('btn-outline-info').addClass('btn-secondary active');
        } else {
            activeButton.removeClass('btn-outline-info').addClass('btn-info active');
        }

        if (piso === 'todos') {
            elements.pisoSections.show();
        } else {
            elements.pisoSections.hide();
            elements.pisoSections.filter('[data-piso="' + piso + '"]').show();
        }

        updateVisibleCount();
    }

    /**
     * Ocultar secciones vacías
     */
    function hideEmptySections() {
        elements.pisoSections.each(function () {
            var visibleCards = $(this).find('.habitacion-selectable:visible').length;
            $(this).toggle(visibleCards > 0);
        });
    }

    /**
     * Actualizar contador de habitaciones visibles
     */
    function updateVisibleCount() {
        var visibleCount = elements.habitacionCards.filter(':visible').length;
        elements.totalDisponibles.text(visibleCount + ' habitaciones disponibles');
    }

    /**
     * Mostrar modal de confirmación de habitación
     * @param {Object} roomData - Datos de la habitación
     */
    function showRoomConfirmModal(roomData) {
        elements.modalRoomNumber.text(roomData.numero);
        elements.modalRoomType.text(roomData.tipo);
        elements.modalRoomPrice.text(parseFloat(roomData.precio).toFixed(2));

        state.selectedRoomData = roomData;
        elements.modal.modal('show');
    }

    /**
     * Confirmar selección de habitación
     */
    function confirmRoomSelection() {
        if (state.selectedRoomData) {
            // Guardar en localStorage para persistencia
            localStorage.setItem(config.storage.selectedRoom, JSON.stringify(state.selectedRoomData));

            // Redirigir al formulario de reserva
            window.location.href = window.location.pathname + '?idhabitacion=' + state.selectedRoomData.id;
        }
    }

    /**
     * Actualizar información del cliente
     */
    function updateClienteInfo() {
        var selectedOption = elements.clienteSelect.find('option:selected');

        if (selectedOption.val()) {
            $('#nombre_cliente').val(selectedOption.data('nombre') || '');
            $('#documento_cliente').val(
                (selectedOption.data('tipodoc') || '') + ': ' + (selectedOption.data('numdoc') || '')
            );
            $('#telefono_cliente').val(selectedOption.data('telefono') || 'No disponible');

            // Marcar como válido
            elements.clienteSelect.removeClass('is-invalid').addClass('is-valid');
        } else {
            $('#nombre_cliente, #documento_cliente, #telefono_cliente').val('');
            elements.clienteSelect.removeClass('is-valid');
        }

        updateResumen();
    }

    /**
     * Actualizar información de tarifa y calcular fechas
     */
    function updateTarifaInfo() {
        var selectedOption = elements.tarifaSelect.find('option:selected');

        if (selectedOption.val()) {
            var precio = parseFloat(selectedOption.data('precio')) || 0;
            var tipoEstancia = selectedOption.data('estancia');
            var duracion = parseInt(selectedOption.data('duracion')) || 1;

            // Actualizar monto total
            elements.montoTotal.val(precio.toFixed(2));

            // Calcular fecha de salida automáticamente
            var fechaEntrada = new Date(elements.fechaEntrada.val());
            if (!isNaN(fechaEntrada.getTime())) {
                var fechaSalida = new Date(fechaEntrada);

                if (tipoEstancia === 'horas') {
                    fechaSalida.setHours(fechaSalida.getHours() + duracion);
                } else {
                    fechaSalida.setDate(fechaSalida.getDate() + duracion);
                }

                // Formatear para datetime-local
                var year = fechaSalida.getFullYear();
                var month = String(fechaSalida.getMonth() + 1).padStart(2, '0');
                var day = String(fechaSalida.getDate()).padStart(2, '0');
                var hours = String(fechaSalida.getHours()).padStart(2, '0');
                var minutes = String(fechaSalida.getMinutes()).padStart(2, '0');

                elements.fechaSalida.val(`${year}-${month}-${day}T${hours}:${minutes}`);
            }

            // Marcar como válido
            elements.tarifaSelect.removeClass('is-invalid').addClass('is-valid');
        } else {
            elements.tarifaSelect.removeClass('is-valid');
        }

        updateResumen();
        updateResumenFinanciero();
    }

    /**
     * Manejar cambios en el método de pago
     */
    function handlePaymentMethodChange() {
        var metodo = elements.pagoMetodo.val();

        if (metodo === 'Efectivo') {
            $('#seccion-efectivo').show();
            // Auto-llenar con el monto total
            var montoTotal = parseFloat(elements.montoTotal.val()) || 0;
            elements.montoPagado.val(montoTotal.toFixed(2));
            elements.pagoRecibido.val(montoTotal.toFixed(2));
            calculateChange();
        } else {
            $('#seccion-efectivo').hide();

            if (metodo === 'QR') {
                // Para QR, asumir pago completo
                var montoTotal = parseFloat(elements.montoTotal.val()) || 0;
                elements.montoPagado.val(montoTotal.toFixed(2));
            } else if (metodo === '') {
                // Sin método de pago
                elements.montoPagado.val('0.00');
            }
        }

        updateResumen();
        updateResumenFinanciero();
    }

    /**
     * Calcular cambio para pagos en efectivo
     */
    function calculateChange() {
        var montoTotal = parseFloat(elements.montoTotal.val()) || 0;
        var montoRecibido = parseFloat(elements.pagoRecibido.val()) || 0;
        var cambio = Math.max(0, montoRecibido - montoTotal);

        elements.cambio.val(cambio.toFixed(2));
        elements.montoPagado.val(montoTotal.toFixed(2));

        updateResumenFinanciero();
    }

    /**
     * Actualizar resumen financiero
     */
    function updateResumenFinanciero() {
        var total = parseFloat(elements.montoTotal.val()) || 0;
        var pagado = parseFloat(elements.montoPagado.val()) || 0;
        var saldo = total - pagado;

        $('#resumen-total').text('$' + total.toFixed(2));
        $('#resumen-pagado').text('$' + pagado.toFixed(2));
        $('#resumen-saldo').text('$' + saldo.toFixed(2));

        // Cambiar color del saldo
        var saldoElement = $('#resumen-saldo');
        saldoElement.removeClass('text-danger text-success text-warning');

        if (saldo > 0) {
            saldoElement.addClass('text-danger');
        } else if (saldo === 0) {
            saldoElement.addClass('text-success');
        } else {
            saldoElement.addClass('text-warning');
        }
    }

    /**
     * Actualizar resumen de la reserva
     */
    function updateResumen() {
        // Cliente
        var clienteText = elements.clienteSelect.find('option:selected').data('nombre') || 'No seleccionado';
        $('#resumen-cliente').text(clienteText);

        // Tarifa
        var tarifaText = elements.tarifaSelect.find('option:selected').text() || 'No seleccionada';
        $('#resumen-tarifa').text(tarifaText);

        // Estado
        var estadoText = elements.estado.find('option:selected').text();
        var estadoBadge = $('#resumen-estado');
        estadoBadge.text(estadoText);

        // Actualizar clase del badge según el estado
        estadoBadge.removeClass('badge-warning badge-info');
        if (elements.estado.val() === 'en_curso') {
            estadoBadge.addClass('badge-warning');
        } else {
            estadoBadge.addClass('badge-info');
        }

        // Fechas
        updateFechasResumen();

        // Método de pago
        var metodoPago = elements.pagoMetodo.find('option:selected').text() || 'Sin pago';
        $('#resumen-metodo-pago').text(metodoPago);

        // Observaciones
        var observaciones = elements.observaciones.val().trim();
        if (observaciones) {
            $('#resumen-observaciones').text(observaciones);
            $('#resumen-observaciones-container').show();
        } else {
            $('#resumen-observaciones-container').hide();
        }

        // Calcular duración
        calculateDuration();
    }

    /**
     * Actualizar fechas en el resumen
     */
    function updateFechasResumen() {
        var fechaEntrada = elements.fechaEntrada.val();
        var fechaSalida = elements.fechaSalida.val();

        if (fechaEntrada) {
            var fecha = new Date(fechaEntrada);
            var dia = fecha.getDate().toString().padStart(2, '0');
            var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = fecha.getHours().toString().padStart(2, '0');
            var minutos = fecha.getMinutes().toString().padStart(2, '0');
            $('#resumen-entrada').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
        }

        if (fechaSalida) {
            var fecha = new Date(fechaSalida);
            var dia = fecha.getDate().toString().padStart(2, '0');
            var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = fecha.getHours().toString().padStart(2, '0');
            var minutos = fecha.getMinutes().toString().padStart(2, '0');
            $('#resumen-salida').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
        }
    }

    /**
     * Calcular y mostrar duración de la estancia
     */
    function calculateDuration() {
        var fechaEntrada = new Date(elements.fechaEntrada.val());
        var fechaSalida = new Date(elements.fechaSalida.val());

        if (!isNaN(fechaEntrada.getTime()) && !isNaN(fechaSalida.getTime())) {
            var diffTime = Math.abs(fechaSalida - fechaEntrada);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            var diffHours = Math.ceil(diffTime / (1000 * 60 * 60));

            var duracionText;
            if (diffDays >= 1) {
                duracionText = diffDays + (diffDays === 1 ? ' día' : ' días');
            } else {
                duracionText = diffHours + (diffHours === 1 ? ' hora' : ' horas');
            }

            $('#resumen-duracion').text(duracionText);
        }
    }

    /**
     * Validar formulario
     */
    function validateForm() {
        var isValid = true;
        var requiredFields = [
            elements.clienteSelect,
            elements.tarifaSelect,
            elements.fechaEntrada,
            elements.fechaSalida,
            elements.montoTotal
        ];

        // Validar campos requeridos
        requiredFields.forEach(function (field) {
            if (!field.val() || field.val().trim() === '') {
                field.removeClass('is-valid').addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid').addClass('is-valid');
            }
        });

        // Validar fechas lógicas
        var fechaEntrada = new Date(elements.fechaEntrada.val());
        var fechaSalida = new Date(elements.fechaSalida.val());

        if (!isNaN(fechaEntrada.getTime()) && !isNaN(fechaSalida.getTime())) {
            if (fechaSalida <= fechaEntrada) {
                elements.fechaSalida.removeClass('is-valid').addClass('is-invalid');
                isValid = false;
            }
        }

        // Validar monto si hay pago en efectivo
        if (elements.pagoMetodo.val() === 'Efectivo') {
            var montoTotal = parseFloat(elements.montoTotal.val()) || 0;
            var montoRecibido = parseFloat(elements.pagoRecibido.val()) || 0;

            if (montoRecibido < montoTotal) {
                elements.pagoRecibido.removeClass('is-valid').addClass('is-invalid');
                isValid = false;
            } else {
                elements.pagoRecibido.removeClass('is-invalid').addClass('is-valid');
            }
        }

        state.isFormValid = isValid;

        // Actualizar botón de envío
        if (isValid) {
            elements.btnSubmit.prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
            $('#btn-text').text('Confirmar Reserva');
        } else {
            elements.btnSubmit.prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
            $('#btn-text').text('Complete los campos requeridos');
        }

        return isValid;
    }

    /**
     * Configurar event handlers
     */
    function setupEventHandlers() {
        // Filtros de selección de habitación
        if (elements.filterTipoButtons.length) {
            elements.filterTipoButtons.on('click', function (e) {
                e.preventDefault();
                var tipo = $(this).data('tipo');
                filterByTipo(tipo);
            });
        }

        if (elements.pisoButtons.length) {
            elements.pisoButtons.on('click', function (e) {
                e.preventDefault();
                var piso = $(this).data('piso');
                filterByPiso(piso);
            });
        }

        // Selección de habitación
        $(document).on('click', config.selectors.btnSelectRoom, function (e) {
            e.preventDefault();
            var roomData = {
                id: $(this).data('id'),
                numero: $(this).data('numero'),
                tipo: $(this).data('tipo'),
                precio: $(this).data('precio')
            };
            showRoomConfirmModal(roomData);
        });

        // Confirmar selección de habitación
        elements.btnConfirmRoom.on('click', function () {
            elements.modal.modal('hide');
            confirmRoomSelection();
        });

        // Formulario de reserva
        if (elements.form.length) {
            // Cliente
            elements.clienteSelect.on('change', updateClienteInfo);

            // Tarifa
            elements.tarifaSelect.on('change', updateTarifaInfo);

            // Fechas - también recalcular cuando cambia fecha entrada
            elements.fechaEntrada.on('change', function () {
                // Si hay tarifa seleccionada, recalcular fecha de salida
                var tarifaSeleccionada = elements.tarifaSelect.find('option:selected');
                if (tarifaSeleccionada.val()) {
                    updateTarifaInfo(); // Esto recalculará la fecha
                }
                updateResumen();
                validateForm();
            });

            elements.fechaSalida.on('change', function () {
                updateResumen();
                validateForm();
            });

            // Estado
            elements.estado.on('change', updateResumen);

            // Observaciones
            elements.observaciones.on('input', updateResumen);

            // Pago
            elements.pagoMetodo.on('change', handlePaymentMethodChange);
            elements.pagoRecibido.on('input', calculateChange);
            elements.montoPagado.on('input', updateResumenFinanciero);

            // Validación en tiempo real
            elements.form.find('input, select, textarea').on('blur change', validateForm);

            // Envío del formulario
            elements.form.on('submit', function (e) {
                e.preventDefault();

                if (!validateForm()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formulario incompleto',
                        text: 'Por favor complete todos los campos requeridos correctamente.',
                        confirmButtonColor: '#dc3545'
                    });
                    return;
                }

                // Mostrar confirmación
                Swal.fire({
                    title: '¿Confirmar reserva?',
                    html: `
                        <div class="text-left">
                            <p><strong>Habitación:</strong> ${$('#resumen-habitacion').text()}</p>
                            <p><strong>Cliente:</strong> ${$('#resumen-cliente').text()}</p>
                            <p><strong>Total:</strong> ${$('#resumen-total').text()}</p>
                            <p><strong>Estado:</strong> ${$('#resumen-estado').text()}</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Revisar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitForm();
                    }
                });
            });
        }

        // Atajos de teclado para selección de habitación
        $(document).on('keydown', function (e) {
            // Solo procesar si estamos en selección de habitación
            if (!$('body').hasClass('step-select-room')) return;
            if ($('input, textarea, select').is(':focus')) return;

            switch (e.key) {
                case '1':
                    e.preventDefault();
                    elements.filterTipoButtons.filter('[data-tipo="todos"]').click();
                    break;
                case '2':
                    e.preventDefault();
                    elements.filterTipoButtons.filter('[data-tipo="individual"]').click();
                    break;
                case '3':
                    e.preventDefault();
                    elements.filterTipoButtons.filter('[data-tipo="doble"]').click();
                    break;
                case '4':
                    e.preventDefault();
                    elements.filterTipoButtons.filter('[data-tipo="compartida"]').click();
                    break;
                case 'Escape':
                    // Limpiar filtros
                    elements.filterTipoButtons.filter('[data-tipo="todos"]').click();
                    elements.pisoButtons.filter('[data-piso="todos"]').click();
                    break;
            }
        });
    }

    /**
     * Enviar formulario
     */
    function submitForm() {
        // Mostrar loading
        elements.btnSubmit.prop('disabled', true);
        $('#btn-text').html('<span class="loading-spinner mr-2"></span>Procesando...');

        // Enviar formulario
        elements.form.off('submit').submit();
    }

    /**
     * Limpiar localStorage al salir
     */
    function cleanup() {
        localStorage.removeItem(config.storage.selectedRoom);
    }

    /**
     * Configurar responsive
     */
    function setupResponsive() {
        function adjustForMobile() {
            if ($(window).width() < 576) {
                $('.module-recepcion-create .btn-filter-tipo, .module-recepcion-create .btn-piso').addClass('btn-sm');
            } else {
                $('.module-recepcion-create .btn-filter-tipo, .module-recepcion-create .btn-piso').removeClass('btn-sm');
            }
        }

        adjustForMobile();
        $(window).on('resize', function () {
            clearTimeout(state.resizeTimeout);
            state.resizeTimeout = setTimeout(adjustForMobile, 250);
        });
    }

    /**
     * Inicializar el módulo
     */
    function init() {
        // Solo inicializar si estamos en la página de create
        if (!$('body').hasClass('module-recepcion-create')) {
            return;
        }

        initElements();
        initializeSelect2();
        setupEventHandlers();
        setupResponsive();

        // Si estamos en el paso de formulario, inicializar valores
        if ($('body').hasClass('step-create-checkin')) {
            updateResumen();
            updateResumenFinanciero();
            validateForm();

            // Limpiar datos de selección anterior
            cleanup();
        }

        // Si estamos en selección de habitación, inicializar filtros
        if ($('body').hasClass('step-select-room')) {
            updateVisibleCount();
        }
    }

    // API pública del módulo
    return {
        init: init,
        filterByTipo: filterByTipo,
        filterByPiso: filterByPiso,
        updateResumen: updateResumen,
        validateForm: validateForm,
        cleanup: cleanup
    };

})(jQuery);

// Auto-inicializar cuando el DOM esté listo
$(document).ready(function () {
    window.RecepcionModule.Create.init();
});

// Limpiar al salir de la página
$(window).on('beforeunload', function () {
    if (window.RecepcionModule && window.RecepcionModule.Create) {
        window.RecepcionModule.Create.cleanup();
    }
});
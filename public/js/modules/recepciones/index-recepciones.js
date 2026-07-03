/**
 * JavaScript para recepciones - Compatible con AdminLTE
 * Versión simplificada sin funcionalidad de búsqueda
 */

$(document).ready(function () {
    // Variables principales
    var allCards = $('.habitacion-card');
    var filterButtons = $('.btn-filter');
    var pisoButtons = $('.btn-piso');

    /**
     * Filtrar habitaciones por estado
     */
    function filterRoomsByStatus(status) {
        // Actualizar botones usando colores nativos de Bootstrap/AdminLTE
        filterButtons.removeClass('active btn-primary btn-success btn-warning btn-danger')
            .addClass('btn-outline-secondary');

        var activeButton = filterButtons.filter('[data-status="' + status + '"]');
        activeButton.removeClass('btn-outline-secondary');

        switch (status) {
            case 'todas':
                activeButton.addClass('btn-primary active');
                break;
            case 'disponibles':
                activeButton.addClass('btn-success active');
                break;
            case 'ocupadas':
                activeButton.addClass('btn-warning active');
                break;
            case 'mantenimiento':
                activeButton.addClass('btn-danger active');
                break;
        }

        // Mostrar/ocultar secciones
        if (status === 'todas') {
            $('.section-disponibles, .section-ocupadas, .section-mantenimiento').show();
        } else {
            $('.section-disponibles, .section-ocupadas, .section-mantenimiento').hide();
            $('.section-' + status).show();
        }

        // Aplicar filtro de piso actual si hay uno activo
        var activePiso = pisoButtons.filter('.active').data('piso');
        if (activePiso && activePiso !== 'todos') {
            applyPisoFilter(activePiso);
        }

        updateCounters();
    }

    /**
     * Filtrar por piso - UNIVERSAL para todas las secciones
     */
    function filterRoomsByPiso(pisoSeleccionado) {
        // Usar colores nativos de AdminLTE
        pisoButtons.removeClass('active btn-info btn-secondary').addClass('btn-outline-info');

        var activeButton = pisoButtons.filter('[data-piso="' + pisoSeleccionado + '"]');
        if (pisoSeleccionado === 'todos') {
            activeButton.removeClass('btn-outline-info').addClass('btn-secondary active');
        } else {
            activeButton.removeClass('btn-outline-info').addClass('btn-info active');
        }

        applyPisoFilter(pisoSeleccionado);
        updateCounters();
    }

    /**
     * Aplicar filtro de piso a TODAS las secciones sin restricciones
     */
    function applyPisoFilter(pisoSeleccionado) {
        if (pisoSeleccionado === 'todos') {
            // Mostrar TODAS las secciones de pisos en TODAS las categorías
            $('.piso-section').show();
        } else {
            // Ocultar TODAS las secciones de pisos primero
            $('.piso-section').hide();

            // Mostrar SOLO las secciones del piso seleccionado
            $('.piso-section[data-piso="' + pisoSeleccionado + '"]').show();
        }

        // Después de aplicar filtro de piso, ocultar secciones principales vacías
        hideEmptySectionsAfterPisoFilter();
    }

    /**
     * Ocultar secciones principales vacías después del filtro de piso
     */
    function hideEmptySectionsAfterPisoFilter() {
        $('.section-disponibles, .section-ocupadas, .section-mantenimiento').each(function () {
            var visiblePisoSections = $(this).find('.piso-section:visible').length;
            $(this).toggle(visiblePisoSections > 0);
        });
    }

    /**
     * Ocultar secciones vacías - MEJORADO
     */
    function hideEmptySections() {
        // Ocultar secciones de pisos vacías
        $('.piso-section').each(function () {
            var visibleCards = $(this).find('.habitacion-card:visible').length;
            $(this).toggle(visibleCards > 0);
        });

        // Ocultar secciones principales vacías
        $('.section-disponibles, .section-ocupadas, .section-mantenimiento').each(function () {
            var visibleSections = $(this).find('.piso-section:visible').length;
            $(this).toggle(visibleSections > 0);
        });
    }

    /**
     * Aplicar filtros activos - MEJORADO para manejar la combinación correcta
     */
    function applyActiveFilters() {
        var activeStatus = filterButtons.filter('.active').data('status');
        var activePiso = pisoButtons.filter('.active').data('piso');

        // Primero aplicar filtro de estado
        if (activeStatus && activeStatus !== 'todas') {
            $('.section-disponibles, .section-ocupadas, .section-mantenimiento').hide();
            $('.section-' + activeStatus).show();
        } else {
            $('.section-disponibles, .section-ocupadas, .section-mantenimiento').show();
        }

        // Luego aplicar filtro de piso (que funciona sobre las secciones visibles)
        if (activePiso && activePiso !== 'todos') {
            applyPisoFilter(activePiso);
        }
    }

    /**
     * Actualizar contadores - MEJORADO para contar correctamente
     */
    function updateCounters() {
        var disponibles = $('.section-disponibles .habitacion-card:visible').length;
        var ocupadas = $('.section-ocupadas .habitacion-card:visible').length;
        var mantenimiento = $('.section-mantenimiento .habitacion-card:visible').length;
        var total = disponibles + ocupadas + mantenimiento;

        // Actualizar contadores en botones
        filterButtons.each(function () {
            var status = $(this).data('status');
            var count = 0;

            switch (status) {
                case 'todas':
                    count = total;
                    break;
                case 'disponibles':
                    count = disponibles;
                    break;
                case 'ocupadas':
                    count = ocupadas;
                    break;
                case 'mantenimiento':
                    count = mantenimiento;
                    break;
            }

            var badge = $(this).find('.filter-count');
            if (badge.length === 0) {
                badge = $('<span class="badge badge-light filter-count ml-1"></span>');
                $(this).append(badge);
            }
            badge.text(count);
        });
    }

    /**
     * Limpiar filtros
     */
    function clearAllFilters() {
        // Resetear a estilos nativos
        filterButtons.removeClass('active btn-primary btn-success btn-warning btn-danger')
            .addClass('btn-outline-secondary');
        filterButtons.filter('[data-status="todas"]')
            .removeClass('btn-outline-secondary')
            .addClass('btn-primary active');

        pisoButtons.removeClass('active btn-info').addClass('btn-outline-info');
        pisoButtons.filter('[data-piso="todos"]')
            .removeClass('btn-outline-info')
            .addClass('btn-secondary active');

        // Mostrar todas las tarjetas y secciones
        allCards.show();
        $('.piso-section').show();
        $('.section-disponibles, .section-ocupadas, .section-mantenimiento').show();

        // Limpiar localStorage
        localStorage.removeItem('recepcion_status_filter');
        localStorage.removeItem('recepcion_piso_filter');

        updateCounters();
    }

    // ========================================
    // EVENT HANDLERS
    // ========================================

    // Filtros de estado
    filterButtons.on('click', function (e) {
        e.preventDefault();
        var status = $(this).data('status');
        filterRoomsByStatus(status);

        // Aplicar filtro de piso activo si existe
        var activePiso = pisoButtons.filter('.active').data('piso');
        if (activePiso && activePiso !== 'todos') {
            applyPisoFilter(activePiso);
        }

        // Guardar preferencia solo si no es "todas"
        if (status !== 'todas') {
            localStorage.setItem('recepcion_status_filter', status);
        } else {
            localStorage.removeItem('recepcion_status_filter');
        }
    });

    // Filtros de piso
    pisoButtons.on('click', function (e) {
        e.preventDefault();
        var piso = $(this).data('piso');
        filterRoomsByPiso(piso);

        // Guardar preferencia solo si no es "todos"
        if (piso !== 'todos') {
            localStorage.setItem('recepcion_piso_filter', piso);
        } else {
            localStorage.removeItem('recepcion_piso_filter');
        }
    });

    // Botón de limpiar filtros
    if ($('#clear-filters-btn').length === 0) {
        var clearButton = $('<button type="button" id="clear-filters-btn" class="btn btn-outline-secondary btn-sm ml-2"><i class="fas fa-times"></i> <span class="d-none d-sm-inline">Limpiar</span></button>');
        $('.piso-filters-container').append(clearButton);
        clearButton.on('click', clearAllFilters);
    }

    // Atajos de teclado básicos
    $(document).on('keydown', function (e) {
        if (!$('input, textarea, select').is(':focus')) {
            switch (e.key) {
                case '1':
                    filterButtons.filter('[data-status="todas"]').click();
                    break;
                case '2':
                    filterButtons.filter('[data-status="disponibles"]').click();
                    break;
                case '3':
                    filterButtons.filter('[data-status="ocupadas"]').click();
                    break;
                case '4':
                    filterButtons.filter('[data-status="mantenimiento"]').click();
                    break;
                case 'Escape':
                    clearAllFilters();
                    break;
            }
        }
    });

    // Configuración para el checkout con SweetAlert2
    $(document).on('click', '.btn-checkout', function (e) {
        e.preventDefault();

        const checkoutUrl = $(this).attr('href');
        const habitacionNumero = $(this).data('habitacion');
        const clienteNombre = $(this).data('cliente');

        Swal.fire({
            title: '¿Realizar Check-Out?',
            html: `
                <div class="text-left">
                    <p><strong>Cliente:</strong> ${clienteNombre || 'Cliente'}</p>
                    <p><strong>Habitación:</strong> ${habitacionNumero || 'N/A'}</p>
                    <p>Esta acción finalizará la estancia del cliente.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Confirmar Check-Out',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a la URL de checkout
                window.location.href = checkoutUrl;
            }
        });
    });

    // ========================================
    // INICIALIZACIÓN
    // ========================================

    // Inicializar contadores
    updateCounters();
});
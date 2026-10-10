/**
 * Listado de habitaciones: DataTable, filtros del lado del cliente y cambio de
 * estado en línea. El estado se filtra desde las tarjetas de resumen
 * (#resumen-estados); tipo, piso y precio desde la tarjeta de filtros avanzados.
 */

$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
    initializeSelect2();

    const tabla = initDataTable('#tablaHabitaciones', {
        titulo: 'Habitaciones',
        archivo: 'habitaciones',
        subtitulo: 'Habitaciones registradas',
        descripcionExcel: 'Registro de habitaciones del sistema',
        entidad: 'habitaciones',
        columnas: [0, 1, 2, 3, 4, 5],
        columnasTexto: [5],
        dt: {
            // En viewports estrechos se colapsan primero Tipo/Piso/Capacidad/Precio;
            // Habitación, Estado y Acciones se mantienen visibles el mayor tiempo posible.
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 5 },
                { responsivePriority: 3, targets: 6 },
                { responsivePriority: 10, targets: [2, 3, 4] }
            ]
        }
    });

    // ── Filtro combinado del lado del cliente ────────────────────────────
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const $row = $(tabla.row(dataIndex).node());

        const estadoFiltro = $('#filtro-estado').val();
        if (estadoFiltro && estadoFiltro !== $row.data('estado')) {
            return false;
        }

        const tipoFiltro = $('#filtro-tipo').val();
        if (tipoFiltro && !data[1].includes(tipoFiltro)) {
            return false;
        }

        const pisoFiltro = $('#filtro-piso').val();
        if (pisoFiltro && !data[2].includes(pisoFiltro)) {
            return false;
        }

        const precioFiltro = parseFloat($('#filtro-precio').val()) || 0;
        const precioRow = parseFloat(data[4].replace(/[^\d.-]/g, '')) || 0;
        if (precioFiltro > 0 && precioRow < precioFiltro) {
            return false;
        }

        return true;
    });

    // ── Tarjetas de resumen como filtro de estado ───────────────────────
    function marcarTarjetaActiva(valor) {
        $('#resumen-estados .filtro-estado').each(function () {
            const activa = String($(this).attr('data-filtro-estado') || '') === valor;
            $(this).toggleClass('filtro-estado--activo', activa)
                .attr('aria-pressed', activa ? 'true' : 'false');
        });
    }

    function aplicarFiltroEstado(elemento) {
        const valor = String($(elemento).attr('data-filtro-estado') || '');
        $('#filtro-estado').val(valor);
        marcarTarjetaActiva(valor);
        tabla.draw();
    }

    $(document).on('click', '#resumen-estados .filtro-estado', function (e) {
        e.preventDefault();
        aplicarFiltroEstado(this);
    });

    // Las tarjetas son <a role="button">: Enter ya activa de forma nativa,
    // Espacio no. Se replica el comportamiento de un botón real.
    $(document).on('keydown', '#resumen-estados .filtro-estado', function (e) {
        if (e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            aplicarFiltroEstado(this);
        }
    });

    // ── Contadores por estado ───────────────────────────────────────────
    function actualizarContadores() {
        const conteo = { '': tabla.rows().count(), disponible: 0, ocupada: 0, limpieza: 0, mantenimiento: 0 };

        tabla.rows().every(function () {
            const estado = $(this.node()).data('estado');
            if (conteo[estado] !== undefined) {
                conteo[estado]++;
            }
        });

        $('[data-contador-estado]').each(function () {
            const clave = $(this).data('contador-estado') || '';
            if (conteo[clave] !== undefined) {
                $(this).text(conteo[clave]);
            }
        });
    }

    // ── Filtros avanzados ───────────────────────────────────────────────
    $('#btn-aplicar-filtros').on('click', function () {
        tabla.draw();
    });

    $('#btn-limpiar-filtros').on('click', function () {
        $('#filtro-estado').val('');
        $('#filtro-tipo').val('').trigger('change');
        $('#filtro-piso').val('').trigger('change');
        $('#filtro-precio').val('').removeClass('border-primary');
        marcarTarjetaActiva('');
        tabla.draw();
    });

    $('#filtro-precio').on('input', function () {
        $(this).toggleClass('border-primary', Boolean($(this).val()));
    });

    // El cambio de estado en línea lo maneja cambiar-estado-habitaciones.js
    // (enlace único a los botones .cambiar-estado).

    actualizarContadores();
});

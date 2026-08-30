/**
 * recepción / nueva reserva — página única con resumen sticky.
 *
 * Reemplaza al wizard de 2 pasos. Responsabilidades:
 *  - selección de habitación por tiles (radio)
 *  - filtro de tarifas según el tipo de la habitación elegida
 *  - cálculo de la fecha de salida y de la duración
 *  - chequeo de solape (disponibilidad_ajax.php) antes de enviar
 *  - resumen en vivo + cálculo de cambio en efectivo
 *  - buscador global (Select2 remoto)
 *
 * @module RecepcionModule.Create
 */
window.RecepcionModule = window.RecepcionModule || {};

window.RecepcionModule.Create = (function ($) {
    'use strict';

    var $form, $habRadios, $tarifa, $entrada, $salida, $estado, $cliente,
        $metodo, $recibido, $total, $pagado, $btns;
    var disponibilidadTimer = null;
    var $tarifaOpts = null; // copia intacta de todos los <option> de #idtarifa

    function fmt(n) { return 'Bs ' + (parseFloat(n) || 0).toFixed(2); }

    function pad(n) { return String(n).padStart(2, '0'); }

    function toLocalInput(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
            'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function selectedRoom() {
        var $r = $habRadios.filter(':checked');
        return $r.length ? $r : null;
    }

    // ── Tarifas: solo las del tipo de la habitación elegida ───────────
    function filterTarifas() {
        var $room = selectedRoom();
        var idtipo = $room ? String($room.data('idtipo')) : null;
        var current = $tarifa.val();

        // Se reconstruyen los <option> (en vez de solo ocultarlos) para que
        // Select2 refleje el filtro: no respeta option.toggle()/disabled.
        if (!$tarifaOpts) { $tarifaOpts = $tarifa.find('option').clone(); }

        var $placeholder = $tarifaOpts.filter(function () { return !this.value; }).clone()
            .text(idtipo ? 'Seleccione una tarifa' : 'Elija primero una habitación');
        var $matching = idtipo
            ? $tarifaOpts.filter(function () {
                return this.value && String($(this).data('idtipo')) === idtipo;
            }).clone()
            : $();

        $tarifa.empty().append($placeholder).append($matching).prop('disabled', !idtipo);

        var keep = current && $matching.filter(function () { return this.value === current; }).length;
        $tarifa.val(keep ? current : '');

        if (typeof refreshSelect2 === 'function' && $tarifa.data('select2')) {
            refreshSelect2('#idtarifa');
        }
        if (current !== $tarifa.val()) { onTarifaChange(); }
    }

    // ── Fecha de salida derivada de la tarifa ─────────────────────────
    function recomputeSalida() {
        var opt = $tarifa.find('option:selected');
        if (!opt.val()) { return; }
        var base = new Date($entrada.val());
        if (isNaN(base.getTime())) { return; }
        var dur = parseInt(opt.data('duracion'), 10) || 1;
        if (opt.data('estancia') === 'horas') {
            base.setHours(base.getHours() + dur);
        } else {
            base.setDate(base.getDate() + dur);
        }
        $salida.val(toLocalInput(base));
    }

    function duracionTexto() {
        var a = new Date($entrada.val()), b = new Date($salida.val());
        if (isNaN(a.getTime()) || isNaN(b.getTime()) || b <= a) { return '—'; }
        var ms = b - a;
        var dias = Math.round(ms / 86400000);
        if (dias >= 1) { return dias + (dias === 1 ? ' día' : ' días'); }
        var horas = Math.round(ms / 3600000);
        return horas + (horas === 1 ? ' hora' : ' horas');
    }

    function fechaTexto(v) {
        var d = new Date(v);
        if (isNaN(d.getTime())) { return '—'; }
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() +
            ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // ── Resumen en vivo ──────────────────────────────────────────────
    function updateResumen() {
        var $room = selectedRoom();
        $('#rs-habitacion').text($room ? ('N° ' + $room.data('numero') + ' · ' + $room.data('tipo')) : 'Sin seleccionar');
        $('#rs-huesped').text($cliente.find('option:selected').data('nombre') || 'Sin seleccionar');
        $('#rs-entrada').text(fechaTexto($entrada.val()));
        $('#rs-salida').text(fechaTexto($salida.val()));
        $('#rs-noches').text(duracionTexto());

        var opt = $tarifa.find('option:selected');
        var total = opt.val() ? (parseFloat(opt.data('precio')) || 0) : 0;
        $('#rs-tarifa').text(opt.val() ? opt.text() : 'Sin seleccionar');
        $total.val(total.toFixed(2));

        var adelanto = 0;
        var metodo = $metodo.val();
        if (metodo === 'Efectivo') {
            adelanto = Math.min(parseFloat($recibido.val()) || 0, total);
        } else if (metodo === 'QR' || metodo === 'OTROS') {
            adelanto = total;
        }
        $pagado.val(adelanto.toFixed(2));

        $('#rs-total').text(fmt(total));
        $('#rs-adelanto').text(fmt(adelanto));
        var saldo = total - adelanto;
        $('#rs-saldo').text(fmt(saldo)).toggleClass('text-danger', saldo > 0.009).toggleClass('text-success', saldo <= 0.009);

        var cambio = Math.max(0, (parseFloat($recibido.val()) || 0) - total);
        $('#pago_cambio').text(cambio.toFixed(2));
    }

    function onTarifaChange() {
        recomputeSalida();
        updateResumen();
        checkDisponibilidad();
    }

    // ── Chequeo de solape ────────────────────────────────────────────
    function checkDisponibilidad() {
        clearTimeout(disponibilidadTimer);
        disponibilidadTimer = setTimeout(function () {
            var $room = selectedRoom();
            $('.rec-select-tile__conflicto').addClass('d-none');
            $('.rec-select-tile').removeClass('is-conflict');
            setSubmitEnabled(true);
            if (!$room || !$entrada.val() || !$salida.val()) { return; }

            $.ajax({
                url: BASE_URL + 'controllers/recepcion/disponibilidad_ajax.php',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: {
                    idhabitacion: $room.val(),
                    fechaentrada: $entrada.val(),
                    fechasalida_prevista: $salida.val()
                }
            }).done(function (res) {
                if (res && res.disponible === false && !res.error) {
                    $room.closest('.rec-select-tile').addClass('is-conflict')
                        .find('.rec-select-tile__conflicto').removeClass('d-none');
                    setSubmitEnabled(false);
                }
            });
        }, 350);
    }

    function setSubmitEnabled(ok) {
        $btns.prop('disabled', !ok);
    }

    // ── Envío ────────────────────────────────────────────────────────
    function validar() {
        var ok = true;
        [$cliente, $tarifa, $entrada, $salida].forEach(function ($f) {
            var bad = !$f.val();
            $f.toggleClass('is-invalid', bad);
            if (bad) { ok = false; }
        });
        if (!selectedRoom()) { ok = false; }
        if (new Date($salida.val()) <= new Date($entrada.val())) {
            $salida.addClass('is-invalid');
            ok = false;
        }
        return ok;
    }

    function onSubmit(e) {
        e.preventDefault();
        if (!validar()) {
            Swal.fire({ icon: 'error', title: 'Faltan datos', text: 'Complete habitación, huésped, tarifa y fechas.' });
            return;
        }
        var $room = selectedRoom();
        Swal.fire({
            title: '¿Confirmar reserva?',
            html: '<div class="text-left">' +
                '<p><strong>Habitación:</strong> N° ' + $room.data('numero') + '</p>' +
                '<p><strong>Huésped:</strong> ' + ($cliente.find('option:selected').data('nombre') || '') + '</p>' +
                '<p><strong>Total:</strong> ' + $('#rs-total').text() + '</p>' +
                '<p><strong>Saldo:</strong> ' + $('#rs-saldo').text() + '</p></div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Revisar'
        }).then(function (r) {
            if (r.isConfirmed) {
                $btns.prop('disabled', true);
                $('#btn-text, #btn-text-side').text('Procesando…');
                $form.off('submit').trigger('submit');
            }
        });
    }

    // ── Buscador global ──────────────────────────────────────────────
    function initBuscador() {
        var $b = $('#rec-buscador-global');
        if (!$b.length || !$.fn.select2) { return; }
        $b.select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar huésped, habitación o #reserva',
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: $b.data('url'),
                dataType: 'json',
                delay: 250,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results || [] }; }
            }
        });
        $b.on('select2:select', function (e) {
            if (e.params.data.url) { window.location.href = e.params.data.url; }
        });
    }

    // ── Filtro de pisos ──────────────────────────────────────────────
    function initPisoFiltros() {
        $('.rec-piso-filtros button').on('click', function () {
            var piso = $(this).data('piso');
            $('.rec-piso-filtros button').removeClass('active btn-secondary').addClass('btn-outline-secondary');
            $(this).addClass('active btn-secondary').removeClass('btn-outline-secondary');
            $('.rec-select-piso').each(function () {
                $(this).toggle(piso === 'todos' || $(this).data('piso') === piso);
            });
        });
    }

    function init() {
        $form = $('#formReserva');
        if (!$form.length) { initBuscador(); return; }

        $habRadios = $('.rec-hab-radio');
        $tarifa = $('#idtarifa');
        $entrada = $('#fechaentrada');
        $salida = $('#fechasalida_prevista');
        $estado = $('#estado');
        $cliente = $('#idcliente');
        $metodo = $('#pago_metodo');
        $recibido = $('#pago_recibido');
        $total = $('#montototal');
        $pagado = $('#montopagado');
        $btns = $('#btn-submit, #btn-submit-side');

        if (typeof initializeSelect2 === 'function') {
            initializeSelect2();
        }

        initBuscador();
        initPisoFiltros();

        $habRadios.on('change', function () {
            $('.rec-select-tile').removeClass('is-selected');
            $(this).closest('.rec-select-tile').addClass('is-selected');
            filterTarifas();
            updateResumen();
            checkDisponibilidad();
        });

        $entrada.on('change', function () { recomputeSalida(); updateResumen(); checkDisponibilidad(); });
        $salida.on('change', function () { updateResumen(); checkDisponibilidad(); });
        $tarifa.on('change', onTarifaChange);
        $cliente.on('change', updateResumen);
        $estado.on('change', updateResumen);
        $metodo.on('change', function () {
            $('#seccion-efectivo').toggleClass('d-none', $(this).val() !== 'Efectivo');
            updateResumen();
        });
        $recibido.on('input', updateResumen);

        // Estado inicial (puede venir con ?idhabitacion= preseleccionada)
        filterTarifas();
        updateResumen();
        checkDisponibilidad();
    }

    return { init: init };
})(jQuery);

$(function () { window.RecepcionModule.Create.init(); });

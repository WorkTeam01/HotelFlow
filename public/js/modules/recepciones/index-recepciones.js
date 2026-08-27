/**
 * Panel de Recepción — vista unificada con tabs Hoy / Mapa / Historial.
 * - Persiste el tab activo en el hash y en sessionStorage.
 * - Inicializa el DataTable del historial y el Select2 remoto del buscador global.
 * - Acciones inline (check-in / check-out / cancelar) por POST con SweetAlert2.
 */
$(function () {
    'use strict';

    var TABS = ['hoy', 'mapa', 'historial'];
    var STORAGE_KEY = 'recepcion_tab_activo';

    // ── Tabs: hash + sessionStorage ───────────────────────────────────
    function activarTab(nombre) {
        if (TABS.indexOf(nombre) === -1) return;
        $('#tab-' + nombre + '-link').tab('show');
    }

    var inicial = (window.location.hash || '').replace('#', '');
    if (TABS.indexOf(inicial) === -1) {
        try { inicial = sessionStorage.getItem(STORAGE_KEY); } catch (e) { inicial = null; }
    }
    if (TABS.indexOf(inicial) !== -1) {
        activarTab(inicial);
    }

    $('#recTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var nombre = $(e.target).attr('href').replace('#', '');
        try { sessionStorage.setItem(STORAGE_KEY, nombre); } catch (err) { /* no-op */ }
        if (history.replaceState) {
            history.replaceState(null, '', '#' + nombre);
        }
    });

    // ── DataTable del historial ───────────────────────────────────────
    if ($.fn.DataTable && $('#tablaRecepciones').length) {
        $('#tablaRecepciones').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, targets: [8] }],
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'Sin recepciones registradas',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ recepciones',
                sInfoEmpty: 'Mostrando 0 a 0 de 0 recepciones',
                sInfoFiltered: '(filtrado de _MAX_ recepciones)',
                sSearch: 'Buscar:',
                sLoadingRecords: 'Cargando...',
                oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
            }
        });
    }

    // ── Buscador global (Select2 remoto) ──────────────────────────────
    var $buscador = $('#rec-buscador-global');
    if ($buscador.length && $.fn.select2) {
        $buscador.select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar huésped, habitación o #reserva',
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: $buscador.data('url'),
                dataType: 'json',
                delay: 250,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results || [] }; }
            }
        });
        $buscador.on('select2:select', function (e) {
            var url = e.params.data.url;
            if (url) window.location.href = url;
        });
    }

    // ── Acciones inline ───────────────────────────────────────────────
    function postAccion(endpoint, payload) {
        return $.ajax({
            url: BASE_URL + 'controllers/recepcion/' + endpoint,
            type: 'POST',
            dataType: 'json',
            data: $.extend({ csrf_token: CSRF_TOKEN }, payload),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    $(document).on('click', '.rec-accion-checkin', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Realizar check-in?',
            text: 'La reserva pasará a "Ocupada".',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, check-in',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            postAccion('transicion_ajax.php', { idrecepcion: id, nuevo_estado: 'en_curso' })
                .done(function (res) {
                    if (res.success) { window.location.reload(); }
                    else { Swal.fire({ icon: 'error', title: 'No se pudo', text: res.message }); }
                })
                .fail(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de comunicación con el servidor' }); });
        });
    });

    $(document).on('click', '.rec-accion-cancelar', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: '¿Cancelar la reserva?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No',
            confirmButtonColor: '#dc3545'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            postAccion('transicion_ajax.php', { idrecepcion: id, nuevo_estado: 'cancelado' })
                .done(function (res) {
                    if (res.success) { window.location.reload(); }
                    else { Swal.fire({ icon: 'error', title: 'No se pudo', text: res.message }); }
                })
                .fail(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de comunicación con el servidor' }); });
        });
    });

    $(document).on('click', '.rec-accion-checkout', function () {
        var $btn = $(this);
        var id = $btn.data('id');
        var cliente = $btn.data('cliente') || 'Huésped';
        var habitacion = $btn.data('habitacion') || 'N/A';

        Swal.fire({
            title: '¿Realizar check-out?',
            html: '<div class="text-left"><p><strong>Huésped:</strong> ' + cliente + '</p>' +
                '<p><strong>Habitación:</strong> ' + habitacion + '</p>' +
                '<p>Se validará que el folio esté saldado antes de finalizar.</p></div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar check-out',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $btn.prop('disabled', true);
            postAccion('checkout_ajax.php', { idrecepcion: id })
                .done(function (res) {
                    if (res.success) { window.location.reload(); return; }
                    if (res.requiere_pago) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Saldo pendiente',
                            text: 'Bs ' + (parseFloat(res.saldo) || 0).toFixed(2) + '. Regístralo en el folio antes del check-out.',
                            confirmButtonText: 'Ir al folio'
                        }).then(function () {
                            window.location.href = BASE_URL + 'views/recepcion/show.php?id=' + id + '#folio-recepcion';
                        });
                        return;
                    }
                    Swal.fire({ icon: 'error', title: 'No se pudo hacer el check-out', text: res.message });
                })
                .fail(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de comunicación con el servidor' }); })
                .always(function () { $btn.prop('disabled', false); });
        });
    });
});

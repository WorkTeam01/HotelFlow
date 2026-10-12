document.addEventListener('DOMContentLoaded', function () {
    // Persistir la pestaña activa del detalle (mismo patrón que usuarios/show)
    // Bootstrap 4 dispara shown.bs.tab por jQuery: un addEventListener nativo no lo recibe
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        try {
            sessionStorage.setItem('lastEquipajeDetailTab', e.target.id);
        } catch (err) {
            // sessionStorage no disponible: se ignora
        }
    });

    try {
        const lastTab = sessionStorage.getItem('lastEquipajeDetailTab');
        if (lastTab && document.getElementById(lastTab)) {
            const tabEl = document.getElementById(lastTab);
            if (window.bootstrap && typeof window.bootstrap.Tab === 'function') {
                new bootstrap.Tab(tabEl).show();
            } else if (window.$ && typeof window.$(tabEl).tab === 'function') {
                window.$(tabEl).tab('show');
            }
        }
    } catch (err) {
        // Sin persistencia de tab: se usa la pestaña por defecto
    }
});

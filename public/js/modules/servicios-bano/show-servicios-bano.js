document.addEventListener('DOMContentLoaded', function () {
    // Persistir la pestaña activa del detalle.
    // Bootstrap 4 dispara shown.bs.tab por jQuery: un addEventListener nativo no lo recibe
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        try {
            sessionStorage.setItem('lastServicioBanoDetailTab', e.target.id);
        } catch (err) {
            // sessionStorage no disponible: se ignora
        }
    });

    try {
        const lastTab = sessionStorage.getItem('lastServicioBanoDetailTab');
        if (lastTab && document.getElementById(lastTab)) {
            $(document.getElementById(lastTab)).tab('show');
        }
    } catch (err) {
        // Sin persistencia de tab: se usa la pestaña por defecto
    }
});

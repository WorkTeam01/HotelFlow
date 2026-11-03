$(document).ready(function () {
    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa, validando el id contra las pestañas
    // reales: lo que venga del almacenamiento no entra en el selector sin comprobar.
    var tabsValidas = ['tab-general', 'tab-inventario', 'tab-precios'];
    var lastTab = localStorage.getItem('lastProductDetailTab');
    if (lastTab && tabsValidas.indexOf(lastTab) !== -1) {
        $('#' + lastTab).tab('show');
    }
});

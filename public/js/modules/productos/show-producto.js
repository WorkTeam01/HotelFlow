$(document).ready(function () {
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastProductDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }
});

/**
 * create-compras.js - Formulario de nueva compra (views/compras/create.php).
 *
 * Solo orquesta: la lógica vive en compra-detalle.js (líneas y total) y
 * compra-selector.js (modal de productos), cargados antes que este archivo junto
 * con ventas/venta-util.js.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form-compra');
    var fecha = document.getElementById('fechacompra');
    var estado = document.getElementById('estado');
    var enviando = false;

    // El estado inicial solo es un <select> para el administrador
    if (estado.tagName === 'SELECT') {
        initializeSelect2('#estado');
    }

    var detalle = CompraDetalle.init();
    var selector = CompraSelector.init({
        productos: JSON.parse(form.dataset.productos || '[]'),
        alElegir: detalle.agregar
    });

    // Precarga desde el dashboard («Comprar» en stock bajo): create.php?producto=ID
    var idProducto = parseInt(new URLSearchParams(window.location.search).get('producto'), 10);
    if (idProducto) {
        var producto = selector.buscarPorId(idProducto);
        if (producto) detalle.agregar(producto);
    }

    fecha.addEventListener('input', function () {
        VentaUtil.marcarInvalido(fecha, !fecha.value);
    });

    function resumenConfirmacion() {
        var observacion = document.getElementById('observaciones').value.trim();
        var corta = observacion.length > 50 ? observacion.substring(0, 50) + '...' : observacion;
        var completada = estado.value === 'completada';
        return '<div class="text-left">' +
            '<p><strong>Total:</strong> ' + detalle.total() + ' Bs.</p>' +
            '<p><strong>Productos:</strong> ' + detalle.cantidadLineas() + '</p>' +
            '<p><strong>Estado:</strong> ' + (completada ? 'Completada (suma stock ahora)' : 'Pendiente') + '</p>' +
            (observacion ? '<p><strong>Observación:</strong> ' + escapeHtml(corta) + '</p>' : '') +
            '<p>¿Está seguro de registrar esta compra?</p></div>';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (enviando) return;

        if (!fecha.value) {
            VentaUtil.marcarInvalido(fecha, true);
            VentaUtil.avisar('Fecha requerida', 'Ingrese la fecha de la compra');
            return;
        }

        if (detalle.cantidadLineas() === 0) {
            VentaUtil.avisar('Sin productos', 'Debe agregar al menos un producto');
            return;
        }

        if (!detalle.validar()) {
            VentaUtil.avisar('Revise los productos', 'Hay cantidades o precios inválidos en el detalle de la compra');
            return;
        }

        Swal.fire({
            title: '¿Confirmar compra?',
            html: resumenConfirmacion(),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: themeColor('primary'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed || enviando) return;
            // Un segundo envío (doble clic, Enter repetido) registraría la compra dos veces
            enviando = true;
            mostrarCarga('Guardando...', 'Registrando la compra');
            form.submit();
        });
    });
});

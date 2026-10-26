/**
 * create-venta.js - Formulario de nueva venta (views/ventas/create.php).
 *
 * Solo orquesta: la lógica vive en venta-pagos.js (forma de pago),
 * venta-detalle.js (carrito) y venta-selectores.js (modales de producto y
 * cliente), todos cargados antes que este archivo.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('form-venta');
    var fecha = document.getElementById('fechaventa');
    var enviando = false;

    var clientes = JSON.parse(form.dataset.clientes || '[]');

    var pagos = VentaPagos.init();
    var detalle = VentaDetalle.init({ alCambiarTotal: pagos.setTotal });
    var selectores = VentaSelectores.init({
        productos: JSON.parse(form.dataset.productos || '[]'),
        clientes: clientes,
        alElegirProducto: detalle.agregar
    });

    // Preseleccionar cliente si se llegó desde su ficha (views/clientes/show.php)
    var idCliente = new URLSearchParams(window.location.search).get('cliente');
    if (idCliente) {
        var cliente = clientes.find(function (c) {
            return String(c.idpersona) === String(idCliente);
        });
        if (cliente) selectores.seleccionarCliente(cliente);
    }

    fecha.addEventListener('input', function () {
        VentaUtil.marcarInvalido(fecha, !fecha.value);
    });

    function resumenConfirmacion() {
        var observacion = document.getElementById('observacion').value.trim();
        var corta = observacion.length > 50 ? observacion.substring(0, 50) + '...' : observacion;
        return '<div class="text-left">' +
            '<p><strong>Total:</strong> ' + document.getElementById('total-venta').textContent + ' Bs.</p>' +
            '<p><strong>Tipo de pago:</strong> ' + (pagos.tipo() === 'unico' ? 'Pago único' : 'Pago mixto') + '</p>' +
            (observacion ? '<p><strong>Observación:</strong> ' + escapeHtml(corta) + '</p>' : '') +
            '<p>¿Está seguro de registrar esta venta?</p></div>';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (enviando) return;

        if (!fecha.value) {
            VentaUtil.marcarInvalido(fecha, true);
            VentaUtil.avisar('Fecha requerida', 'Ingrese la fecha de la venta');
            return;
        }

        if (!detalle.validar()) {
            VentaUtil.avisar('Revise los productos', 'Hay cantidades, precios o descuentos inválidos en el detalle de la venta');
            return;
        }

        if (!pagos.esValido()) {
            VentaUtil.avisar('Pago incompleto', 'El total pagado no coincide con el total de la venta');
            return;
        }

        Swal.fire({
            title: '¿Confirmar Venta?',
            html: resumenConfirmacion(),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: themeColor('primary'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed || enviando) return;
            // Un segundo envío (doble clic, Enter repetido) registraría la venta dos veces
            enviando = true;
            mostrarCarga('Guardando...', 'Registrando la venta');
            form.submit();
        });
    });
});

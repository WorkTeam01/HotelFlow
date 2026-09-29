/**
 * Lógica de formulario compartida por create.php y update.php de productos.
 *
 * Select2, vista previa de la imagen, validación en vivo de precios/stock y
 * guardas de submit viven aquí para que ambas vistas no mantengan dos copias.
 * Cada vista llama a ProductoForm.init() con su formulario y su mensaje de
 * carga; los umbrales de margen salen de los atributos data-* del <form>
 * (los pone la vista desde config/productos vía ProductoController).
 *
 * El resumen en vivo (#resumen-vivo) solo lo declara create.php: update.php
 * muestra en su columna lateral los datos del registro en BD, no los que se
 * están escribiendo. Si el contenedor no existe, esa parte no se inicializa.
 */
(function (window, $) {
    'use strict';

    function umbrales($form) {
        return {
            alerta: parseInt($form.data('margen-alerta'), 10) || 10,
            bueno: parseInt($form.data('margen-bueno'), 10) || 20
        };
    }

    function inicializarEtiquetaImagen() {
        $('.custom-file-input').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass('selected').html(fileName);

            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#preview-image').attr('src', e.target.result);
                    $('#preview-container').show();
                    $('#preview-placeholder').hide();
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    /**
     * Margen de ganancia clasificado contra los umbrales de config/productos.
     * Único punto de cálculo: lo usan el mensaje bajo el precio de venta y el
     * resumen lateral, para que no diverjan.
     *
     * @returns {{porcentaje:number,ganancia:number,clase:string,icono:string}}
     */
    function clasificarMargen(precioCompra, precioVenta, umbralesForm) {
        var ganancia = precioVenta - precioCompra;
        var porcentaje = (ganancia / precioCompra) * 100;
        var clase = 'text-success';
        var icono = 'fas fa-check-circle';

        if (porcentaje < umbralesForm.alerta) {
            clase = 'text-danger';
            icono = 'fas fa-exclamation-circle';
        } else if (porcentaje < umbralesForm.bueno) {
            clase = 'text-warning';
            icono = 'fas fa-exclamation-triangle';
        }

        return { porcentaje: porcentaje, ganancia: ganancia, clase: clase, icono: icono };
    }

    function inicializarValidacionPrecios(umbralesForm) {
        $('#precioventa, #preciocompra').on('input', function () {
            var precioCompra = parseFloat($('#preciocompra').val()) || 0;
            var precioVenta = parseFloat($('#precioventa').val()) || 0;

            if (precioVenta > 0 && precioCompra > 0) {
                if (precioVenta < precioCompra) {
                    $('#precio-feedback').html('<div class="text-danger mt-1"><i class="fas fa-exclamation-triangle"></i> El precio de venta no puede ser menor al precio de compra</div>');
                    return;
                }

                var margen = clasificarMargen(precioCompra, precioVenta, umbralesForm);

                $('#precio-feedback').html(
                    '<div class="' + margen.clase + ' mt-1">' +
                    '<i class="' + margen.icono + '"></i>' +
                    ' Margen de ganancia: ' + margen.porcentaje.toFixed(2) + '%' +
                    ' (Bs ' + margen.ganancia.toFixed(2) + ')' +
                    '</div>'
                );
            } else {
                $('#precio-feedback').html('');
            }
        });
    }

    /**
     * Refleja lo que se está escribiendo en el formulario en #resumen-vivo.
     * Los badges de estado salen de data-estados (el JSON de
     * ProductoController::estadosProducto()), nunca de un badge hardcodeado.
     */
    function inicializarResumenVivo(umbralesForm) {
        var $resumen = $('#resumen-vivo');
        if (!$resumen.length) {
            return;
        }

        function textoO(sel, vacio) {
            var valor = $(sel).val();
            return (valor === null || valor === undefined || valor === '') ? vacio : valor;
        }

        function estadoPorValor(valor) {
            var mapa = $resumen.data('estados') || {};
            var encontrado = null;
            Object.keys(mapa).forEach(function (clave) {
                if (parseInt(mapa[clave].valor, 10) === parseInt(valor, 10)) {
                    encontrado = mapa[clave];
                }
            });
            return encontrado;
        }

        function pintar() {
            var precioCompra = parseFloat($('#preciocompra').val()) || 0;
            var precioVenta = parseFloat($('#precioventa').val()) || 0;

            $('#rv-nombre').text(textoO('#nombre', 'Sin nombre'));
            $('#rv-codigo').text(textoO('#codigo', '—'));
            $('#rv-categoria').text($('#idcategoria').val() ? $('#idcategoria option:selected').text().trim() : '—');
            $('#rv-compra').text('Bs ' + precioCompra.toFixed(2));
            $('#rv-venta').text('Bs ' + precioVenta.toFixed(2));
            $('#rv-stock').text(parseInt($('#stock').val(), 10) || 0);

            var $margen = $('#rv-margen').removeClass('text-success text-warning text-danger');
            if (precioCompra > 0 && precioVenta >= precioCompra) {
                var margen = clasificarMargen(precioCompra, precioVenta, umbralesForm);
                $margen
                    .text(margen.porcentaje.toFixed(2) + '% (Bs ' + margen.ganancia.toFixed(2) + ')')
                    .addClass(margen.clase);
            } else {
                $margen.text('—');
            }

            var estado = estadoPorValor($('#estado').val());
            if (estado) {
                $('#rv-estado')
                    .text(estado.label)
                    .attr('class', 'badge font-weight-bold ' + estado.badge);
            }
        }

        $('#nombre, #codigo, #idcategoria, #estado, #preciocompra, #precioventa, #stock')
            .on('input change', pintar);

        pintar();
    }

    function inicializarValidacionStock() {
        $('#stock_minimo, #stock_maximo').on('input', function () {
            var stockMinimo = parseInt($('#stock_minimo').val()) || 0;
            var stockMaximo = parseInt($('#stock_maximo').val()) || 0;

            if (stockMaximo > 0) {
                if (stockMaximo < stockMinimo) {
                    $('#stock-validation-feedback').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle"></i>' +
                        ' El stock máximo (' + stockMaximo + ') no puede ser menor al stock mínimo (' + stockMinimo + ')' +
                        '</div>'
                    );
                } else {
                    $('#stock-validation-feedback').html(
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle"></i>' +
                        ' Configuración de stock válida. Rango: ' + stockMinimo + ' - ' + stockMaximo + ' unidades' +
                        '</div>'
                    );
                }
            } else {
                $('#stock-validation-feedback').html('');
            }
        });
    }

    function inicializarGuardasDeEnvio($form, opciones) {
        $form.on('submit', function (e) {
            var precioCompra = parseFloat($('#preciocompra').val()) || 0;
            var precioVenta = parseFloat($('#precioventa').val()) || 0;
            var stockMinimo = parseInt($('#stock_minimo').val()) || 0;
            var stockMaximo = parseInt($('#stock_maximo').val()) || 0;

            if (precioVenta < precioCompra) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en Precios',
                    text: 'El precio de venta no puede ser menor al precio de compra',
                    confirmButtonColor: themeColor('primary')
                });
                return false;
            }

            if (stockMaximo > 0 && stockMaximo < stockMinimo) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en Stock',
                    text: 'El stock máximo no puede ser menor al stock mínimo',
                    confirmButtonColor: themeColor('primary')
                });
                return false;
            }

            if ($('#idcategoria').val() === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Categoría Requerida',
                    text: 'Debe seleccionar una categoría para el producto',
                    confirmButtonColor: themeColor('primary')
                });
                return false;
            }

            mostrarCarga(opciones.tituloCarga, opciones.textoCarga);
        });
    }

    /**
     * @param {{form:string,tituloCarga:string,textoCarga:string}} opciones
     */
    function init(opciones) {
        var $form = $(opciones.form);
        var umbralesForm = umbrales($form);

        initializeSelect2();
        inicializarEtiquetaImagen();
        inicializarValidacionPrecios(umbralesForm);
        inicializarValidacionStock();
        inicializarResumenVivo(umbralesForm);

        $('#precioventa').trigger('input');
        if ($('#stock_maximo').val()) {
            $('#stock_maximo').trigger('input');
        }

        inicializarGuardasDeEnvio($form, opciones);
    }

    window.ProductoForm = { init: init };
})(window, jQuery);

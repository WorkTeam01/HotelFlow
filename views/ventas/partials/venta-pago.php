<?php
/**
 * Partial: tarjeta "Forma de Pago" (pago único o mixto) del formulario de nueva venta.
 *
 * No requiere variables del include-r. La lógica vive en venta-pagos.js; las
 * tarjetas del pago mixto salen de partials/plantilla-metodo-pago.php.
 *
 * Solo pinta HTML.
 */
?>
<div class="card card-outline card-primary" id="card-forma-pago">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-money-check-alt"></i> Forma de Pago
            <span class="text-danger">*</span>
        </h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                <label class="btn btn-touch btn-outline-primary active" id="btn-pago-unico">
                    <input type="radio" name="tipo_pago" value="unico" checked> Pago Único
                </label>
                <label class="btn btn-touch btn-outline-primary" id="btn-pago-mixto">
                    <input type="radio" name="tipo_pago" value="mixto"> Pago Mixto
                </label>
            </div>
        </div>

        <!-- Pago único -->
        <div id="seccion-pago-unico">
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="metodopago-unico">Método de Pago <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="metodopago-unico" name="metodopago_unico" required>
                        <option value="Efectivo" selected>Efectivo</option>
                        <option value="QR">QR</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="monto-unico">Monto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Bs.</span>
                        </div>
                        <input type="number" class="form-control" id="monto-unico" name="monto_unico" step="0.01" min="0" value="0.00" readonly>
                    </div>
                </div>
                <div class="form-group col-md-4" id="div-pago-recibido-unico">
                    <label for="pago-recibido-unico">Pago Recibido</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Bs.</span>
                        </div>
                        <input type="number" class="form-control" id="pago-recibido-unico" name="pago_recibido_unico" step="0.01" min="0" value="0.00" required>
                        <div class="invalid-feedback">El pago recibido no cubre el total</div>
                    </div>
                </div>
            </div>
            <div class="form-group" id="div-cambio-unico" hidden>
                <label for="cambio-unico">Cambio:</label>
                <div class="input-group vent-cambio-ancho">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Bs.</span>
                    </div>
                    <input type="text" class="form-control" id="cambio-unico" readonly>
                    <input type="hidden" name="cambio_unico" id="cambio-unico-hidden" value="0.00">
                </div>
            </div>
        </div>

        <!-- Pagos mixtos -->
        <div id="seccion-pago-mixto" hidden>
            <button type="button" id="btn-agregar-pago" class="btn btn-touch btn-success btn-sm mb-3">
                <i class="fas fa-plus"></i> Agregar método de pago
            </button>
            <div id="contenedor-pagos"></div>
        </div>

        <hr>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Total Pagado</span>
            <strong id="total-pagado-display" class="text-success">0.00 Bs.</strong>
        </div>
        <small id="diferencia-pago" class="d-block text-right" aria-live="polite"></small>
    </div>
</div>

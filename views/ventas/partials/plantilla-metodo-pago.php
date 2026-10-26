<?php
/**
 * Partial: plantilla de una tarjeta de método de pago del pago mixto.
 *
 * No requiere variables del include-r. Va fuera del <form> y dentro de un
 * <template>, así sus campos no se envían ni sus ids chocan con los del
 * documento; venta-pagos.js la clona y regenera los ids de cada copia.
 *
 * Solo pinta HTML.
 */
?>
<template id="template-metodo-pago">
    <div class="card metodo-pago-item mb-3">
        <div class="card-header">
            <h3 class="card-title metodo-pago-titulo">Método de pago</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="metodopago-mixto">Método de Pago <span class="text-danger">*</span></label>
                <select class="form-control select2 select-metodo-pago" id="metodopago-mixto" name="metodopago[]" required>
                    <option value="Efectivo">Efectivo</option>
                    <option value="QR">QR</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div class="form-group">
                <label for="monto-mixto">Monto <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Bs.</span>
                    </div>
                    <input type="number" class="form-control monto-pago" id="monto-mixto" name="montopago[]" step="0.01" min="0" value="0.00" required>
                    <div class="invalid-feedback">Ingrese un monto válido</div>
                </div>
            </div>
            <div class="form-group div-pago-recibido">
                <label for="pagorecibido-mixto">Pago Recibido</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Bs.</span>
                    </div>
                    <input type="number" class="form-control pago-recibido" id="pagorecibido-mixto" name="pagorecibido[]" step="0.01" min="0" value="0.00">
                    <div class="invalid-feedback">El pago recibido no cubre el monto</div>
                </div>
            </div>
            <div class="form-group div-cambio" hidden>
                <label for="cambio-mixto">Cambio:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Bs.</span>
                    </div>
                    <input type="text" class="form-control cambio-pago" id="cambio-mixto" readonly>
                    <input type="hidden" name="cambio[]" class="cambio-hidden" value="0.00">
                </div>
            </div>
            <button type="button" class="btn btn-touch btn-danger btn-sm btn-eliminar-pago" aria-label="Eliminar método de pago">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</template>

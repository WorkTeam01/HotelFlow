<?php
/**
 * Partial: totales de la venta y botones de acción (columna lateral).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL  base de la aplicación
 *
 * Solo pinta HTML; los importes los escribe venta-detalle.js.
 */
?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <span class="text-muted">Subtotal</span>
            <span><span id="subtotal-venta">0.00</span> Bs.</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Descuento</span>
            <span><span id="descuento-total">0.00</span> Bs.</span>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h5 mb-0">Total</span>
            <span class="h3 mb-0 text-primary"><span id="total-venta">0.00</span> Bs.</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <button type="submit" class="btn btn-primary btn-lg btn-block">
            <i class="fas fa-save"></i> Registrar Venta
        </button>
        <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-touch btn-secondary btn-block">
            <i class="fas fa-times"></i> Cancelar
        </a>
    </div>
</div>

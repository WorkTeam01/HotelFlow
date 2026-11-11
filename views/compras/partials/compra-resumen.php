<?php
/**
 * Partial: total de la compra y botones de acción (columna lateral).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL  base de la aplicación
 *
 * Solo pinta HTML; el importe lo escribe compra-detalle.js. El total que se guarda lo
 * recalcula el servidor desde las líneas.
 */
?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h5 mb-0">Total</span>
            <span class="h3 mb-0 comp-total" aria-live="polite"><span id="total-compra">0.00</span> Bs.</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <button type="submit" class="btn btn-primary btn-lg btn-block">
            <i class="fas fa-save" aria-hidden="true"></i> Registrar compra
        </button>
        <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-touch btn-secondary btn-block">
            <i class="fas fa-times" aria-hidden="true"></i> Cancelar
        </a>
    </div>
</div>

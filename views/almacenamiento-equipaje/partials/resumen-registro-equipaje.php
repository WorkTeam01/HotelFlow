<?php

/**
 * Partial: panel resumen del registro de equipaje (columna derecha sticky).
 * Incluido desde views/almacenamiento-equipaje/create.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL (global de la app)
 *
 * Solo pinta HTML. Los ids #resumen-* los rellena create-equipaje.js en vivo.
 * El botón de envío apunta al form del partial form-registro-equipaje.php.
 */
?>
<div class="card card-outline card-info equipaje-resumen sticky-top">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Resumen</h3>
    </div>
    <div class="card-body">
        <dl class="equipaje-resumen__lista mb-0">
            <dt>Cliente</dt>
            <dd id="resumen-cliente">No seleccionado</dd>
            <dt>Tipo de equipaje</dt>
            <dd id="resumen-tipo">No seleccionado</dd>
            <dt>Piezas</dt>
            <dd id="resumen-cantidad">1</dd>
            <dt>Entrada</dt>
            <dd id="resumen-fecha"><?= date('d/m/Y H:i'); ?></dd>
            <dt>Método de pago</dt>
            <dd id="resumen-metodopago">Efectivo</dd>
            <dt>Descripción</dt>
            <dd id="resumen-descripcion">-</dd>
        </dl>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <span>Monto total</span>
            <strong id="resumen-monto" class="h5 mb-0">Bs. 0.00</strong>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-outline-secondary btn-block mb-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <button type="submit" form="formRegistroEquipaje" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-2"></i> Registrar Equipaje
        </button>
    </div>
</div>
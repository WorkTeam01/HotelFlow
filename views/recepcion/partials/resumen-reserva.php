<?php

/**
 * Partial: panel resumen de la nueva reserva (página única, columna derecha sticky).
 * Incluido desde views/recepcion/create.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - (ninguna variable de datos: los valores los rellena create-recepcion.js en vivo)
 *
 * Solo pinta HTML.
 */
?>
<div class="card card-primary card-outline rec-resumen sticky-top">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Resumen</h3></div>
    <div class="card-body">
        <dl class="rec-resumen__lista mb-0">
            <dt>Habitación</dt>
            <dd id="rs-habitacion">Sin seleccionar</dd>
            <dt>Huésped</dt>
            <dd id="rs-huesped">Sin seleccionar</dd>
            <dt>Entrada</dt>
            <dd id="rs-entrada">—</dd>
            <dt>Salida prevista</dt>
            <dd id="rs-salida">—</dd>
            <dt>Duración</dt>
            <dd id="rs-noches">—</dd>
            <dt>Tarifa</dt>
            <dd id="rs-tarifa">Sin seleccionar</dd>
        </dl>
        <hr>
        <div class="d-flex justify-content-between"><span>Total</span><strong id="rs-total">Bs 0.00</strong></div>
        <div class="d-flex justify-content-between"><span>Adelanto</span><span id="rs-adelanto">Bs 0.00</span></div>
        <div class="d-flex justify-content-between"><span>Saldo</span><strong id="rs-saldo" class="text-danger">Bs 0.00</strong></div>
    </div>
    <div class="card-footer">
        <a href="<?= $URL; ?>views/recepcion/index.php" class="btn btn-outline-secondary btn-block mb-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <button type="submit" form="formReserva" class="btn btn-success btn-block btn-lg" id="btn-submit-side">
            <i class="fas fa-check mr-2"></i><span id="btn-text-side">Confirmar reserva</span>
        </button>
    </div>
</div>

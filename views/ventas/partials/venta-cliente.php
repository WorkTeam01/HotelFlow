<?php
/**
 * Partial: tarjeta de fecha y cliente de la venta (columna lateral).
 *
 * No requiere variables del include-r. El cliente es opcional: sin elegir
 * ninguno la venta queda como "Consumidor Final". La ficha la actualiza
 * venta-selectores.js.
 *
 * Solo pinta HTML.
 */
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Cliente</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="fechaventa">Fecha de venta <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="fechaventa" name="fechaventa" value="<?= date('Y-m-d'); ?>" required>
            <div class="invalid-feedback">Ingrese una fecha válida</div>
        </div>
        <div id="sin-cliente-seleccionado">
            <p class="text-muted mb-2">Ningún cliente seleccionado</p>
            <button type="button" class="btn btn-touch btn-outline-primary btn-block" id="btn-abrir-modal-clientes" data-toggle="modal" data-target="#modal-clientes">
                <i class="fas fa-search"></i> Buscar Cliente
            </button>
        </div>
        <div id="info-cliente-seleccionado" hidden>
            <input type="hidden" id="idcliente" name="idcliente">
            <strong id="nombre-cliente"></strong>
            <div id="documento-cliente" class="text-muted small"></div>
            <button type="button" class="btn btn-touch btn-outline-secondary btn-sm btn-block mt-2" id="btn-cambiar-cliente">
                <i class="fas fa-exchange-alt"></i> Cambiar cliente
            </button>
        </div>
    </div>
</div>

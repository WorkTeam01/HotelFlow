<?php

/**
 * Partial: buscador global de reserva/huésped (Select2 remoto).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL  (global de la app)
 *
 * El JS del módulo inicializa el Select2 sobre #rec-buscador-global apuntando a
 * controllers/recepcion/buscar_ajax.php y navega a la URL del resultado elegido.
 * Solo pinta HTML.
 */
?>
<div class="form-group mb-0" style="min-width: 260px;">
    <select id="rec-buscador-global" class="form-control form-control-sm"
        data-url="<?= htmlspecialchars($URL); ?>controllers/recepcion/buscar_ajax.php"
        aria-label="Buscar reserva o huésped">
        <option value=""></option>
    </select>
</div>

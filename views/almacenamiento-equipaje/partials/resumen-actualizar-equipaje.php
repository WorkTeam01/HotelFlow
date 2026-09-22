<?php

/**
 * Partial: panel resumen de la edición de equipaje (columna derecha sticky).
 * Incluido desde views/almacenamiento-equipaje/update.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL        (global de la app)
 * - $equipaje   fila decorada de AlmacenamientoEquipajeController::editar()
 * - $estado_ui  array de estado_ui del equipaje
 * - $tiempo     array de tiempo_almacenado del equipaje
 *
 * Solo pinta HTML. Los ids #resumen-estado / #resumen-cliente /
 * #resumen-descripcion los rellena update-equipaje.js en vivo.
 * El botón de envío apunta al form del partial form-actualizar-equipaje.php.
 */
?>
<div class="card card-outline card-info equipaje-resumen sticky-top">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Resumen de cambios</h3>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <span id="resumen-estado" class="badge <?= $estado_ui['badge']; ?> badge-lg">
                <i class="fas fa-<?= $estado_ui['icono']; ?>"></i> <?= $estado_ui['label']; ?>
            </span>
        </div>

        <dl class="equipaje-resumen__lista mb-0">
            <dt>Cliente</dt>
            <dd id="resumen-cliente"><?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'No seleccionado'); ?></dd>
            <dt>Descripción</dt>
            <dd id="resumen-descripcion"><?= htmlspecialchars($equipaje['descripcion'] ?? '-'); ?></dd>
            <dt>Tipo de equipaje</dt>
            <dd><?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'No seleccionado'); ?></dd>
            <dt>Piezas</dt>
            <dd><?= (int)$equipaje['cantidad_piezas']; ?></dd>
            <dt>Monto</dt>
            <dd>Bs. <?= number_format($equipaje['monto'], 2); ?></dd>
        </dl>

        <hr>
        <h6 class="mb-2"><i class="fas fa-calendar-day mr-1"></i> Tiempo almacenado</h6>
        <p class="lead mb-1"><?= $tiempo['texto']; ?></p>
        <?php
        $porcentaje = $tiempo['porcentaje'];
        $color_barra = $porcentaje < 50 ? 'success' : ($porcentaje < 75 ? 'warning' : 'danger');
        ?>
        <div class="progress">
            <div class="progress-bar bg-<?= $color_barra; ?>" role="progressbar"
                style="width: <?= $porcentaje; ?>%"
                aria-valuenow="<?= $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= $URL; ?>views/almacenamiento-equipaje/show.php?id=<?= $equipaje['idalmacen']; ?>"
            class="btn btn-info btn-block mb-2">
            <i class="fas fa-eye"></i> Ver detalles
        </a>
        <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-outline-secondary btn-block mb-2">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" form="formActualizarEquipaje" class="btn btn-warning btn-block btn-lg">
            <i class="fas fa-save mr-2"></i> Guardar cambios
        </button>
        <p class="text-muted small mb-0 mt-2">
            Para marcar como "Retirado", hazlo desde la página de detalles.
        </p>
    </div>
</div>

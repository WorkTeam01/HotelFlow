<?php

/**
 * Partial: tab "Mapa" del panel de Recepción — room rack denso agrupado por piso.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL   (global de la app)
 * - $mapa  array de RecepcionController::mapa() con: habitaciones_por_piso, pisos, contadores
 *
 * Cada fila ya trae la doble dimensión y la acción primaria resueltas por el controlador.
 * El tile lo pinta partials/tile-habitacion.php. Solo pinta HTML.
 */

$c = $mapa['contadores'];
?>
<div class="rec-leyenda">
    <span><span class="rec-dot bg-success"></span> Disponible <?= (int) $c['disponible']; ?></span>
    <span><span class="rec-dot bg-warning"></span> Ocupada <?= (int) $c['ocupada']; ?></span>
    <span><span class="rec-dot bg-info"></span> Reservada <?= (int) $c['reservada']; ?></span>
    <span><span class="rec-dot bg-secondary"></span> Por limpiar <?= (int) $c['limpieza']; ?></span>
    <span><span class="rec-dot bg-danger"></span> Mantenimiento <?= (int) $c['mantenimiento']; ?></span>
</div>

<?php if (empty($mapa['habitaciones_por_piso'])): ?>
    <div class="rec-empty">
        <i class="fas fa-door-closed"></i>
        <div>No hay habitaciones registradas.</div>
    </div>
<?php else: ?>
    <?php foreach ($mapa['habitaciones_por_piso'] as $piso => $filas): ?>
        <h6 class="text-muted mt-3 mb-2"><i class="fas fa-building mr-1"></i><?= htmlspecialchars($piso); ?></h6>
        <div class="rec-rack">
            <?php foreach ($filas as $t): ?>
                <?php include __DIR__ . '/tile-habitacion.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

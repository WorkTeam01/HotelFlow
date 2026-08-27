<?php

/**
 * Partial: tab "Mapa" del panel de Recepción — room rack agrupado por piso.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL   (global de la app)
 * - $mapa  array de RecepcionController::mapa() con: habitaciones_por_piso, pisos, contadores
 *
 * Cada fila ya trae doble dimensión resuelta por el controlador:
 *  - 'estado_ui'       → ocupación (color de la franja + acción primaria)
 *  - 'housekeeping_ui' → limpieza/mantenimiento (punto en la esquina)
 *
 * Solo pinta HTML. El tile denso definitivo se extrae a partials/tile-habitacion.php en la Fase 3.
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
                <?php
                $ocupacion = $t['estado_ui'];
                $hk = $t['housekeeping_ui'];
                $mostrarHk = in_array($t['estado'], ['limpieza', 'mantenimiento'], true);

                if (!empty($t['idrecepcion'])) {
                    $href = $URL . 'views/recepcion/show.php?id=' . (int) $t['idrecepcion'];
                    $accionLabel = 'Ver folio';
                } elseif ($mostrarHk) {
                    $href = $URL . 'views/habitaciones/show.php?id=' . (int) $t['id_habitacion'];
                    $accionLabel = 'Ver habitación';
                } else {
                    $href = $URL . 'views/recepcion/create.php?idhabitacion=' . (int) $t['id_habitacion'];
                    $accionLabel = 'Check-in';
                }
                ?>
                <div class="rec-tile-col col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="rec-tile">
                        <div class="rec-tile__strip bg-<?= $ocupacion['clase']; ?>"></div>
                        <?php if ($mostrarHk): ?>
                            <span class="rec-tile__hk text-<?= $hk['clase']; ?>" title="<?= htmlspecialchars($hk['label']); ?>">
                                <i class="fas fa-<?= $hk['icono']; ?>"></i>
                            </span>
                        <?php endif; ?>
                        <div class="rec-tile__body">
                            <h5 class="rec-tile__num"><?= htmlspecialchars($t['numero']); ?></h5>
                            <span class="small text-muted d-block text-truncate"><?= htmlspecialchars($t['tipo_nombre'] ?? ''); ?></span>
                            <?php if (!empty($t['huesped'])): ?>
                                <span class="rec-tile__huesped small"><?= htmlspecialchars($t['huesped']); ?></span>
                            <?php else: ?>
                                <span class="badge <?= $ocupacion['badge']; ?> badge-sm"><?= htmlspecialchars($ocupacion['label']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="rec-tile__action">
                            <a href="<?= $href; ?>" class="btn btn-sm btn-block btn-outline-<?= $ocupacion['clase']; ?>">
                                <?= htmlspecialchars($accionLabel); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

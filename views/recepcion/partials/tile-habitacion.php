<?php

/**
 * Partial: tile denso de habitación para el room rack (tab "Mapa").
 *
 * Reemplaza a card-habitacion.php: una sola rama, sin lógica de negocio.
 * Doble dimensión — ocupación (franja de color + acción) y housekeeping (punto en la esquina).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL   (global de la app)
 * - $t     fila normalizada por RecepcionController::mapa(), con:
 *          'id_habitacion', 'numero', 'tipo_nombre', 'estado' (housekeeping crudo),
 *          'estado_ui' (['label','clase','badge','icono']), 'housekeeping_ui' (idem),
 *          'mostrar_hk' (bool), 'huesped'|null, 'idrecepcion'|null,
 *          'accion' => ['label', 'href' (relativo a $URL), 'clase']
 *
 * Solo pinta HTML.
 */

$ocupacion = $t['estado_ui'];
$hk = $t['housekeeping_ui'];
$accion = $t['accion'];
?>
<div class="rec-tile-col col-6 col-sm-4 col-md-3 col-lg-2">
    <div class="rec-tile rec-tile--<?= $ocupacion['clase']; ?>">
        <div class="rec-tile__strip bg-<?= $ocupacion['clase']; ?>"></div>
        <?php if (!empty($t['mostrar_hk'])): ?>
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
            <a href="<?= $URL . $accion['href']; ?>" class="btn btn-sm btn-block btn-outline-<?= $accion['clase']; ?>">
                <?= htmlspecialchars($accion['label']); ?>
            </a>
        </div>
    </div>
</div>

<?php

/**
 * Partial: una fila de movimiento del tab "Hoy" (llegada / salida / en casa).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL   (global de la app)
 * - $tipo  string: 'llegada' | 'salida' | 'in_house'
 * - $mov   array de recepción ya decorado por el controlador, con:
 *          idrecepcion, nombre_cliente, apellido_cliente, numero_habitacion,
 *          fechaentrada, fechasalida_prevista, estado_ui (label/badge/icono),
 *          estado_derivado, y para in_house: saldo
 *
 * Solo pinta HTML.
 */

$nombre = trim(($mov['nombre_cliente'] ?? '') . ' ' . ($mov['apellido_cliente'] ?? ''));
$hab = $mov['numero_habitacion'] ?? 'N/A';
$estadoUi = $mov['estado_ui'] ?? RecepcionController::estadoRecepcion($mov['estado'] ?? '');
$showUrl = $URL . 'views/recepcion/show.php?id=' . (int) $mov['idrecepcion'];

if ($tipo === 'llegada') {
    $hora = !empty($mov['fechaentrada']) ? date('H:i', strtotime($mov['fechaentrada'])) : '';
    $metaIcono = 'sign-in-alt';
} elseif ($tipo === 'salida') {
    $hora = !empty($mov['fechasalida_prevista']) ? date('H:i', strtotime($mov['fechasalida_prevista'])) : '';
    $metaIcono = 'sign-out-alt';
} else {
    $hora = !empty($mov['fechasalida_prevista']) ? 'Sale ' . date('d/m', strtotime($mov['fechasalida_prevista'])) : '';
    $metaIcono = 'bed';
}
?>
<div class="rec-mov">
    <div class="rec-mov__info">
        <div class="rec-mov__nombre"><?= htmlspecialchars($nombre !== '' ? $nombre : 'Sin huésped'); ?></div>
        <div class="rec-mov__meta text-muted">
            <i class="fas fa-door-closed mr-1"></i>Hab. <?= htmlspecialchars($hab); ?>
            <?php if ($hora !== ''): ?>
                <span class="ml-2"><i class="fas fa-<?= $metaIcono; ?> mr-1"></i><?= htmlspecialchars($hora); ?></span>
            <?php endif; ?>
            <span class="badge <?= $estadoUi['badge']; ?> ml-2"><?= htmlspecialchars($estadoUi['label']); ?></span>
            <?php if ($tipo === 'in_house' && isset($mov['saldo']) && (float) $mov['saldo'] > 0.01): ?>
                <span class="badge badge-danger ml-1">Saldo Bs <?= number_format((float) $mov['saldo'], 2); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="rec-mov__accion">
        <?php if ($tipo === 'llegada'): ?>
            <button type="button" class="btn btn-sm btn-success rec-accion-checkin" data-id="<?= (int) $mov['idrecepcion']; ?>" aria-label="Check-in de <?= htmlspecialchars($nombre !== '' ? $nombre : 'huésped'); ?>, habitación <?= htmlspecialchars($hab); ?>">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i> <span class="d-none d-sm-inline">Check-in</span>
            </button>
        <?php elseif ($tipo === 'salida'): ?>
            <button type="button" class="btn btn-sm btn-warning rec-accion-checkout"
                data-id="<?= (int) $mov['idrecepcion']; ?>"
                data-habitacion="<?= htmlspecialchars($hab); ?>"
                data-cliente="<?= htmlspecialchars($nombre); ?>"
                aria-label="Check-out de <?= htmlspecialchars($nombre !== '' ? $nombre : 'huésped'); ?>, habitación <?= htmlspecialchars($hab); ?>">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i> <span class="d-none d-sm-inline">Check-out</span>
            </button>
        <?php else: ?>
            <a href="<?= $showUrl; ?>" class="btn btn-sm btn-info" aria-label="Ver folio de <?= htmlspecialchars($nombre !== '' ? $nombre : 'huésped'); ?>, habitación <?= htmlspecialchars($hab); ?>">
                <i class="fas fa-folder-open" aria-hidden="true"></i> <span class="d-none d-sm-inline">Ver folio</span>
            </a>
        <?php endif; ?>
    </div>
</div>

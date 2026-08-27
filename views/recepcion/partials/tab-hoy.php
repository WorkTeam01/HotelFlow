<?php

/**
 * Partial: tab "Hoy" del panel de Recepción — llegadas, salidas previstas y en casa.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL   (global de la app)
 * - $hoy   array de RecepcionController::hoy() con: llegadas, salidas, in_house, contadores
 *
 * Solo pinta HTML; incluye partials/fila-movimiento.php por cada fila.
 */

$columnas = [
    ['tipo' => 'llegada', 'titulo' => 'Llegadas', 'icono' => 'sign-in-alt', 'clase' => 'info',
        'items' => $hoy['llegadas'], 'count' => $hoy['contadores']['llegadas'],
        'vacio' => 'Sin llegadas para hoy', 'accion_vacio' => ['Crear reserva', $URL . 'views/recepcion/create.php']],
    ['tipo' => 'salida', 'titulo' => 'Salidas', 'icono' => 'sign-out-alt', 'clase' => 'warning',
        'items' => $hoy['salidas'], 'count' => $hoy['contadores']['salidas'],
        'vacio' => 'Sin salidas previstas para hoy', 'accion_vacio' => null],
    ['tipo' => 'in_house', 'titulo' => 'En casa', 'icono' => 'bed', 'clase' => 'secondary',
        'items' => $hoy['in_house'], 'count' => $hoy['contadores']['in_house'],
        'vacio' => 'No hay huéspedes en casa', 'accion_vacio' => null],
];
?>
<div class="row">
    <?php foreach ($columnas as $col): ?>
        <div class="col-lg-4 mb-3">
            <div class="card card-outline card-<?= $col['clase']; ?> h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-<?= $col['icono']; ?> mr-2"></i><?= $col['titulo']; ?>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-<?= $col['clase']; ?>" aria-live="polite"><?= (int) $col['count']; ?></span>
                    </div>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($col['items'])): ?>
                        <div class="rec-empty">
                            <i class="fas fa-<?= $col['icono']; ?>"></i>
                            <div><?= htmlspecialchars($col['vacio']); ?></div>
                            <?php if ($col['accion_vacio']): ?>
                                <a href="<?= htmlspecialchars($col['accion_vacio'][1]); ?>" class="btn btn-sm btn-outline-<?= $col['clase']; ?> mt-2">
                                    <?= htmlspecialchars($col['accion_vacio'][0]); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($col['items'] as $mov): ?>
                            <?php $tipo = $col['tipo']; ?>
                            <?php include __DIR__ . '/fila-movimiento.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

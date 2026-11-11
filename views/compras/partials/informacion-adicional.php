<?php
/**
 * Partial: observaciones y aviso del efecto de la compra sobre el inventario.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $compra  de CompraController::ver() (con 'estado_ui')
 *
 * Solo pinta HTML.
 */
$estadoUi = $compra['estado_ui'];
$tieneObservaciones = !empty($compra['observaciones']);
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Información adicional</h3>
    </div>
    <div class="card-body">
        <?php if ($tieneObservaciones) : ?>
            <div class="form-group">
                <p class="font-weight-bold mb-1">Observaciones:</p>
                <div class="p-2 bg-light rounded">
                    <?= nl2br(htmlspecialchars($compra['observaciones'] ?? '')); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($estadoUi['alerta'] !== '') : ?>
            <div class="alert alert-<?= $estadoUi['clase']; ?> mb-0" role="status">
                <i class="icon fas <?= $estadoUi['icono']; ?>" aria-hidden="true"></i>
                <?= htmlspecialchars($estadoUi['alerta']); ?>
                <?php if ($compra['estado'] !== 'pendiente' && !empty($compra['fechaactualizacion'])) : ?>
                    <small class="d-block mt-1">Actualizada el <?= date('d/m/Y H:i', strtotime($compra['fechaactualizacion'])); ?>.</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

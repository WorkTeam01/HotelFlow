<?php

/**
 * Partial: tab "Precios" del detalle de Producto — precios de venta y compra
 * más el análisis de rentabilidad.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $producto     fila de producto (precioventa, preciocompra)
 * - $rentabilidad resultado de ProductoController::calcularRentabilidad($producto)
 *
 * Solo pinta HTML; no hace queries ni cálculos.
 */
?>
<div class="row">
    <div class="col-md-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-tag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Precio de Venta</span>
                <span class="info-box-number">Bs <?= number_format($producto['precioventa'], 2); ?></span>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Precio de Compra</span>
                <span class="info-box-number">Bs <?= number_format($producto['preciocompra'], 2); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Análisis de Rentabilidad</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Ganancia por unidad:</label>
                    <p class="text-<?= $rentabilidad['nivel'] === 'bajo' ? 'danger' : 'success'; ?> font-weight-bold">
                        Bs <?= number_format($rentabilidad['ganancia'], 2); ?>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Margen de ganancia:</label>
                    <p class="text-<?= $rentabilidad['nivel'] === 'bajo' ? 'danger' : 'success'; ?> font-weight-bold">
                        <?= $rentabilidad['margen']; ?>%
                    </p>
                </div>
            </div>
        </div>

        <div class="progress">
            <div class="progress-bar bg-success" role="progressbar"
                aria-valuenow="<?= $rentabilidad['margen']; ?>" aria-valuemin="0" aria-valuemax="100"
                style="width: <?= min(100, $rentabilidad['margen']); ?>%">
                <?= $rentabilidad['margen']; ?>%
            </div>
        </div>

        <div class="mt-3">
            <?php if ($rentabilidad['nivel'] === 'bajo'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong>Margen bajo.</strong> El margen de ganancia está por debajo del <?= $rentabilidad['umbral_alerta']; ?>%.
                </div>
            <?php elseif ($rentabilidad['nivel'] === 'moderado'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Margen moderado.</strong> El margen de ganancia está entre el <?= $rentabilidad['umbral_alerta']; ?>% y el <?= $rentabilidad['umbral_bueno']; ?>%.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Buen margen.</strong> El margen de ganancia es superior al <?= $rentabilidad['umbral_bueno']; ?>%.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
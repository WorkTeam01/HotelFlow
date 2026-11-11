<?php
/**
 * Partial: indicadores del listado de compras (4 info-box).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $estadisticas  array de CompraController::getEstadisticas()
 *                  ('pendientes','monto_pendiente','invertido_mes','compras_mes','producto_top')
 *
 * Solo pinta HTML.
 */
?>
<div class="row">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pendientes por recibir</span>
                <span class="info-box-number"><?= (int) $estadisticas['pendientes']; ?></span>
                <small class="text-muted"><?= number_format($estadisticas['monto_pendiente'], 2); ?> Bs.</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-coins" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Invertido en el mes</span>
                <span class="info-box-number"><?= number_format($estadisticas['invertido_mes'], 2); ?> Bs.</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-truck-loading" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Compras del mes</span>
                <span class="info-box-number"><?= (int) $estadisticas['compras_mes']; ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-box-open" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Producto más repuesto</span>
                <span class="info-box-number">
                    <?= $estadisticas['producto_top'] ? htmlspecialchars($estadisticas['producto_top']['nombre']) : 'Sin compras'; ?>
                </span>
                <?php if ($estadisticas['producto_top']) : ?>
                    <small class="text-muted"><?= (int) $estadisticas['producto_top']['unidades']; ?> unidades este mes</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

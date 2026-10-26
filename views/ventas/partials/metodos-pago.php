<?php
/**
 * Partial: tarjeta de métodos de pago de la venta.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $venta        de VentaController::ver() (cada pago trae 'icono' y 'clase_badge')
 * - $esPagoMixto  bool de $venta['info_metodo_pago']['es_mixto']
 *
 * Solo pinta HTML; los cálculos los hace el controlador.
 */
?>
<!-- Tarjeta de métodos de pago -->
<div class="card <?= $esPagoMixto ? 'card-purple card-outline' : 'card-info card-outline'; ?>">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas <?= $esPagoMixto ? 'fa-money-check-alt' : 'fa-money-bill-wave'; ?>"></i>
            <?= $esPagoMixto ? 'Pago Mixto' : 'Método de Pago'; ?>
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php foreach ($venta['pagos'] as $pago) :
            [$iconoMetodo, $claseMetodo] = $controller->obtenerIconoMetodoPago($pago['metodopago']);
        ?>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge <?= htmlspecialchars($pago['clase_badge'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="<?= htmlspecialchars($pago['icono'], ENT_QUOTES, 'UTF-8'); ?>"></i> <?= htmlspecialchars(ucfirst($pago['metodopago']), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span><?= number_format($pago['monto'], 2); ?> Bs.</span>
            </div>
            <?php if (strtolower($pago['metodopago']) === 'efectivo' && (float)$pago['cambio'] > 0) : ?>
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span>Recibido <?= number_format($pago['pagorecibido'], 2); ?> Bs.</span>
                    <span>Cambio <?= number_format($pago['cambio'], 2); ?> Bs.</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($esPagoMixto) : ?>
            <div class="d-flex justify-content-between font-weight-bold border-top pt-2 mt-1 mb-0">
                <span>Total Pagado</span>
                <span><?= number_format($venta['totales']['total_pagado'], 2); ?> Bs.</span>
            </div>
        <?php endif; ?>
    </div>
</div>

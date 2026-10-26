<?php
/**
 * Partial: tarjeta de productos vendidos con subtotal, descuento y total.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $venta  de VentaController::ver() (con 'desglose_detalles' y 'totales')
 *
 * Solo pinta HTML; los cálculos los hace el controlador.
 */
?>
<!-- Tarjeta de detalles de productos -->
<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title">Productos Vendidos</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Descuento</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $contador = 1;
                    foreach ($venta['desglose_detalles']['lineas'] as $linea) : ?>
                        <tr>
                            <td><?= $contador++; ?></td>
                            <td>
                                <?= htmlspecialchars($linea['producto_nombre']); ?>
                                <small class="text-muted d-block">
                                    Código: <?= htmlspecialchars($linea['producto_codigo'] ?? 'N/A'); ?>
                                </small>
                            </td>
                            <td class="text-right"><?= number_format($linea['precioventa'], 2); ?> Bs.</td>
                            <td class="text-center"><?= $linea['cantidad']; ?></td>
                            <td class="text-right">
                                <?php if ($linea['calculo']['descuento_total'] > 0) : ?>
                                    <span class="text-danger">-<?= number_format($linea['calculo']['descuento_total'], 2); ?> Bs.</span>
                                <?php else : ?>
                                    0.00 Bs.
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= number_format($linea['calculo']['neto'], 2); ?> Bs.</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Subtotal:</th>
                        <th class="text-right"><?= number_format($venta['totales']['subtotal'], 2); ?> Bs.</th>
                    </tr>
                    <?php if ($venta['totales']['descuento'] > 0) : ?>
                        <tr>
                            <th colspan="5" class="text-right">Descuento Total:</th>
                            <th class="text-right text-danger">-<?= number_format($venta['totales']['descuento'], 2); ?> Bs.</th>
                        </tr>
                    <?php endif; ?>
                    <tr class="bg-light">
                        <th colspan="5" class="text-right">Total:</th>
                        <th class="text-right"><?= number_format($venta['totales']['total'], 2); ?> Bs.</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

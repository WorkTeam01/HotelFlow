<?php
/**
 * Partial: tarjeta de productos comprados con subtotal por línea y total.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $compra  de CompraController::ver() (con 'desglose' = ['lineas','total'])
 *
 * Solo pinta HTML; los cálculos los hace el controlador.
 */
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h2 class="card-title">Productos comprados</h2>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer productos comprados">
                <i class="fas fa-minus" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="d-none d-sm-table-cell">#</th>
                        <th>Producto</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">P. unitario</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compra['desglose']['lineas'] as $indice => $linea) : ?>
                        <tr>
                            <td class="d-none d-sm-table-cell"><?= $indice + 1; ?></td>
                            <td>
                                <?= htmlspecialchars($linea['producto_nombre'] ?? ''); ?>
                                <small class="text-muted d-block">
                                    Código: <?= htmlspecialchars($linea['producto_codigo'] ?? 'N/A'); ?>
                                </small>
                            </td>
                            <td class="text-right"><?= (int) $linea['cantidad']; ?></td>
                            <td class="text-right"><?= number_format($linea['preciocompra'], 2); ?></td>
                            <td class="text-right"><?= number_format($linea['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="3" class="text-right d-sm-none">Total:</th>
                        <th colspan="4" class="text-right d-none d-sm-table-cell">Total:</th>
                        <th class="text-right"><?= number_format($compra['desglose']['total'], 2); ?> Bs.</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

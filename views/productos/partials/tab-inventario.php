<?php

/**
 * Partial: tab "Inventario" del detalle de Producto — cifras de stock, barra de
 * progreso, alertas y últimos movimientos.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $producto    fila de producto (stock, stock_minimo, stock_maximo)
 * - $inventario  resultado de ProductoController::calcularInventario($producto)
 * - $movimientos resultado de ProductoController::getUltimosMovimientos(), ya
 *                decorados con label/icono/color
 *
 * Solo pinta HTML; no hace queries ni cálculos.
 */
?>
<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $producto['stock']; ?></h3>
                <p>Unidades en Stock</p>
            </div>
            <div class="icon">
                <i class="fas fa-boxes"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $producto['stock_minimo']; ?></h3>
                <p>Stock Mínimo</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= !empty($producto['stock_maximo']) ? $producto['stock_maximo'] : 'N/A'; ?></h3>
                <p>Stock Máximo</p>
            </div>
            <div class="icon">
                <i class="fas fa-warehouse"></i>
            </div>
        </div>
    </div>
</div>

<!-- Estado de Inventario -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Estado de Inventario</h3>
    </div>
    <div class="card-body">
        <div class="progress">
            <div class="progress-bar bg-<?= $inventario['barra']; ?>" role="progressbar"
                aria-valuenow="<?= $inventario['porcentaje']; ?>" aria-valuemin="0" aria-valuemax="100"
                style="width: <?= $inventario['porcentaje']; ?>%">
                <?= $inventario['porcentaje']; ?>%
            </div>
        </div>

        <div class="mt-3">
            <?php if ($inventario['alerta'] === 'bajo'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong>¡Alerta de stock bajo!</strong> El stock actual está por debajo del mínimo recomendado.
                    Se recomienda realizar una compra pronto.
                </div>
            <?php elseif ($inventario['alerta'] === 'cercano'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Stock cercano al mínimo.</strong> El stock actual se está acercando al nivel mínimo recomendado.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Stock saludable.</strong> El nivel de inventario está en un nivel óptimo.
                </div>
            <?php endif; ?>

            <?php if ($inventario['sobre_maximo']): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Stock sobre el máximo.</strong> El inventario actual supera el nivel máximo recomendado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Últimos Movimientos -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Últimos Movimientos</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($movimientos)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Total</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($movimiento['fecha'])); ?></td>
                                <td>
                                    <span class="badge badge-<?= $movimiento['color']; ?>">
                                        <i class="fas <?= $movimiento['icono']; ?> mr-1"></i>
                                        <?= htmlspecialchars($movimiento['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?= $movimiento['tipo_movimiento'] == 'compra' ? 'text-success' : 'text-danger'; ?>">
                                        <?= $movimiento['operacion']; ?><?= $movimiento['cantidad']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($movimiento['tipo_movimiento'] != 'servicio_baño'): ?>
                                        Bs <?= number_format($movimiento['precio'], 2); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($movimiento['tipo_movimiento'] != 'servicio_baño'): ?>
                                        Bs <?= number_format($movimiento['total'], 2); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Consumo interno</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($movimiento['tipo_movimiento'] == 'venta'): ?>
                                        <?= !empty($movimiento['referencia']) ? htmlspecialchars($movimiento['referencia']) : 'Cliente no registrado'; ?>
                                    <?php elseif ($movimiento['tipo_movimiento'] == 'servicio_baño'): ?>
                                        <?= htmlspecialchars($movimiento['referencia']); ?>
                                        <small class="d-block text-muted">Por: <?= htmlspecialchars($movimiento['usuario']); ?></small>
                                    <?php else: ?>
                                        <?= htmlspecialchars($movimiento['referencia']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No hay movimientos registrados para este producto.</p>
        <?php endif; ?>
    </div>
</div>
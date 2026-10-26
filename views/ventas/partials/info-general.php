<?php
/**
 * Partial: tarjeta de información general de la venta (total, código, cliente, estado, acciones).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL          base de la aplicación
 * - $venta        de VentaController::ver() (con 'totales', 'estado_ui')
 * - $codigoVenta  'VENT-000123'
 * - $puedeAnular  bool: venta activa y usuario administrador
 *
 * Solo pinta HTML; los cálculos y permisos los resuelve show.php.
 */
?>
<!-- Tarjeta de información general -->
<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title">Información General</h3>
    </div>
    <div class="card-body box-profile">
        <div class="text-center mb-3">
            <i class="fas fa-file-invoice-dollar fa-3x text-info"></i>
            <h3 class="mt-2 mb-0 text-info font-weight-bold">
                <?= number_format($venta['totales']['total'], 2); ?> Bs.
            </h3>
            <p class="text-muted mb-0">Total Venta</p>
        </div>

        <ul class="list-group list-group-unbordered mb-3">
            <li class="list-group-item">
                <b><i class="fas fa-hashtag mr-2"></i>Código</b>
                <span class="float-right">VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-calendar-alt mr-2"></i>Fecha Venta</b>
                <span class="float-right"><?= date('d/m/Y', strtotime($venta['fechaventa'])); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-user mr-2"></i>Cliente</b>
                <span class="float-right"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-user-tie mr-2"></i>Vendedor</b>
                <span class="float-right"><?= htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                <span class="float-right">
                    <span class="badge <?= $esActiva ? 'badge-success' : 'badge-danger'; ?>" role="status">
                        <?= $esActiva ? 'Activa' : 'Anulada'; ?>
                    </span>
                </span>
            </li>
        </ul>

        <div class="d-flex justify-content-between">
            <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-touch btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

            <?php if ($puedeAnular) : ?>
                <button type="button" class="btn btn-touch btn-danger btn-anular-venta"
                    data-id="<?= (int)$venta['idventa']; ?>"
                    data-titulo="<?= $codigoVenta; ?>"
                    title="Anular venta" data-toggle="tooltip"
                    aria-label="Anular venta <?= $codigoVenta; ?>">
                    <i class="fas fa-ban"></i> Anular
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>


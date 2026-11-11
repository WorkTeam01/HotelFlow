<?php
/**
 * Partial: tarjeta de información general de la compra (total, código, fecha,
 * responsable, estado y acciones).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL        base de la aplicación
 * - $compra     de CompraController::ver() (con 'estado_ui', 'codigo')
 * - $acciones   estados de destino permitidos (CompraController::accionesEstado())
 *
 * Solo pinta HTML; los cálculos y permisos los resuelve show.php.
 */
$estadoUi = $compra['estado_ui'];
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Información general</h3>
    </div>
    <div class="card-body box-profile">
        <div class="text-center mb-3">
            <h3 class="mb-0 comp-total font-weight-bold">
                <?= number_format($compra['totalcompra'], 2); ?> Bs.
            </h3>
            <p class="text-muted mb-0">Total de la compra</p>
        </div>

        <ul class="list-group list-group-unbordered mb-3">
            <li class="list-group-item">
                <b><i class="fas fa-hashtag mr-2" aria-hidden="true"></i>Código</b>
                <span class="float-right"><?= htmlspecialchars($compra['codigo']); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-calendar-alt mr-2" aria-hidden="true"></i>Fecha</b>
                <span class="float-right"><?= date('d/m/Y', strtotime($compra['fechacompra'])); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-user-tie mr-2" aria-hidden="true"></i>Responsable</b>
                <span class="float-right"><?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A'); ?></span>
            </li>
            <li class="list-group-item">
                <b><i class="fas fa-toggle-on mr-2" aria-hidden="true"></i>Estado</b>
                <span class="float-right">
                    <span class="badge <?= $estadoUi['badge']; ?>" role="status">
                        <i class="fas <?= $estadoUi['icono']; ?>" aria-hidden="true"></i>
                        <?= $estadoUi['label']; ?>
                    </span>
                </span>
            </li>
        </ul>

        <?php
        $variante = 'detalle';
        include __DIR__ . '/acciones-estado.php';
        ?>
        <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-touch btn-secondary btn-block">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
        </a>
    </div>
</div>

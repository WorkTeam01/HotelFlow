<?php

/**
 * Partial: tab "Información General" del detalle de Producto — descripción,
 * datos básicos e información del sistema.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL       (global de la app, la define layouts/session.php)
 * - $producto  fila de producto (nombre, categoria_nombre, codigo, descripcion,
 *               fechacreacion, fechaactualizacion)
 * - $estado_ui resultado de ProductoController::estadoProducto($producto['estado'])
 *
 * Solo pinta HTML; no hace queries ni cálculos.
 */
?>
<div class="row">
    <div class="col-12">
        <div class="info-box bg-light">
            <div class="info-box-content">
                <h5 class="info-box-text text-center text-muted">Descripción del Producto</h5>
                <div class="info-box-number text-center text-muted mb-0">
                    <?php if (!empty($producto['descripcion'])): ?>
                        <?= nl2br(htmlspecialchars($producto['descripcion'])); ?>
                    <?php else: ?>
                        <span class="text-muted">No hay descripción disponible</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h3 class="card-title">Datos Básicos</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <td><i class="fas fa-signature mr-2"></i>Nombre:</td>
                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-list-alt mr-2"></i>Categoría:</td>
                            <td><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-barcode mr-2"></i>Código:</td>
                            <td><?= !empty($producto['codigo']) ? htmlspecialchars($producto['codigo']) : 'N/A'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h3 class="card-title">Información del Sistema</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <td><i class="fas fa-calendar-plus mr-2"></i>Fecha Creación:</td>
                            <td><?= isset($producto['fechacreacion']) ? date('d/m/Y', strtotime($producto['fechacreacion'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-calendar-check mr-2"></i>Última Actualización:</td>
                            <td><?= isset($producto['fechaactualizacion']) ? date('d/m/Y', strtotime($producto['fechaactualizacion'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-toggle-on mr-2"></i>Estado:</td>
                            <td>
                                <span class="badge <?= $estado_ui['badge']; ?>"><?= $estado_ui['label']; ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
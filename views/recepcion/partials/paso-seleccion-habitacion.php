<?php

/**
 * Partial: paso 1 del wizard de check-in — selección de habitación.
 * Incluido desde views/recepcion/create.php cuando no hay ?idhabitacion=.
 * Depende de variables ya definidas por create.php: $URL, $habitaciones_disponibles,
 * $habitaciones_por_piso, $pisos_unicos.
 */
?>
<!-- Filtros simplificados -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>Filtros de Habitaciones
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <label class="form-label small text-muted mb-2">Filtrar por pisos:</label>
                        <div class="piso-filters-container">
                            <button type="button" class="btn btn-secondary btn-sm btn-piso active" data-piso="todos">
                                <i class="fas fa-building"></i> Todos los pisos
                            </button>
                            <?php if (isset($pisos_unicos)): ?>
                                <?php foreach ($pisos_unicos as $piso): ?>
                                    <button type="button" class="btn btn-outline-info btn-sm btn-piso" data-piso="<?= htmlspecialchars($piso); ?>">
                                        <i class="fas fa-layer-group"></i> <?= htmlspecialchars($piso); ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <!-- Habitaciones disponibles agrupadas por piso -->
        <div class="habitaciones-selection">
            <?php if (empty($habitaciones_disponibles)): ?>
                <div class="card card-body">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No hay habitaciones disponibles en este momento.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($habitaciones_por_piso as $nombrePiso => $habitaciones): ?>
                    <div class="card card-outline card-success piso-card piso-section mb-4" data-piso="<?= htmlspecialchars($nombrePiso); ?>">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-building mr-2"></i>
                                    <?= htmlspecialchars($nombrePiso); ?>
                                </h5>
                                <div class="piso-stats">
                                    <span class="badge badge-secondary"><?= count($habitaciones); ?> habitaciones</span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <?php $modo = 'seleccion'; ?>
                                <?php foreach ($habitaciones as $hab): ?>
                                    <?php $habitacion = $hab; ?>
                                    <?php include __DIR__ . '/card-habitacion.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php

/**
 * Partial: rack de baños (libres, en uso, fuera de operación).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $banos           ServicioBanoController::getBanosConOcupacion() (con 'ocupacion' y 'minutos')
 * - $disponibilidad  ServicioBanoController::validarDisponibilidadServicio()
 * - $URL             ruta base de la aplicación
 *
 * Solo pinta HTML.
 */
?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Registro rápido - Estado de los baños</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir el rack">
                        <i class="fas fa-minus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$disponibilidad['disponible']): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i>
                        <strong>Registro no disponible:</strong> <?= htmlspecialchars($disponibilidad['message']); ?>
                        <a href="<?= $URL; ?>views/productos" class="alert-link ml-2">Gestionar productos</a>
                    </div>
                <?php elseif (($disponibilidad['stock_minimo'] ?? 0) > 0 && $disponibilidad['stock_actual'] <= $disponibilidad['stock_minimo']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i>
                        <strong>Stock bajo:</strong> quedan <?= (int)$disponibilidad['stock_actual']; ?> unidades de papel higiénico
                        (mínimo recomendado: <?= (int)$disponibilidad['stock_minimo']; ?>).
                    </div>
                <?php endif; ?>

                <?php if (empty($banos)): ?>
                    <div class="alert alert-warning text-center mb-0">
                        <i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i>
                        No hay baños registrados.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($banos as $bano): ?>
                            <?php $ocupacion = $bano['ocupacion']; ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <?php if ($ocupacion === 'libre'): ?>
                                    <button type="button" class="btn btn-touch btn-block btn-success btn-registro-rapido"
                                        <?= $disponibilidad['disponible'] ? '' : 'disabled'; ?>
                                        data-id="<?= (int)$bano['idbano']; ?>"
                                        data-nombre="<?= htmlspecialchars($bano['nombre'] ?? ''); ?>"
                                        data-precio="<?= htmlspecialchars($bano['precio'] ?? ''); ?>">
                                        <i class="fas fa-bolt mr-2" aria-hidden="true"></i>
                                        <?= htmlspecialchars($bano['nombre'] ?? ''); ?> - <?= number_format($bano['precio'], 2); ?>
                                    </button>
                                <?php elseif ($ocupacion === 'en_uso' || $ocupacion === 'excedido'): ?>
                                    <div class="bano-rack <?= $ocupacion === 'excedido' ? 'bano-rack-excedido' : 'bano-rack-uso'; ?>">
                                        <div class="font-weight-bold"><?= htmlspecialchars($bano['nombre'] ?? ''); ?></div>
                                        <div class="small">
                                            <?= $ocupacion === 'excedido' ? 'Excedido' : 'En uso'; ?> · <?= (int)$bano['minutos']; ?> min
                                        </div>
                                        <button type="button" class="btn btn-touch btn-sm btn-light btn-block mt-2 cambiar-estado-servicio"
                                            data-id="<?= (int)$bano['idservicio']; ?>"
                                            data-estado="finalizado">
                                            <i class="fas fa-flag-checkered mr-1" aria-hidden="true"></i> Finalizar
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-block btn-secondary" disabled>
                                        <i class="fas fa-tools mr-2" aria-hidden="true"></i>
                                        <?= htmlspecialchars($bano['nombre'] ?? ''); ?> · <?= $ocupacion === 'mantenimiento' ? 'Mantenimiento' : 'Fuera de servicio'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center mt-3">
                        <a href="<?= $URL; ?>views/servicios-bano/create.php" class="btn btn-touch btn-outline-primary">
                            <i class="fas fa-plus-circle" aria-hidden="true"></i> Registro manual
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
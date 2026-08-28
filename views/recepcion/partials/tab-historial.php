<?php

/**
 * Partial: tab "Historial" del panel de Recepción — DataTable de todas las recepciones.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL         (global de la app)
 * - $historial   array de RecepcionController::historial() con la clave 'recepciones'
 *                (cada fila ya decorada con 'estado_ui' y 'estado_derivado')
 *
 * Solo pinta HTML. El DataTable lo inicializa index-recepciones.js sobre #tablaRecepciones.
 * Las acciones de estado van por POST (handlers .rec-accion-* del módulo JS).
 */

$recepciones = $historial['recepciones'] ?? [];
?>
<div class="table-responsive">
    <table id="tablaRecepciones" class="table table-sm table-bordered table-hover table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Habitación</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Total</th>
                <th>Pagado</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $contador = 1; ?>
            <?php foreach ($recepciones as $recepcion): ?>
                <?php
                $estado = $recepcion['estado'];
                $estadoUi = $recepcion['estado_ui'];
                $saldo = ($recepcion['montototal'] ?? 0) - ($recepcion['montopagado'] ?? 0);
                ?>
                <tr>
                    <td class="text-center"><?= $contador++; ?></td>
                    <td>
                        <?= htmlspecialchars(($recepcion['nombre_cliente'] ?? '') . ' ' . ($recepcion['apellido_cliente'] ?? '')); ?>
                        <br><small class="text-muted"><?= htmlspecialchars($recepcion['numdoc_cliente'] ?? ''); ?></small>
                    </td>
                    <td class="text-center">
                        <strong><?= htmlspecialchars($recepcion['numero_habitacion'] ?? 'N/A'); ?></strong>
                        <?php if (!empty($recepcion['piso_nombre'])): ?>
                            <br><small class="text-muted">Piso: <?= htmlspecialchars($recepcion['piso_nombre']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= !empty($recepcion['fechaentrada']) ? date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])) : 'N/A'; ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($recepcion['fechasalida'])): ?>
                            <?= date('d/m/Y H:i', strtotime($recepcion['fechasalida'])); ?>
                        <?php elseif (!empty($recepcion['fechasalida_prevista'])): ?>
                            <span class="text-muted"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?><br><small>(Previsto)</small></span>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td class="text-right">Bs <?= number_format(floatval($recepcion['montototal'] ?? 0), 2); ?></td>
                    <td class="text-right">
                        Bs <?= number_format(floatval($recepcion['montopagado'] ?? 0), 2); ?>
                        <?php if ($saldo > 0): ?>
                            <br><small class="text-danger">Saldo: Bs <?= number_format($saldo, 2); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $estadoUi['badge']; ?>"><?= htmlspecialchars($estadoUi['label']); ?></span>
                    </td>
                    <td class="text-center">
                        <?php $tieneAcciones = in_array($estado, ['reservado', 'en_curso'], true); ?>
                        <div class="btn-group">
                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>"
                                class="btn btn-info btn-sm" title="Ver detalles" aria-label="Ver detalles"><i class="fas fa-eye"></i></a>

                            <a href="<?= $URL; ?>views/recepcion/recibo.php?id=<?= $recepcion['idrecepcion']; ?>"
                                class="btn btn-secondary btn-sm" target="_blank" title="Imprimir comprobante" aria-label="Imprimir comprobante"><i class="fas fa-print"></i></a>

                            <?php if ($tieneAcciones): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">
                                    <span class="sr-only">Más acciones</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="<?= $URL; ?>views/recepcion/update.php?id=<?= $recepcion['idrecepcion']; ?>">
                                        <i class="fas fa-edit mr-2"></i> Editar
                                    </a>

                                    <?php if ($estado === 'reservado'): ?>
                                        <button type="button" class="dropdown-item text-success rec-accion-checkin"
                                            data-id="<?= $recepcion['idrecepcion']; ?>">
                                            <i class="fas fa-sign-in-alt mr-2"></i> Check-in
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($estado === 'en_curso'): ?>
                                        <button type="button" class="dropdown-item text-primary rec-accion-checkout"
                                            data-id="<?= $recepcion['idrecepcion']; ?>"
                                            data-habitacion="<?= htmlspecialchars($recepcion['numero_habitacion'] ?? ''); ?>"
                                            data-cliente="<?= htmlspecialchars(($recepcion['nombre_cliente'] ?? '') . ' ' . ($recepcion['apellido_cliente'] ?? '')); ?>">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Check-out
                                        </button>
                                    <?php endif; ?>

                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item text-danger rec-accion-cancelar"
                                        data-id="<?= $recepcion['idrecepcion']; ?>">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

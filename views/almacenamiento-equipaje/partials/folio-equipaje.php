<?php

/**
 * Partial: folio de pagos de un equipaje (listado de solo lectura).
 * Incluido desde el tab Folio de views/almacenamiento-equipaje/show.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $equipaje  fila decorada de AlmacenamientoEquipajeController::mostrar()
 *              con la clave 'pagos' (Pago::getByEquipaje)
 *
 * Solo pinta HTML. El cobro se registra al crear el almacenamiento, así que el
 * folio no admite movimientos nuevos desde aquí.
 */
?>
<?php if (empty($equipaje['pagos'])): ?>
    <div class="text-center text-muted py-4 mb-0">
        <i class="fas fa-receipt fa-3x mb-3" aria-hidden="true"></i>
        <p class="mb-0">Sin movimientos en el folio.</p>
    </div>
<?php else: ?>
    <div id="folio-equipaje">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="fas fa-receipt mr-2"></i>Folio de pagos</h5>
            <span class="badge badge-info"><?= count($equipaje['pagos']); ?> movimiento(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Concepto</th>
                        <th class="text-right">Monto</th>
                        <th>Método</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipaje['pagos'] as $linea): ?>
                        <tr class="<?= $linea['tipo'] === 'pago' ? 'table-success' : ''; ?>">
                            <td>
                                <span class="badge badge-<?= $linea['tipo'] === 'cargo' ? 'warning' : ($linea['tipo'] === 'pago' ? 'success' : 'secondary'); ?>">
                                    <?= ucfirst($linea['tipo']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($linea['concepto']); ?></td>
                            <td class="text-right">Bs. <?= number_format($linea['montototal'], 2); ?></td>
                            <td><?= htmlspecialchars($linea['metodopago']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

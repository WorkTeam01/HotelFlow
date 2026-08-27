<?php

/**
 * Partial: folio de huésped (historial de cargos/pagos + formularios de registro)
 *
 * Dependencias que debe definir el archivo que hace el include, antes del include:
 * - $recepcion       array de la recepción actual (idrecepcion, estado)
 * - $folio_lineas    array de líneas del folio (Pago::getByRecepcion)
 * - $folio_saldo     array ['total_cargos' => float, 'total_pagado' => float, 'saldo' => float]
 * - $URL ya definido por session.php/header.php; el token CSRF se genera aquí con generateCSRFToken()
 */

$puedeRegistrarMovimientos = !in_array($recepcion['estado'], ['finalizado', 'cancelado'], true);
?>
<div class="card card-outline card-info" id="folio-recepcion" data-module="folio-recepcion" data-idrecepcion="<?= (int)$recepcion['idrecepcion']; ?>">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-invoice mr-2"></i>
            Folio de Huésped
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Folio de Huésped">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Registrado por</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($folio_lineas)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">Sin movimientos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($folio_lineas as $linea): ?>
                        <?php
                        $esNegativo = $linea['tipo'] === 'pago';
                        $claseTipo = $linea['tipo'] === 'cargo' ? 'text-danger' : ($linea['tipo'] === 'pago' ? 'text-success' : 'text-muted');
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($linea['fechacreacion'])); ?></td>
                            <td>
                                <?= htmlspecialchars($linea['concepto']); ?>
                                <span class="badge badge-light"><?= htmlspecialchars(ucfirst($linea['tipo'])); ?></span>
                            </td>
                            <td><?= htmlspecialchars($linea['nombre_usuario'] ?? 'Sistema'); ?></td>
                            <td class="text-right <?= $claseTipo; ?>">
                                <?= $esNegativo ? '-' : ''; ?>Bs <?= number_format((float)$linea['montototal'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
                    <td colspan="3" class="text-right">Saldo pendiente</td>
                    <td class="text-right <?= $folio_saldo['saldo'] > 0 ? 'text-danger' : 'text-success'; ?>">
                        Bs <?= number_format($folio_saldo['saldo'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($puedeRegistrarMovimientos): ?>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6 mb-2 mb-md-0">
                    <form class="form-folio-pago" data-accion="<?= $URL; ?>controllers/recepcion/registrar_pago_ajax.php">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <input type="hidden" name="idrecepcion" value="<?= (int)$recepcion['idrecepcion']; ?>">
                        <label for="folio-pago-monto" class="sr-only">Monto del pago</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="monto" id="folio-pago-monto"
                                class="form-control" placeholder="Monto" required>
                            <select name="metodopago" class="form-control" aria-label="Método de pago">
                                <option value="Efectivo">Efectivo</option>
                                <option value="QR">QR</option>
                                <option value="OTROS">Otros</option>
                            </select>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-hand-holding-usd mr-1"></i> Registrar pago
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <form class="form-folio-cargo" data-accion="<?= $URL; ?>controllers/recepcion/registrar_cargo_ajax.php">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <input type="hidden" name="idrecepcion" value="<?= (int)$recepcion['idrecepcion']; ?>">
                        <label for="folio-cargo-concepto" class="sr-only">Concepto del cargo</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="concepto" id="folio-cargo-concepto"
                                class="form-control" placeholder="Concepto" required>
                            <input type="number" step="0.01" min="0.01" name="monto"
                                class="form-control" placeholder="Monto" required>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-plus-circle mr-1"></i> Agregar cargo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

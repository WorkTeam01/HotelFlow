<?php

/**
 * Partial: formulario de edición de equipaje (columna izquierda).
 * Incluido desde views/almacenamiento-equipaje/update.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL                (global de la app)
 * - $equipaje           fila decorada de AlmacenamientoEquipajeController::editar()
 * - $clientes           array de AlmacenamientoEquipaje::getClientes()
 *
 * Solo pinta HTML. El resumen en vivo y la validación/confirmación de envío
 * viven en update-equipaje.js (#resumen-*, #info-cliente, #formActualizarEquipaje).
 * El botón principal de envío no está aquí: está en el partial
 * resumen-actualizar-equipaje.php apuntando a este form vía form="...".
 */
?>
<form id="formActualizarEquipaje" action="<?= $URL; ?>controllers/almacenamiento-equipaje/actualizar_equipaje.php" method="POST">
    <input type="hidden" name="idalmacen" value="<?= $equipaje['idalmacen']; ?>">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

    <!-- 1 · Cliente -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user mr-2"></i>Cliente</h3>
        </div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label for="idcliente" class="required-field">Cliente</label>
                <select class="form-control select2" id="idcliente" name="idcliente" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['idpersona']; ?>"
                            <?= $cliente['idpersona'] == $equipaje['idcliente'] ? 'selected' : ''; ?>
                            data-tipodoc="<?= htmlspecialchars($cliente['tipodocumento'] ?? ''); ?>"
                            data-numdoc="<?= htmlspecialchars($cliente['numdocumento'] ?? ''); ?>"
                            data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? ''); ?>">
                            <?= htmlspecialchars($cliente['nombre_completo'] ?? ''); ?> -
                            <?= htmlspecialchars($cliente['tipodocumento'] ?? ''); ?>:
                            <?= htmlspecialchars($cliente['numdocumento'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Información del cliente seleccionado (visible si ya hay cliente; update-equipaje.js la muestra/oculta) -->
            <div id="info-cliente"<?= !empty($equipaje['idcliente']) ? '' : ' style="display: none;"'; ?>>
                <div class="row mt-3 mb-0">
                    <div class="col-md-4">
                        <div class="info-box bg-light mb-0">
                            <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tipo de Documento</span>
                                <span class="info-box-number" id="cliente-tipodoc"><?= htmlspecialchars($equipaje['tipodoc_cliente'] ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light mb-0">
                            <span class="info-box-icon bg-primary"><i class="fas fa-passport"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Número de Documento</span>
                                <span class="info-box-number" id="cliente-numdoc"><?= htmlspecialchars($equipaje['numdoc_cliente'] ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light mb-0">
                            <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Teléfono</span>
                                <span class="info-box-number" id="cliente-telefono"><?= htmlspecialchars($equipaje['telefono_cliente'] ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 · Equipaje (campos editables) -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-suitcase mr-2"></i>Equipaje</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select class="form-control select2" id="estado" name="estado">
                            <?php foreach (AlmacenamientoEquipajeController::estadosEquipaje() as $valor => $ui): ?>
                                <?php if ($valor === 'retirado') {
                                    continue;
                                } ?>
                                <option value="<?= htmlspecialchars($valor); ?>"
                                    data-clase="<?= $ui['clase']; ?>"
                                    <?= $equipaje['estado'] == $valor ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($ui['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">No se puede cambiar a "Retirado" desde este formulario</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="codigo_ticket">Código de Ticket</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo_ticket" value="<?= htmlspecialchars($equipaje['codigo_ticket']); ?>" readonly>
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-ticket-alt"></i></span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Fijo tras el registro</small>
                    </div>
                </div>
            </div>
            <div class="form-group mb-0">
                <label for="descripcion">Descripción del Equipaje</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                    placeholder="Describa el equipaje (color, tipo, características especiales, etc.)"><?= htmlspecialchars($equipaje['descripcion'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- 3 · Entrada y cobro (solo lectura, sin inputs de escritura) -->
    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Entrada y cobro</h3>
            <div class="card-tools">
                <span class="badge badge-secondary">Fija tras el registro</span>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Tipo de Equipaje</dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'N/A'); ?> —
                    Bs. <?= number_format($equipaje['precio_base'] ?? 0, 2); ?>
                </dd>

                <dt class="col-sm-4">Cantidad de Piezas</dt>
                <dd class="col-sm-8"><?= (int)$equipaje['cantidad_piezas']; ?></dd>

                <dt class="col-sm-4">Fecha y Hora de Entrada</dt>
                <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($equipaje['fechaentrada'])); ?></dd>

                <dt class="col-sm-4">Monto</dt>
                <dd class="col-sm-8">Bs. <?= number_format($equipaje['monto'], 2); ?></dd>

                <dt class="col-sm-4">Método de Pago</dt>
                <dd class="col-sm-8"><?= htmlspecialchars($equipaje['metodopago'] ?? 'Efectivo'); ?></dd>

                <dt class="col-sm-4">Fecha de Registro</dt>
                <dd class="col-sm-8">
                    <?= isset($equipaje['fechacreacion']) ? date('d/m/Y H:i', strtotime($equipaje['fechacreacion'])) : date('d/m/Y H:i', strtotime($equipaje['fechaentrada'])); ?>
                </dd>

                <dt class="col-sm-4">Usuario que Registró</dt>
                <dd class="col-sm-8 mb-0"><?= htmlspecialchars($equipaje['nombre_usuario'] ?? 'No disponible'); ?></dd>
            </dl>
        </div>
    </div>

    <!-- Envío visible solo en móvil/tablet (en desktop usa el del resumen) -->
    <div class="equipaje-form-actions d-lg-none">
        <button type="submit" class="btn btn-warning btn-block btn-lg">
            <i class="fas fa-save mr-2"></i> Guardar cambios
        </button>
        <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-outline-secondary btn-block mt-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</form>

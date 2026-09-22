<?php

/**
 * Partial: formulario de registro de equipaje (columna izquierda).
 * Incluido desde views/almacenamiento-equipaje/create.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL                (global de la app)
 * - $clientes           array de AlmacenamientoEquipaje::getClientes()
 * - $precios_equipaje   array de AlmacenamientoEquipaje::getPreciosEquipaje()
 *
 * Solo pinta HTML. El cálculo del monto, el resumen en vivo y la validación/
 * confirmación de envío viven en create-equipaje.js (#monto, #resumen-*,
 * #cliente-*, #info-cliente). El botón principal de envío no está aquí: está en
 * el partial resumen-registro-equipaje.php apuntando a este form vía form="...".
 */
?>
<form id="formRegistroEquipaje" action="<?= $URL; ?>controllers/almacenamiento-equipaje/crear_equipaje.php" method="POST">
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

            <!-- Información del cliente seleccionado (la muestra/oculta create-equipaje.js) -->
            <div id="info-cliente" style="display: none;">
                <div class="row mt-3 mb-0">
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tipo de Documento</span>
                                <span class="info-box-number" id="cliente-tipodoc">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-primary"><i class="fas fa-passport"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Número de Documento</span>
                                <span class="info-box-number" id="cliente-numdoc">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Teléfono</span>
                                <span class="info-box-number" id="cliente-telefono">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 · Equipaje -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-suitcase mr-2"></i>Equipaje</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="idpequipaje" class="required-field">Tipo de Equipaje</label>
                        <select class="form-control select2" id="idpequipaje" name="idpequipaje" required>
                            <option value="">Seleccione un tipo de equipaje</option>
                            <?php foreach ($precios_equipaje as $precio): ?>
                                <option value="<?= $precio['idprecioe']; ?>" data-precio="<?= $precio['precio']; ?>">
                                    <?= htmlspecialchars($precio['tamano'] ?? ''); ?> -
                                    (Bs. <?= number_format($precio['precio'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="cantidad_piezas">Cantidad de Piezas</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="cantidad_piezas" name="cantidad_piezas"
                                value="1" min="1" max="50">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="codigo_ticket">Código de Ticket</label>
                        <input type="text" class="form-control" id="codigo_ticket" name="codigo_ticket" readonly>
                        <small class="form-text text-muted">Generado automáticamente</small>
                    </div>
                </div>
            </div>
            <div class="form-group mb-0">
                <label for="descripcion">Descripción del Equipaje</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                    placeholder="Describa el equipaje (color, tipo, características especiales, etc.)"></textarea>
            </div>
        </div>
    </div>

    <!-- 3 · Entrada y cobro -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Entrada y cobro</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fechaentrada">Fecha y Hora de Entrada</label>
                        <div class="input-group">
                            <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                                value="<?= date('Y-m-d\TH:i'); ?>">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="monto" class="required-field">Monto</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bs.</span>
                            </div>
                            <input type="number" class="form-control" id="monto" name="monto"
                                step="0.01" min="0" required readonly>
                        </div>
                        <small class="form-text text-muted">Calculado automáticamente</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="metodopago" class="required-field">Método de Pago</label>
                        <select class="form-control select2" id="metodopago" name="metodopago" required>
                            <option value="Efectivo">Efectivo</option>
                            <option value="QR">QR</option>
                            <option value="OTROS">Otros</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Envío visible solo en móvil/tablet (en desktop usa el del resumen) -->
    <div class="equipaje-form-actions d-lg-none">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-2"></i> Registrar Equipaje
        </button>
        <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-outline-secondary btn-block mt-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</form>
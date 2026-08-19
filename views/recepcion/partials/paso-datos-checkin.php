<?php
/**
 * Partial: paso 2 del wizard de check-in — formulario de reserva.
 * Incluido desde views/recepcion/create.php cuando hay ?idhabitacion= válido.
 * Depende de variables ya definidas por create.php: $URL, $habitacion, $clientes, $tarifas.
 */
?>
<!-- Progreso visual -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-info">
            <div class="card-body p-3">
                <div class="progress-steps d-flex justify-content-between align-items-center">
                    <div class="step completed">
                        <div class="step-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="step-label">Habitación Seleccionada</span>
                    </div>
                    <div class="step-line completed"></div>
                    <div class="step active">
                        <div class="step-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <span class="step-label">Datos de la Reserva</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-save"></i>
                        </div>
                        <span class="step-label">Confirmación</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Panel izquierdo: Información de la habitación -->
    <div class="col-md-4">
        <div class="card card-outline card-success sticky-top" style="top: 1rem;">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bed mr-2"></i>Habitación Seleccionada
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="room-preview mb-3">
                    <i class="fas fa-bed fa-4x mb-3 text-success"></i>
                    <h2 class="font-weight-bold text-success"><?= htmlspecialchars($habitacion['numero']); ?></h2>
                    <p class="text-muted mb-2"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></p>
                </div>

                <hr>

                <div class="room-details text-left">
                    <div class="detail-item mb-2">
                        <i class="fas fa-building text-info mr-2"></i>
                        <strong>Piso:</strong> <?= htmlspecialchars($habitacion['piso_nombre']); ?>
                    </div>
                    <div class="detail-item mb-2">
                        <i class="fas fa-users text-info mr-2"></i>
                        <strong>Capacidad:</strong>
                        <?= $habitacion['capacidad_actual'] == 1 ? 'Individual' : $habitacion['capacidad_actual'] . ' personas'; ?>
                    </div>
                    <div class="detail-item mb-2">
                        <i class="fas fa-dollar-sign text-info mr-2"></i>
                        <strong>Precio base:</strong> Bs <?= number_format($habitacion['precio_base'], 2); ?>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-check-circle text-success mr-2"></i>
                        <strong>Estado:</strong> <span class="badge badge-success">Disponible</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?= $URL; ?>views/recepcion/create.php" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i>Cambiar Habitación
                </a>
            </div>
        </div>
    </div>

    <!-- Panel derecho: Formulario de reserva -->
    <div class="col-md-8">
        <form id="formCheckin" action="<?= $URL; ?>controllers/recepcion/guardar_checkin.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()); ?>">
            <input type="hidden" name="idhabitacion" value="<?= $habitacion['id_habitacion']; ?>">

            <!-- Datos del cliente -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>Información del Cliente
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="idcliente">Cliente <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="idcliente" name="idcliente" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['idpersona']; ?>"
                                            data-nombre="<?= htmlspecialchars($cliente['nombre_completo']); ?>"
                                            data-tipodoc="<?= htmlspecialchars($cliente['tipodocumento']); ?>"
                                            data-numdoc="<?= htmlspecialchars($cliente['numdocumento']); ?>"
                                            data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? ''); ?>">
                                            <?= htmlspecialchars($cliente['tipodocumento'] . ': ' . $cliente['numdocumento'] . ' - ' . $cliente['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un cliente.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre_cliente">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre_cliente" readonly
                                    placeholder="Se mostrará al seleccionar cliente">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="documento_cliente">Documento</label>
                                <input type="text" class="form-control" id="documento_cliente" readonly
                                    placeholder="Se mostrará al seleccionar cliente">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono_cliente">Teléfono</label>
                                <input type="text" class="form-control" id="telefono_cliente" readonly
                                    placeholder="Se mostrará al seleccionar cliente">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>¿Cliente nuevo?</strong>
                        <a href="<?= $URL; ?>views/clientes/create.php" target="_blank" class="alert-link">
                            Regístrale aquí <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Datos de la reserva -->
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar mr-2"></i>Detalles de la Reserva
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fechaentrada">Fecha y hora de entrada <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                                    value="<?= date('Y-m-d\TH:i'); ?>" required>
                                <div class="invalid-feedback">Por favor ingrese la fecha de entrada.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fechasalida_prevista">Fecha y hora de salida prevista <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="fechasalida_prevista" name="fechasalida_prevista"
                                    value="<?= date('Y-m-d\TH:i', strtotime('+1 day')); ?>" required>
                                <div class="invalid-feedback">Por favor ingrese la fecha de salida prevista.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="idtarifa">Tarifa <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="idtarifa" name="idtarifa" required>
                                    <option value="">Seleccione una tarifa</option>
                                    <?php foreach ($tarifas as $tarifa): ?>
                                        <?php if ($tarifa['id_tipo'] == $habitacion['id_tipo']): ?>
                                            <option value="<?= $tarifa['idtarifa']; ?>"
                                                data-precio="<?= $tarifa['precio']; ?>"
                                                data-tipo="<?= $tarifa['id_tipo']; ?>"
                                                data-estancia="<?= $tarifa['tipo_estancia']; ?>"
                                                data-duracion="<?= $tarifa['duracion']; ?>">
                                                <?= htmlspecialchars(
                                                    ($tarifa['tipo_estancia'] == 'horas' ? $tarifa['duracion'] . ' hora(s)' : $tarifa['duracion'] . ' día(s)') .
                                                        ' - Bs ' . number_format($tarifa['precio'], 2)
                                                ); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione una tarifa.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado de la reserva <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="estado" name="estado" required>
                                    <option value="en_curso" selected>Check-in inmediato (En curso)</option>
                                    <option value="reservado">Solo reservar (Pendiente de check-in)</option>
                                </select>
                                <small class="form-text text-muted">
                                    Selecciona "Check-in inmediato" si el cliente se hospeda ahora.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                    placeholder="Notas adicionales sobre la reserva (opcional)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de pago -->
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-dollar-sign mr-2"></i>Información de Pago
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="montototal">Monto total <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Bs</span>
                                    </div>
                                    <input type="number" class="form-control" id="montototal" name="montototal"
                                        step="0.01" min="0" value="<?= $habitacion['precio_base']; ?>" required>
                                </div>
                                <small class="form-text text-muted">Se actualiza automáticamente según la tarifa seleccionada.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="montopagado">Adelanto/Pago</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Bs</span>
                                    </div>
                                    <input type="number" class="form-control" id="montopagado" name="montopagado"
                                        step="0.01" min="0" value="0">
                                </div>
                                <small class="form-text text-muted">Monto que paga el cliente ahora.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pago_metodo">Método de pago</label>
                                <select class="form-control select2" id="pago_metodo" name="pago_metodo">
                                    <option value="">Seleccione un método de pago</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="QR">QR</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de pago en efectivo -->
                    <div id="seccion-efectivo" class="row mb-3" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pago_recibido">Monto recibido</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Bs</span>
                                    </div>
                                    <input type="number" class="form-control" id="pago_recibido" name="pago_recibido"
                                        step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cambio">Cambio</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Bs</span>
                                    </div>
                                    <input type="number" class="form-control" id="cambio" name="cambio"
                                        step="0.01" min="0" value="0" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de saldos -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info" id="resumen-saldos">
                                <h6><i class="fas fa-calculator mr-2"></i>Resumen financiero:</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Total:</strong> <span id="resumen-total">Bs 0.00</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Pagado:</strong> <span id="resumen-pagado">Bs 0.00</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Saldo:</strong> <span id="resumen-saldo" class="text-danger">Bs 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen final de la reserva -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clipboard-list mr-2"></i>Resumen de la Reserva
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-item">
                                <strong>Habitación:</strong>
                                <span id="resumen-habitacion">N° <?= htmlspecialchars($habitacion['numero']); ?> - <?= htmlspecialchars($habitacion['tipo_nombre']); ?></span>
                            </div>
                            <div class="summary-item">
                                <strong>Cliente:</strong>
                                <span id="resumen-cliente">No seleccionado</span>
                            </div>
                            <div class="summary-item">
                                <strong>Tarifa:</strong>
                                <span id="resumen-tarifa">No seleccionada</span>
                            </div>
                            <div class="summary-item">
                                <strong>Estado:</strong>
                                <span id="resumen-estado" class="badge badge-warning">Check-in inmediato</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-item">
                                <strong>Entrada:</strong>
                                <span id="resumen-entrada"><?= date('d/m/Y H:i'); ?></span>
                            </div>
                            <div class="summary-item">
                                <strong>Salida prevista:</strong>
                                <span id="resumen-salida"><?= date('d/m/Y H:i', strtotime('+1 day')); ?></span>
                            </div>
                            <div class="summary-item">
                                <strong>Método de pago:</strong>
                                <span id="resumen-metodo-pago">Sin pago</span>
                            </div>
                            <div class="summary-item">
                                <strong>Duración estimada:</strong>
                                <span id="resumen-duracion">1 día</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3" id="resumen-observaciones-container" style="display: none;">
                        <div class="col-md-12">
                            <div class="summary-item">
                                <strong>Observaciones:</strong>
                                <span id="resumen-observaciones" class="text-muted"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="card">
                <div class="card-body">
                    <div class="row g-1 justify-content-lg-center">
                        <div class="col-12 col-sm-auto">
                            <a href="<?= $URL; ?>views/recepcion/create.php" class="btn btn-secondary btn-lg w-100">
                                <i class="fas fa-arrow-left"></i> Cambiar Habitación
                            </a>
                        </div>
                        <div class="col-12 col-sm-auto">
                            <button type="submit" class="btn btn-success btn-lg w-100" id="btn-submit">
                                <i class="fas fa-save mr-2"></i>
                                <span id="btn-text"> Confirmar Reserva</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

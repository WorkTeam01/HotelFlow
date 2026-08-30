<?php

/**
 * Partial: formulario de nueva reserva (página única, columna izquierda).
 * Incluido desde views/recepcion/create.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $URL                     (global de la app)
 * - $habitaciones_disponibles array plano de habitaciones disponibles (solo para el contador)
 * - $clientes                array de Recepcion::getClientes()
 * - $tarifas                 array de Recepcion::getTarifas()
 * - $habitaciones_por_piso   mapa [piso => habitaciones[]] de RecepcionController::crear()
 * - $pisos_unicos            string[] con los nombres de piso
 * - $habitacion              fila de la habitación preseleccionada (?idhabitacion=) o null
 *
 * Solo pinta HTML. Toda la lógica (fechas, filtro de tarifas por tipo, chequeo de
 * solape, resumen en vivo, cálculo de cambio) vive en create-recepcion.js.
 */

$habitacionSel = $habitacion['id_habitacion'] ?? null;
$hayDisponibles = !empty($habitaciones_por_piso);
?>
<form id="formReserva" action="<?= $URL; ?>controllers/recepcion/guardar_checkin.php" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()); ?>">
    <input type="hidden" name="montototal" id="montototal" value="0">
    <input type="hidden" name="montopagado" id="montopagado" value="0">

    <!-- 1 · Fechas -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Fechas de la estancia</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fechaentrada">Entrada <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                            value="<?= date('Y-m-d\TH:i'); ?>" required>
                        <div class="invalid-feedback">Indique la fecha de entrada.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fechasalida_prevista">Salida prevista <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fechasalida_prevista" name="fechasalida_prevista"
                            value="<?= date('Y-m-d\TH:i', strtotime('+1 day')); ?>" required>
                        <div class="invalid-feedback">La salida debe ser posterior a la entrada.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 · Habitación -->
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bed mr-2"></i>Habitación</h3>
            <div class="card-tools">
                <span class="badge badge-secondary" id="rec-hab-count"><?= count($habitaciones_disponibles ?? []); ?> disponibles</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$hayDisponibles): ?>
                <div class="rec-empty">
                    <i class="fas fa-door-closed"></i>
                    <div>No hay habitaciones disponibles en este momento.</div>
                </div>
            <?php else: ?>
                <?php if (count($pisos_unicos) > 1): ?>
                    <div class="btn-group btn-group-sm rec-piso-filtros mb-3" role="group">
                        <button type="button" class="btn btn-secondary active" data-piso="todos">Todos</button>
                        <?php foreach ($pisos_unicos as $piso): ?>
                            <button type="button" class="btn btn-outline-secondary" data-piso="<?= htmlspecialchars($piso); ?>">
                                <?= htmlspecialchars($piso); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="rec-select-rack">
                    <?php foreach ($habitaciones_por_piso as $nombrePiso => $habs): ?>
                        <div class="rec-select-piso" data-piso="<?= htmlspecialchars($nombrePiso); ?>">
                            <h6 class="text-muted mb-2"><i class="fas fa-building mr-1"></i><?= htmlspecialchars($nombrePiso); ?></h6>
                            <div class="rec-rack">
                                <?php foreach ($habs as $h): ?>
                                    <?php $checked = ($habitacionSel && $habitacionSel == $h['id_habitacion']); ?>
                                    <div class="rec-tile-col col-6 col-sm-4 col-md-3">
                                        <label class="rec-select-tile<?= $checked ? ' is-selected' : ''; ?>">
                                            <input type="radio" name="idhabitacion" class="rec-hab-radio"
                                                value="<?= (int) $h['id_habitacion']; ?>"
                                                data-numero="<?= htmlspecialchars($h['numero']); ?>"
                                                data-tipo="<?= htmlspecialchars($h['tipo_nombre']); ?>"
                                                data-idtipo="<?= (int) $h['id_tipo']; ?>"
                                                data-precio="<?= htmlspecialchars($h['precio_base']); ?>"
                                                <?= $checked ? 'checked' : ''; ?> required>
                                            <span class="rec-select-tile__num"><?= htmlspecialchars($h['numero']); ?></span>
                                            <span class="rec-select-tile__tipo small text-muted text-truncate"><?= htmlspecialchars($h['tipo_nombre']); ?></span>
                                            <span class="rec-select-tile__precio small">Bs <?= number_format($h['precio_base'], 2); ?></span>
                                            <span class="rec-select-tile__conflicto small text-danger d-none">No disponible en esas fechas</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3 · Huésped -->
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user mr-2"></i>Huésped</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="idcliente">Cliente <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="idcliente" name="idcliente" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= (int) $cliente['idpersona']; ?>"
                                    data-nombre="<?= htmlspecialchars($cliente['nombre_completo']); ?>">
                                    <?= htmlspecialchars($cliente['tipodocumento'] . ': ' . $cliente['numdocumento'] . ' - ' . $cliente['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione un cliente.</div>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="<?= $URL; ?>views/clientes/create.php" target="_blank" class="btn btn-outline-info btn-block mb-3">
                        <i class="fas fa-user-plus mr-1"></i> Cliente nuevo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 · Tarifa y estado -->
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Tarifa</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="idtarifa">Tarifa <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="idtarifa" name="idtarifa" required>
                            <option value="">Elija primero una habitación</option>
                            <?php foreach ($tarifas as $tarifa): ?>
                                <option value="<?= (int) $tarifa['idtarifa']; ?>"
                                    data-idtipo="<?= (int) $tarifa['id_tipo']; ?>"
                                    data-precio="<?= htmlspecialchars($tarifa['precio']); ?>"
                                    data-estancia="<?= htmlspecialchars($tarifa['tipo_estancia']); ?>"
                                    data-duracion="<?= htmlspecialchars($tarifa['duracion']); ?>">
                                    <?= htmlspecialchars(
                                        ($tarifa['tipo_estancia'] === 'horas' ? $tarifa['duracion'] . ' hora(s)' : $tarifa['duracion'] . ' día(s)')
                                            . ' - Bs ' . number_format($tarifa['precio'], 2)
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione una tarifa.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="estado">Tipo de registro <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="estado" name="estado" required>
                            <option value="en_curso" selected>Check-in inmediato</option>
                            <option value="reservado">Solo reservar</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"
                    placeholder="Notas adicionales (opcional)"></textarea>
            </div>
        </div>
    </div>

    <!-- 5 · Cobro inicial -->
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Cobro inicial</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pago_metodo">Método de pago</label>
                        <select class="form-control select2" id="pago_metodo" name="pago_metodo">
                            <option value="">Sin cobro ahora</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="QR">QR</option>
                            <option value="OTROS">Otros</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 d-none" id="seccion-efectivo">
                    <div class="form-group">
                        <label for="pago_recibido">Monto recibido (efectivo)</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Bs</span></div>
                            <input type="number" class="form-control" id="pago_recibido" name="pago_recibido" step="0.01" min="0" value="0">
                        </div>
                        <small class="form-text text-muted">Cambio: Bs <span id="pago_cambio">0.00</span></small>
                    </div>
                </div>
            </div>
            <p class="text-muted mb-0 small">
                <i class="fas fa-info-circle mr-1"></i>
                El total se toma de la tarifa. El saldo restante se gestiona luego en el folio del huésped.
            </p>
        </div>
    </div>

    <div class="rec-form-actions d-lg-none">
        <button type="submit" class="btn btn-success btn-block btn-lg" id="btn-submit">
            <i class="fas fa-check mr-2"></i><span id="btn-text">Confirmar reserva</span>
        </button>
        <a href="<?= $URL; ?>views/recepcion/index.php" class="btn btn-outline-secondary btn-block mt-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</form>

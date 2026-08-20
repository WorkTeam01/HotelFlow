<?php

/**
 * Partial: cambio de habitación auditable + historial de movimientos
 *
 * Dependencias que debe definir el archivo que hace el include, antes del include:
 * - $recepcion                          array de la recepción actual (idrecepcion, estado, numero_habitacion)
 * - $movimientos                        array de Recepcion::getMovimientos()
 * - $habitaciones_disponibles_cambio    array de habitaciones disponibles como destino (solo si estado === 'en_curso')
 * - $URL ya definido por session.php/header.php; el token CSRF se genera aquí con generateCSRFToken()
 */

$puedeCambiarHabitacion = $recepcion['estado'] === 'en_curso';
?>
<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exchange-alt mr-2"></i>
            Cambio de Habitación
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Cambio de Habitación">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($movimientos)): ?>
            <p class="text-muted mb-0">Sin cambios de habitación registrados.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush mb-0">
                <?php foreach ($movimientos as $mov): ?>
                    <li class="list-group-item px-0">
                        <i class="fas fa-arrow-right mr-2 text-warning"></i>
                        Habitación <?= htmlspecialchars($mov['numero_origen'] ?? $mov['valor_anterior']); ?>
                        &rarr;
                        <?= htmlspecialchars($mov['numero_destino'] ?? $mov['valor_nuevo']); ?>
                        <?php if (!empty($mov['motivo'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($mov['motivo']); ?></small>
                        <?php endif; ?>
                        <span class="float-right text-muted">
                            <small>
                                <?= date('d/m/Y H:i', strtotime($mov['fechacreacion'])); ?>
                                &middot; <?= htmlspecialchars($mov['nombre_usuario'] ?? 'Sistema'); ?>
                            </small>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if ($puedeCambiarHabitacion): ?>
        <div class="card-footer">
            <?php if (empty($habitaciones_disponibles_cambio)): ?>
                <p class="text-muted mb-0">No hay habitaciones disponibles para trasladar al huésped en este momento.</p>
            <?php else: ?>
                <form id="form-cambio-habitacion" data-accion="<?= $URL; ?>controllers/recepcion/cambiar_habitacion_ajax.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <input type="hidden" name="idrecepcion" value="<?= (int)$recepcion['idrecepcion']; ?>">
                    <div class="form-row align-items-end">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label for="cambio-habitacion-destino" class="mb-1">Nueva habitación</label>
                            <select name="idhabitacion_destino" id="cambio-habitacion-destino" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($habitaciones_disponibles_cambio as $hab): ?>
                                    <option value="<?= (int)$hab['id_habitacion']; ?>">
                                        Habitación <?= htmlspecialchars($hab['numero']); ?> — <?= htmlspecialchars($hab['tipo_nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label for="cambio-habitacion-motivo" class="mb-1">Motivo (opcional)</label>
                            <input type="text" name="motivo" id="cambio-habitacion-motivo" class="form-control" placeholder="Ej. Solicitud del huésped">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-exchange-alt mr-1"></i> Cambiar
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

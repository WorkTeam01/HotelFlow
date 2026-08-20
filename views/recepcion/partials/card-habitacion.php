<?php
/**
 * Partial: tarjeta de habitación reutilizable (Fase 2 del refactor PMS de Recepción).
 *
 * Dependencias que debe definir el archivo que hace el include, ANTES de incluirlo:
 * - $URL            (global de la app)
 * - $modo           string: 'disponible' | 'ocupada' | 'mantenimiento' | 'seleccion'
 * - $hora_actual     string 'H:i'  (requerido en modo 'disponible')
 * - $fecha_actual    string 'd/m/Y' (requerido en modo 'mantenimiento')
 *
 * Y según el modo, uno de estos arrays con los datos de la fila:
 * - modo 'disponible'/'seleccion': $habitacion (numero, tipo_nombre, capacidad_actual,
 *   id_habitacion, precio_base [solo 'seleccion'])
 * - modo 'ocupada': $recepcion (numero_habitacion, nombre_cliente, apellido_cliente,
 *   fechasalida_prevista, idrecepcion)
 * - modo 'mantenimiento': $habitacion (numero, tipo_nombre, estado, id_habitacion)
 * - modo 'reserva': $recepcion (numero_habitacion, nombre_cliente, apellido_cliente,
 *   fechaentrada, idrecepcion)
 *
 * Solo pinta HTML, no contiene lógica de negocio ni consultas.
 */
?>
<?php if ($modo === 'disponible'): ?>
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="card h-100 habitacion-card">
            <div class="card-header bg-success py-2">
                <h6 class="card-title mb-0 room-number">
                    <i class="fas fa-door-open mr-1"></i>
                    N°: <?= htmlspecialchars($habitacion['numero']); ?>
                </h6>
            </div>

            <div class="card-body text-center p-3">
                <div class="mb-2">
                    <i class="fas fa-bed fa-2x text-muted"></i>
                </div>

                <h6 class="card-subtitle mb-2 text-muted room-type">
                    <?= htmlspecialchars($habitacion['tipo_nombre']); ?>
                </h6>

                <div class="mb-2">
                    <span class="badge badge-success badge-pill px-3">
                        <i class="fas fa-check-circle mr-1"></i>
                        Disponible
                    </span>
                </div>

                <small class="text-muted d-block mb-1">
                    <i class="fas fa-users mr-1"></i>
                    <?= isset($habitacion['capacidad_actual']) ?
                        ($habitacion['capacidad_actual'] == 1 ? 'Individual' : $habitacion['capacidad_actual'] . ' personas') :
                        'No definido'; ?>
                </small>

                <small class="text-muted d-block mb-3">
                    <i class="far fa-clock mr-1"></i>
                    <?= $hora_actual; ?>
                </small>
            </div>

            <div class="card-footer p-0">
                <a href="<?= $URL; ?>views/recepcion/create.php?idhabitacion=<?= $habitacion['id_habitacion']; ?>"
                    class="btn btn-success btn-block rounded-0">
                    <i class="fas fa-play mr-1"></i>
                    <span class="d-none d-md-inline">Iniciar Check-in</span>
                    <span class="d-md-none">Iniciar</span>
                </a>
            </div>
        </div>
    </div>

<?php elseif ($modo === 'ocupada'): ?>
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="card h-100 habitacion-card">
            <div class="card-header bg-warning py-2">
                <h6 class="card-title mb-0 room-number">
                    <i class="fas fa-door-closed mr-1"></i>
                    N°: <?= htmlspecialchars($recepcion['numero_habitacion']); ?>
                </h6>
            </div>

            <div class="card-body text-center p-3">
                <div class="mb-2">
                    <i class="fas fa-bed fa-2x text-muted"></i>
                </div>

                <h6 class="card-subtitle mb-2 text-primary client-name">
                    <?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?>
                </h6>

                <div class="mb-2">
                    <span class="badge badge-warning badge-pill px-3">
                        <i class="fas fa-user-clock mr-1"></i>
                        Ocupada
                    </span>
                </div>

                <small class="text-muted d-block mb-1">
                    <i class="fas fa-calendar-check mr-1"></i>
                    Check-out: <?= date('d/m/Y', strtotime($recepcion['fechasalida_prevista'])); ?>
                </small>

                <small class="text-muted d-block mb-3">
                    <i class="far fa-clock mr-1"></i>
                    <?= date('H:i', strtotime($recepcion['fechasalida_prevista'])); ?>
                </small>
            </div>

            <div class="card-footer p-0">
                <div class="row no-gutters">
                    <div class="col-6">
                        <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>"
                            class="btn btn-info btn-block rounded-0">
                            <i class="fas fa-eye"></i>
                            <span class="d-none d-lg-inline ">Detalles</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= $recepcion['idrecepcion']; ?>&nuevo_estado=finalizado&csrf_token=<?= generateCSRFToken(); ?>"
                            class="btn btn-success btn-checkout"
                            data-habitacion="<?= htmlspecialchars($recepcion['numero_habitacion']); ?>"
                            data-cliente="<?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?>">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="d-none d-lg-inline ml-1">Check-out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($modo === 'mantenimiento'): ?>
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="card h-100 habitacion-card">
            <div class="card-header bg-danger py-2">
                <h6 class="card-title mb-0 room-number">
                    <i class="fas fa-tools mr-1"></i>
                    N°: <?= htmlspecialchars($habitacion['numero']); ?>
                </h6>
            </div>

            <div class="card-body text-center p-3">
                <div class="mb-2">
                    <i class="fas fa-bed fa-2x text-muted"></i>
                </div>

                <h6 class="card-subtitle mb-2 text-muted room-type">
                    <?= htmlspecialchars($habitacion['tipo_nombre']); ?>
                </h6>

                <div class="mb-2">
                    <span class="badge badge-danger badge-pill px-3">
                        <i class="fas fa-wrench mr-1"></i>
                        <?= $habitacion['estado'] == 'mantenimiento' ? 'Mantenimiento' : 'Limpieza'; ?>
                    </span>
                </div>

                <small class="text-muted d-block mb-1">
                    <i class="fas fa-calendar-day mr-1"></i>
                    <?= $fecha_actual; ?>
                </small>

                <small class="text-muted d-block mb-3">
                    <i class="fas fa-cog mr-1"></i>
                    En proceso
                </small>
            </div>

            <div class="card-footer p-0">
                <a href="<?= $URL; ?>views/habitaciones/show.php?id=<?= $habitacion['id_habitacion']; ?>"
                    class="btn btn-secondary btn-block rounded-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span class="d-none d-md-inline">Ver Detalles</span>
                    <span class="d-md-none">Detalles</span>
                </a>
            </div>
        </div>
    </div>

<?php elseif ($modo === 'seleccion'): ?>
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="card h-100 habitacion-card habitacion-selectable"
            data-numero="<?= htmlspecialchars($habitacion['numero']); ?>"
            data-precio="<?= $habitacion['precio_base']; ?>">

            <div class="card-header bg-success text-white text-center py-2">
                <h6 class="card-title mb-0 room-number">
                    <i class="fas fa-door-open mr-1"></i>
                    N°: <?= htmlspecialchars($habitacion['numero']); ?>
                </h6>
            </div>

            <div class="card-body text-center p-3">
                <div class="mb-2">
                    <i class="fas fa-bed fa-2x text-muted"></i>
                </div>

                <h6 class="card-subtitle mb-2 text-muted room-type">
                    <?= htmlspecialchars($habitacion['tipo_nombre']); ?>
                </h6>

                <div class="mb-2">
                    <span class="badge badge-success badge-pill px-3">
                        <i class="fas fa-check-circle mr-1"></i>
                        Disponible
                    </span>
                </div>

                <small class="text-muted d-block mb-3">
                    <i class="fas fa-dollar-sign mr-1"></i>
                    Precio base: Bs <?= number_format($habitacion['precio_base'], 2); ?>
                </small>
            </div>

            <div class="card-footer p-0">
                <div class="row no-gutters">
                    <div class="col-6">
                        <a href="#" class="btn btn-info btn-block rounded-0">
                            <i class="fas fa-eye mr-1"></i>
                            Detalles
                        </a>
                    </div>
                    <div class="col-6">
                        <button type="button"
                            class="btn btn-success btn-block rounded-0 btn-select-room"
                            data-id="<?= $habitacion['id_habitacion']; ?>"
                            data-numero="<?= htmlspecialchars($habitacion['numero']); ?>"
                            data-tipo="<?= htmlspecialchars($habitacion['tipo_nombre']); ?>"
                            data-precio="<?= $habitacion['precio_base']; ?>">
                            <i class="fas fa-check mr-1"></i>
                            Asignar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($modo === 'reserva'): ?>
    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
        <div class="card h-100 habitacion-card">
            <div class="card-header bg-info py-2">
                <h6 class="card-title mb-0 room-number">
                    <i class="fas fa-calendar-check mr-1"></i>
                    N°: <?= htmlspecialchars($recepcion['numero_habitacion']); ?>
                </h6>
            </div>

            <div class="card-body text-center p-3">
                <div class="mb-2">
                    <i class="fas fa-bed fa-2x text-muted"></i>
                </div>

                <h6 class="card-subtitle mb-2 text-primary client-name">
                    <?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?>
                </h6>

                <div class="mb-2">
                    <span class="badge badge-info badge-pill px-3">
                        <i class="fas fa-calendar-day mr-1"></i>
                        Reservado
                    </span>
                </div>

                <small class="text-muted d-block mb-1">
                    <i class="fas fa-sign-in-alt mr-1"></i>
                    Entrada: <?= date('d/m/Y', strtotime($recepcion['fechaentrada'])); ?>
                </small>

                <small class="text-muted d-block mb-3">
                    <i class="far fa-clock mr-1"></i>
                    <?= date('H:i', strtotime($recepcion['fechaentrada'])); ?>
                </small>
            </div>

            <div class="card-footer p-0">
                <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>"
                    class="btn btn-info btn-block rounded-0">
                    <i class="fas fa-eye"></i>
                    <span class="d-none d-lg-inline ml-1">Ver detalle</span>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

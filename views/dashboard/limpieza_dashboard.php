<!-- Dashboard visual de habitaciones pendientes -->

<!-- Indicadores clave para Personal de Limpieza -->
<div class="row">
    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['pendientes'] ?? 0 ?></h3>
                <p>Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['completadas'] ?? 0 ?></h3>
                <p>Completadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['total'] ?? 0 ?></h3>
                <p>Total Asignaciones</p>
            </div>
            <div class="icon">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
    </div>
</div>


<!-- Dashboard visual de habitaciones pendientes -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-1"></i>
                    Mis Asignaciones Pendientes
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="buscarHabitacionPendiente" class="form-control float-right" placeholder="Buscar habitación">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Fecha actual -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Hoy</h4>
                        <?php
                        $meses_es = [
                            1 => 'enero',
                            'febrero',
                            'marzo',
                            'abril',
                            'mayo',
                            'junio',
                            'julio',
                            'agosto',
                            'septiembre',
                            'octubre',
                            'noviembre',
                            'diciembre'
                        ];
                        ?>
                        <p class="text-muted mb-0"><?= date('d') . ' ' . $meses_es[(int)date('n')] . ' ' . date('Y') ?></p>
                    </div>
                    <div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary filtro-estado-pendiente active" data-estado="todos">Todos</button>
                            <button type="button" class="btn btn-outline-warning filtro-estado-pendiente" data-estado="pendiente">Pendientes</button>
                            <button type="button" class="btn btn-outline-info filtro-estado-pendiente" data-estado="enprogreso">En progreso</button>
                        </div>
                    </div>
                </div>

                <!-- Grid de habitaciones asignadas pendientes -->
                <div class="row" id="grid-habitaciones-pendientes">
                    <?php
                    // Filtrar solo las asignaciones pendientes o en progreso
                    $asignaciones_activas = array_filter($stats['asignaciones']['pendientes'], function ($asignacion) {
                        return in_array($asignacion['estado'], ['pendiente', 'enprogreso']);
                    });

                    if (empty($asignaciones_activas)):
                    ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">
                                <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                                ¡No tienes asignaciones pendientes para hoy!
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($asignaciones_activas as $asignacion): ?>
                            <?php
                            $estado = $asignacion['estado'];
                            $clase_estado = '';
                            $texto_estado = '';
                            $icono_estado = '';

                            switch ($estado) {
                                case 'pendiente':
                                    $clase_estado = 'bg-warning';
                                    $texto_estado = 'Pendiente';
                                    $icono_estado = 'fa-clock';
                                    break;
                                case 'enprogreso':
                                    $clase_estado = 'bg-info';
                                    $texto_estado = 'En Progreso';
                                    $icono_estado = 'fa-sync-alt fa-spin';
                                    break;
                            }
                            ?>
                            <div class="col-md-3 col-sm-6 mb-4 habitacion-card-pendiente" data-estado="<?= $estado ?>" data-numero="<?= $asignacion['numero_habitacion'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="card-title">N°: <?= htmlspecialchars($asignacion['numero_habitacion']) ?></h5>
                                        <div class="badge <?= $clase_estado ?> mb-3">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            <?= htmlspecialchars($asignacion['tipo_habitacion']) ?><br>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($asignacion['fecha'])) ?><br>
                                            <i class="far fa-clock mr-1"></i> <?= date('H:i', strtotime($asignacion['hora'])) ?>
                                        </p>
                                    </div>
                                    <div class="card-footer p-0">
                                        <div class="btn-group d-flex">
                                            <?php if ($estado == 'pendiente'): ?>
                                                <button type="button" class="btn btn-info btn-sm btn-block cambiar-estado rounded-0"
                                                    data-id="<?= $asignacion['idasignacion']; ?>"
                                                    data-estado="enprogreso">
                                                    <i class="fas fa-play mr-1"></i> Iniciar
                                                </button>
                                            <?php elseif ($estado == 'enprogreso'): ?>
                                                <button type="button" class="btn btn-success btn-sm btn-block cambiar-estado rounded-0"
                                                    data-id="<?= $asignacion['idasignacion']; ?>"
                                                    data-estado="completada">
                                                    <i class="fas fa-check mr-1"></i> Completar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard visual de habitaciones completadas -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-check-circle mr-1"></i>
                    Asignaciones Completadas Recientemente
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="buscarHabitacionCompletada" class="form-control float-right" placeholder="Buscar habitación">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Grid de habitaciones completadas -->
                <div class="row" id="grid-habitaciones-completadas">
                    <?php
                    // Combinar las asignaciones completadas con las que están completadas en la lista de pendientes
                    $asignaciones_completadas_pendientes = array_filter($stats['asignaciones']['pendientes'], function ($asignacion) {
                        return in_array($asignacion['estado'], ['completada', 'verificada']);
                    });

                    $todas_completadas = array_merge($stats['asignaciones']['completadas'], $asignaciones_completadas_pendientes);

                    if (empty($todas_completadas)):
                    ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle fa-3x mb-3"></i><br>
                                No hay asignaciones completadas recientemente.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todas_completadas as $asignacion): ?>
                            <?php
                            $estado = $asignacion['estado'];
                            $clase_estado = $estado == 'completada' ? 'bg-success' : 'bg-secondary';
                            $texto_estado = $estado == 'completada' ? 'Completada' : 'Verificada';
                            $icono_estado = $estado == 'completada' ? 'fa-check-circle' : 'fa-clipboard-check';
                            ?>
                            <div class="col-md-3 col-sm-6 mb-4 habitacion-card-completada" data-numero="<?= $asignacion['numero_habitacion'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="card-title">N°: <?= htmlspecialchars($asignacion['numero_habitacion']) ?></h5>
                                        <div class="badge <?= $clase_estado ?> mb-3">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            <?= htmlspecialchars($asignacion['tipo_habitacion']) ?><br>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($asignacion['fecha'])) ?><br>
                                            <i class="far fa-check-circle mr-1"></i>
                                            <?= isset($asignacion['fechaactualizacion']) ? date('H:i', strtotime($asignacion['fechaactualizacion'])) : 'Recientemente' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

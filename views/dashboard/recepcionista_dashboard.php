<!-- Indicadores clave para Recepcionista -->
<div class="row">
    <div class="col-lg-3 col-md-6 col-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $stats['banos']['disponibles'] ?? 0 ?></h3>
                <p>Baños Disponibles</p>
            </div>
            <div class="icon">
                <i class="fas fa-toilet"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $stats['servicios_bano']['total_hoy'] ?? 0 ?></h3>
                <p>Servicios Baño Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-shower"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['pendientes'] ?? 0 ?></h3>
                <p>Limpiezas Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-broom"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['hoy'] ?? 0 ?></h3>
                <p>Limpiezas Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>
</div>

<!-- Baños disponibles y Asignaciones de limpieza -->
<div class="row">
    <!-- Baños disponibles -->
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-toilet mr-1"></i>
                    Baños Disponibles
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtro para baños -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group input-group-sm" style="max-width: 200px;">
                        <input type="text" id="buscarBano" class="form-control" placeholder="Buscar baño">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Grid de baños disponibles -->
                <div class="row">
                    <?php
                    if (empty($stats['banos']['lista_disponibles'])):
                    ?>
                        <div class="col-12 text-center py-3">
                            <p class="text-muted mb-0">No hay baños disponibles en este momento.</p>
                        </div>
                        <?php
                    else:
                        foreach ($stats['banos']['lista_disponibles'] as $bano):
                            // Determinar clase y texto según el estado
                            $estado = isset($bano['estado']) ? $bano['estado'] : 'disponible';
                            $clase_estado = '';
                            $texto_estado = '';
                            $icono_estado = '';

                            switch ($estado) {
                                case 'disponible':
                                    $clase_estado = 'bg-success';
                                    $texto_estado = 'Disponible';
                                    $icono_estado = 'fa-check-circle';
                                    break;
                                case 'ocupada':
                                    $clase_estado = 'bg-danger';
                                    $texto_estado = 'Ocupado';
                                    $icono_estado = 'fa-user';
                                    break;
                                case 'limpieza':
                                    $clase_estado = 'bg-warning';
                                    $texto_estado = 'Limpieza';
                                    $icono_estado = 'fa-broom';
                                    break;
                                case 'mantenimiento':
                                case 'fuera_servicio':
                                    $clase_estado = 'bg-secondary';
                                    $texto_estado = 'Mantenimiento';
                                    $icono_estado = 'fa-tools';
                                    break;
                            }
                        ?>
                            <div class="col-lg-6 col-md-12 mb-3 bano-item" data-estado="<?= $estado ?>" data-numero="<?= $bano['numero'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-toilet fa-2x text-muted"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Baño #<?= htmlspecialchars($bano['numero']) ?></h5>
                                        <div class="badge <?= $clase_estado ?> mb-2">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted mb-1">
                                            <?= htmlspecialchars($bano['tipo'] ?? $bano['ubicacion'] ?? 'No especificada') ?>
                                        </p>
                                        <p class="card-text text-primary font-weight-bold mb-0">
                                            <?= $bano['precio'] ?? '10.00' ?> Bs.
                                        </p>
                                    </div>
                                    <div class="card-footer p-0">
                                        <a href="<?= $URL; ?>views/servicios-bano/create.php?idbano=<?= $bano['idbano'] ?>"
                                            class="btn btn-success btn-sm btn-block rounded-0">
                                            <i class="fas fa-plus-circle mr-1"></i> Nuevo Servicio
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?= $URL; ?>views/servicios-bano/create.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Nuevo Servicio
                </a>
            </div>
        </div>
    </div>

    <!-- Asignaciones de limpieza -->
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-broom mr-1"></i>
                    Asignaciones de Limpieza
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Habitación</th>
                                <th>Responsable</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($stats['asignaciones']['todas'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        No hay asignaciones de limpieza pendientes.
                                    </td>
                                </tr>
                                <?php
                            else :
                                // Mostrar solo las últimas 5 asignaciones, priorizando las pendientes
                                $asignaciones_mostrar = array_slice($stats['asignaciones']['todas'], 0, 5);
                                foreach ($asignaciones_mostrar as $asignacion):
                                    $estado = $asignacion['estado'];
                                    $clase_estado = '';
                                    $texto_estado = '';

                                    switch ($estado) {
                                        case 'pendiente':
                                            $clase_estado = 'badge-warning';
                                            $texto_estado = 'Pendiente';
                                            break;
                                        case 'enprogreso':
                                            $clase_estado = 'badge-primary';
                                            $texto_estado = 'En Progreso';
                                            break;
                                        case 'completada':
                                            $clase_estado = 'badge-success';
                                            $texto_estado = 'Completada';
                                            break;
                                        case 'verificada':
                                            $clase_estado = 'badge-secondary';
                                            $texto_estado = 'Verificada';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= htmlspecialchars($asignacion['numero_habitacion']) ?></strong>
                                            <small class="d-block text-muted"><?= htmlspecialchars($asignacion['tipo_habitacion']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($asignacion['nombre_usuario']) ?></td>
                                        <td><?= date('H:i', strtotime($asignacion['hora'])) ?></td>
                                        <td>
                                            <span class="badge <?= $clase_estado ?>"><?= $texto_estado ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?= $URL; ?>views/limpieza/index.php"
                                                    class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($estado == 'completada'): ?>
                                                    <button type="button" class="btn btn-secondary btn-sm cambiar-estado-asignacion"
                                                        data-id="<?= $asignacion['idasignacion']; ?>"
                                                        data-estado="verificada"
                                                        data-toggle="tooltip"
                                                        title="Verificar limpieza"
                                                        aria-label="Verificar limpieza">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?= $URL; ?>views/limpieza/index.php" class="btn btn-warning">
                    <i class="fas fa-plus-circle mr-1"></i> Nueva Asignación
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Panel de control visual de habitaciones -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-th mr-1"></i>
                    Panel de Control de Habitaciones
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="buscarHabitacion" class="form-control float-right" placeholder="Buscar habitación">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Estado de las habitaciones</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary filtro-habitacion active" data-estado="todos">Todos</button>
                        <button type="button" class="btn btn-outline-success filtro-habitacion" data-estado="disponible">Disponible</button>
                        <button type="button" class="btn btn-outline-warning filtro-habitacion" data-estado="limpieza">Limpieza</button>
                        <button type="button" class="btn btn-outline-danger filtro-habitacion" data-estado="ocupada">Ocupada</button>
                        <button type="button" class="btn btn-outline-secondary filtro-habitacion" data-estado="mantenimiento">Mantenimiento</button>
                    </div>
                </div>

                <!-- Grid de habitaciones en estilo de tarjetas -->
                <div class="row">
                    <?php
                    // Todas las habitaciones del sistema
                    if (empty($stats['habitaciones'])):
                    ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted">No hay habitaciones registradas en el sistema.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($stats['habitaciones'] as $habitacion):
                            $estado = $habitacion['estado'];
                            $clase_estado = '';
                            $texto_estado = '';
                            $icono_estado = '';

                            switch ($estado) {
                                case 'disponible':
                                    $clase_estado = 'bg-success';
                                    $texto_estado = 'Disponible';
                                    $icono_estado = 'fa-check-circle';
                                    break;
                                case 'ocupada':
                                    $clase_estado = 'bg-danger';
                                    $texto_estado = 'Ocupada';
                                    $icono_estado = 'fa-user';
                                    break;
                                case 'limpieza':
                                    $clase_estado = 'bg-warning';
                                    $texto_estado = 'Limpieza';
                                    $icono_estado = 'fa-broom';
                                    break;
                                case 'mantenimiento':
                                    $clase_estado = 'bg-secondary';
                                    $texto_estado = 'Mantenimiento';
                                    $icono_estado = 'fa-tools';
                                    break;
                            }
                        ?>
                            <div class="col-md-3 col-sm-6 mb-4 habitacion-item" data-estado="<?= $estado ?>" data-numero="<?= $habitacion['numero'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-3x text-muted"></i>
                                        </div>
                                        <h3 class="card-title mb-3">N°: <?= htmlspecialchars($habitacion['numero']) ?></h3>
                                        <div class="badge <?= $clase_estado ?> mb-3">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            <?= htmlspecialchars($habitacion['tipo_habitacion']) ?>
                                        </p>
                                    </div>
                                    <div class="card-footer p-0">
                                        <div class="btn-group d-flex">
                                            <a href="<?= $URL; ?>views/habitaciones/show.php?id=<?= $habitacion['id_habitacion'] ?>"
                                                class="btn btn-info btn-sm rounded-0" style="flex: 1;">
                                                <i class="fas fa-eye mr-1"></i> Detalles
                                            </a>

                                            <?php if ($estado == 'disponible'): ?>
                                                <a href="<?= $URL; ?>views/recepcion/create.php?idhabitacion=<?= $habitacion['id_habitacion'] ?>"
                                                    class="btn btn-success btn-sm rounded-0" style="flex: 1;">
                                                    <i class="fas fa-key mr-1"></i> Asignar
                                                </a>
                                            <?php elseif ($estado == 'ocupada'): ?>
                                                <a href="<?= $URL; ?>views/recepcion/index.php?id=<?= $habitacion['id_habitacion'] ?>"
                                                    class="btn btn-danger btn-sm rounded-0" style="flex: 1;">
                                                    <i class="fas fa-sign-out-alt mr-1"></i> Checkout
                                                </a>
                                            <?php elseif ($estado == 'limpieza'): ?>
                                                <button type="button" class="btn btn-warning btn-sm rounded-0 btn-asignar-limpieza"
                                                    data-id="<?= $habitacion['id_habitacion'] ?>" style="flex: 1;">
                                                    <i class="fas fa-broom mr-1"></i> Asignar
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary btn-sm rounded-0 btn-cambiar-estado"
                                                    data-id="<?= $habitacion['id_habitacion'] ?>"
                                                    data-estado-actual="<?= $estado ?>" style="flex: 1;">
                                                    <i class="fas fa-exchange-alt mr-1"></i> Cambiar Estado
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
            <div class="card-footer text-center">
                <a href="<?= $URL; ?>views/habitaciones" class="btn btn-primary">
                    <i class="fas fa-bed mr-1"></i> Gestionar Habitaciones
                </a>
                <a href="<?= $URL; ?>views/limpieza" class="btn btn-warning ml-2">
                    <i class="fas fa-broom mr-1"></i> Asignar Limpieza
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Accesos rápidos para Recepcionista -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Accesos Rápidos
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/servicios-bano/create.php" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-shower"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Nuevo Servicio Baño</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/limpieza" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-broom"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Asignar Limpieza</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/clientes/create.php" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-user-plus"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Nuevo Cliente</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/almacenamiento-equipaje/create.php" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-luggage-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Registrar Equipaje</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

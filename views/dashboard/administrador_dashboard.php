<!-- Dashboard Principal del Administrador - Actualizado según requerimientos -->

<?php
$dashboard_data = [
    'habEstadisticas' => [
        'total' => $stats['habitaciones']['ocupacion']['total'] ?? 0,
        'disponibles' => $stats['habitaciones']['por_estado']['disponible'] ?? 0,
        'ocupadas' => $stats['habitaciones']['por_estado']['ocupada'] ?? 0,
        'mantenimiento' => $stats['habitaciones']['por_estado']['mantenimiento'] ?? 0,
        'limpieza' => $stats['habitaciones']['por_estado']['limpieza'] ?? 0,
    ],
    'graficos' => [
        'labels' => !empty($stats['graficos']['labels']) ? $stats['graficos']['labels'] : ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        'fechasCompletas' => !empty($stats['graficos']['fechas_completas']) ? $stats['graficos']['fechas_completas'] : ['24/06/2025', '25/06/2025', '26/06/2025', '27/06/2025', '28/06/2025', '29/06/2025', '30/06/2025'],
        'serviciosBano' => !empty($stats['graficos']['servicios_bano']) ? $stats['graficos']['servicios_bano'] : array_fill(0, 7, 0),
        'equipajes' => !empty($stats['graficos']['equipajes']) ? $stats['graficos']['equipajes'] : array_fill(0, 7, 0),
        'ocupacion' => !empty($stats['graficos']['ocupacion']) ? $stats['graficos']['ocupacion'] : array_fill(0, 7, 0),
        'ingresos' => !empty($stats['graficos']['ingresos']) ? $stats['graficos']['ingresos'] : array_fill(0, 7, 0),
        'banos' => [
            'disponibles' => $stats['banos']['disponibles'] ?? 0,
            'mantenimiento' => $stats['banos']['mantenimiento'] ?? 0,
            'fuera_servicio' => $stats['banos']['fuera_servicio'] ?? 0,
        ],
    ],
    'habitaciones' => $stats['lista_habitaciones'] ?? [],
    'habitacionesPorPiso' => $stats['habitaciones_por_piso'] ?? [],
];
?>
<div id="dashboard-admin-root" data-module="dashboard-admin" data-dashboard="<?= htmlspecialchars(json_encode($dashboard_data), ENT_QUOTES) ?>"></div>

<!-- Resumen de Estadísticas Generales -->
<div class="row">
    <div class="col-lg-3 col-md-6 col-12">
        <div class="info-box bg-gradient-info">
            <span class="info-box-icon"><i class="fas fa-hotel"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ocupación Habitaciones</span>
                <span class="info-box-number">
                    <?= $stats['habitaciones']['ocupacion']['ocupadas'] ?? 0 ?>/<?= $stats['habitaciones']['ocupacion']['total'] ?? 0 ?>
                </span>
                <div class="progress">
                    <div class="progress-bar" style="width: <?= $stats['habitaciones']['ocupacion']['porcentaje'] ?? 0 ?>%"></div>
                </div>
                <span class="progress-description">
                    <?= $stats['habitaciones']['ocupacion']['porcentaje'] ?? 0 ?>% de ocupación actual
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="info-box bg-gradient-warning">
            <span class="info-box-icon"><i class="fas fa-broom"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Limpiezas Pendientes</span>
                <span class="info-box-number">
                    <?= $stats['limpieza_pendientes']['total_pendientes'] ?? 0 ?>
                </span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    <i class="fas fa-clock"></i>
                    <?= $stats['limpieza_pendientes']['pendientes_hoy'] ?? 0 ?> para hoy
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="info-box bg-gradient-success">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Clientes Registrados</span>
                <span class="info-box-number">
                    <?= $stats['personas']['total'] ?? 0 ?>
                </span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    <?= $stats['personas']['nuevos_mes'] ?? 0 ?> nuevos este mes
                </span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="info-box bg-gradient-danger">
            <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ingresos Totales (Hoy)</span>
                <span class="info-box-number">
                    <?= number_format($stats['ingresos_totales']['total'] ?? 0, 2) ?> Bs.
                </span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    <i class="fas fa-chart-line"></i> Todos los servicios
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Panel de KPIs y Gráfico Principal -->
<div class="row">
    <!-- Gráfico Principal -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                    <h3 class="card-title">Actividad Reciente (Últimos 7 días)</h3>
                    <!-- Selector simple de métrica -->
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary metrica-btn active" data-metrica="servicios_equipajes">Servicios</button>
                        <button type="button" class="btn btn-outline-success metrica-btn" data-metrica="ocupacion">Ocupación</button>
                        <button type="button" class="btn btn-outline-danger metrica-btn" data-metrica="ingresos">Ingresos</button>
                    </div>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="actividad-chart" height="403"></canvas>
                </div>
                <div class="d-flex flex-row justify-content-end">
                    <!-- Leyenda de gráfico - cambiará según el gráfico seleccionado -->
                    <div id="chart-legend">
                        <span class="mr-2"><i class="fas fa-square text-primary"></i> Servicios Baño</span>
                        <span><i class="fas fa-square text-warning"></i> Equipajes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs y Distribución -->
    <div class="col-lg-4">
        <!-- KPIs Clave -->
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Métricas Clave</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-shower text-info mr-2"></i>
                            Servicios de Baño (Hoy)
                        </div>
                        <span class="badge badge-info badge-pill">
                            <?= $stats['servicios_bano']['total_hoy'] ?? 0 ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-luggage-cart text-warning mr-2"></i>
                            Equipajes Almacenados
                        </div>
                        <span class="badge badge-warning badge-pill">
                            <?= $stats['equipajes']['activos'] ?? 0 ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-bed text-success mr-2"></i>
                            Habitaciones Disponibles
                        </div>
                        <span class="badge badge-success badge-pill">
                            <?= $stats['habitaciones']['por_estado']['disponible'] ?? 0 ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users text-primary mr-2"></i>
                            Clientes Activos
                        </div>
                        <span class="badge badge-primary badge-pill">
                            <?= $stats['personas']['activos'] ?? 0 ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Estado de Baños -->
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Estado de Baños</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Gráfico de dona para estado de baños -->
                <div class="position-relative">
                    <canvas id="banos-chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas y Panel de Control -->
<div class="row">
    <!-- Productos con Stock Bajo -->
    <div class="col-md-6">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Productos con Stock Bajo
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
                                <th>Producto</th>
                                <th class="d-none d-md-table-cell">Categoría</th>
                                <th>Stock</th>
                                <th class="d-none d-md-table-cell">Mín.</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['productos']['lista_stock_bajo'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        No hay productos con stock bajo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stats['productos']['lista_stock_bajo'] as $producto): ?>
                                    <?php
                                    $porcentaje = round(($producto['stock'] / $producto['stock_minimo']) * 100);
                                    $clase = $porcentaje <= 50 ? 'danger' : ($porcentaje <= 75 ? 'warning' : 'info');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($producto['categoria']) ?></td>
                                        <td class="text-center font-weight-bold">
                                            <span class="badge badge-<?= $clase ?>"><?= $producto['stock'] ?></span>
                                        </td>
                                        <td class="text-center d-none d-md-table-cell"><?= $producto['stock_minimo'] ?></td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-<?= $clase ?>" style="width: <?= $porcentaje ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?= $URL; ?>views/compras/create.php?producto=<?= $producto['idproducto'] ?>"
                                                class="btn btn-xs btn-primary">
                                                <i class="fas fa-plus"></i> Comprar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row g-2 justify-content-lg-center">
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/productos" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fas fa-box mr-1"></i> Ver todos los productos
                        </a>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/compras/create.php" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-shopping-cart mr-1"></i> Nueva Compra
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Servicios de Baño Recientes -->
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shower mr-1"></i>
                    Servicios de Baño Recientes
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
                                <th>Baño</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['servicios_bano']['ultimos'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <i class="fas fa-info-circle text-info mr-1"></i>
                                        No hay servicios de baño recientes.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stats['servicios_bano']['ultimos'] as $servicio):
                                    $estado_clase = '';
                                    switch ($servicio['estado']) {
                                        case 'iniciado':
                                            $estado_clase = 'primary';
                                            break;
                                        case 'finalizado':
                                            $estado_clase = 'success';
                                            break;
                                        case 'cancelado':
                                            $estado_clase = 'danger';
                                            break;
                                        default:
                                            $estado_clase = 'secondary';
                                    }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($servicio['numero_bano']) ?></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($servicio['fecha'])) ?>
                                            <span class="text-muted d-block small">
                                                <?= isset($servicio['hora']) ? $servicio['hora'] : date('H:i', strtotime($servicio['fecha'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $estado_clase ?>">
                                                <?= ucfirst($servicio['estado']) ?>
                                            </span>
                                        </td>
                                        <td class="font-weight-bold">
                                            <?= number_format($servicio['precio'], 2) ?> Bs
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row g-2 justify-content-lg-center">
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-sm btn-outline-info w-100">
                            <i class="fas fa-list mr-1"></i> Ver todos los servicios
                        </a>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/servicios-bano/create.php" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-plus mr-1"></i> Nuevo Servicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel de Control Operativo -->
<div class="row">
    <!-- Estado de Habitaciones -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header card-outline card-primary">
                <h3 class="card-title">
                    <i class="fas fa-bed mr-1"></i>
                    Estado de Habitaciones
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Resumen de estados en tarjetas responsivas -->
                <div class="row mb-3">
                    <div class="col-12 col-sm-6 col-md-3 mb-2">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Disponibles</span>
                                <span class="info-box-number">
                                    <?= $stats['habitaciones']['por_estado']['disponible'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-2">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-user"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ocupadas</span>
                                <span class="info-box-number">
                                    <?= $stats['habitaciones']['por_estado']['ocupada'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-2">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-tools"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Mantenimiento</span>
                                <span class="info-box-number">
                                    <?= $stats['habitaciones']['por_estado']['mantenimiento'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-2">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-broom"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Limpieza</span>
                                <span class="info-box-number">
                                    <?= $stats['habitaciones']['por_estado']['limpieza'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa de habitaciones responsivo -->
                <div class="habitaciones-mapa mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Mapa de Habitaciones</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-default active" data-filter="all">Todas (<span id="contador-total"><?= $stats['habitaciones']['total'] ?? 0 ?></span>)</button>
                            <button type="button" class="btn btn-success" data-filter="disponible">Disponibles (<span id="contador-disponibles"><?= $stats['habitaciones']['por_estado']['disponible'] ?? 0 ?></span>)</button>
                            <button type="button" class="btn btn-warning" data-filter="ocupada">Ocupadas (<span id="contador-ocupadas"><?= $stats['habitaciones']['por_estado']['ocupada'] ?? 0 ?></span>)</button>
                            <button type="button" class="btn btn-danger" data-filter="mantenimiento">Mantenimiento (<span id="contador-mantenimiento"><?= $stats['habitaciones']['por_estado']['mantenimiento'] ?? 0 ?></span>)</button>
                            <button type="button" class="btn btn-primary" data-filter="limpieza">Limpieza (<span id="contador-limpieza"><?= $stats['habitaciones']['por_estado']['limpieza'] ?? 0 ?></span>)</button>
                        </div>
                    </div>

                    <!-- Contenedor donde JS renderizará las habitaciones -->
                    <div id="mapa-habitaciones-container" class="row mapa-habitaciones">
                        <!-- El contenido se generará dinámicamente desde JS -->
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row g-2 justify-content-lg-center">
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/habitaciones" class="btn btn-primary w-100">
                            <i class="fas fa-bed mr-1"></i> Gestionar Habitaciones
                        </a>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/tarifas" class="btn btn-info w-100">
                            <i class="fas fa-money-bill-wave mr-1"></i> Gestionar Tarifas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos y Últimos Usuarios -->
    <div class="col-md-4">
        <!-- Accesos Rápidos -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Acciones Rápidas
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="<?= $URL; ?>views/recepcion/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-key text-success mr-2"></i> Nuevo Check-in
                        </div>
                        <span class="badge badge-success badge-pill"><i class="fas fa-arrow-right"></i></span>
                    </a>
                    <a href="<?= $URL; ?>views/servicios-bano/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-shower text-info mr-2"></i> Nuevo Servicio de Baño
                        </div>
                        <span class="badge badge-info badge-pill"><i class="fas fa-arrow-right"></i></span>
                    </a>
                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-luggage-cart text-warning mr-2"></i> Registrar Equipaje
                        </div>
                        <span class="badge badge-warning badge-pill"><i class="fas fa-arrow-right"></i></span>
                    </a>
                    <a href="<?= $URL; ?>views/clientes/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-plus text-primary mr-2"></i> Nuevo Cliente
                        </div>
                        <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
                    </a>
                    <a href="<?= $URL; ?>views/usuarios/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-cog text-purple mr-2"></i> Nuevo Usuario
                        </div>
                        <span class="badge badge-purple badge-pill"><i class="fas fa-arrow-right"></i></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Últimos usuarios -->
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-1"></i>
                    Equipo de Trabajo
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer/expandir panel">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats['usuarios']['ultimos'])): ?>
                    <div class="text-center py-3 w-100">
                        <p class="text-muted">No hay usuarios registrados recientemente.</p>
                    </div>
                <?php else: ?>
                    <!-- Vista para dispositivos pequeños (móviles) -->
                    <div class="d-block d-md-none">
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($stats['usuarios']['ultimos'], 0, 6) as $usuario): ?>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $URL; ?>public/uploads/usuarios/<?= $usuario['imagen'] ?? 'user_default.jpg' ?>"
                                            alt="Foto de <?= htmlspecialchars($usuario['nombre']) ?>" class="img-circle mr-3"
                                            style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <div class="font-weight-bold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                            <span class="badge badge-<?= $usuario['cargo'] == 'Administrador' ? 'primary' : ($usuario['cargo'] == 'Recepcionista' ? 'success' : 'info') ?>">
                                                <?= htmlspecialchars($usuario['cargo']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Vista para dispositivos medianos y grandes -->
                    <div class="d-none d-md-block">
                        <ul class="users-list clearfix">
                            <?php foreach (array_slice($stats['usuarios']['ultimos'], 0, 8) as $usuario): ?>
                                <li>
                                    <img src="<?= $URL; ?>public/uploads/usuarios/<?= $usuario['imagen'] ?? 'user_default.jpg' ?>"
                                        alt="Foto de <?= htmlspecialchars($usuario['nombre']) ?>" class="img-circle elevation-1"
                                        style="width: 60px; height: 60px; object-fit: cover;">
                                    <a class="users-list-name" href="<?= $URL; ?>views/usuarios/show.php?id=<?= $usuario['idusuario'] ?>">
                                        <?= htmlspecialchars($usuario['nombre']) ?>
                                    </a>
                                    <span class="users-list-date">
                                        <span class="badge badge-<?= $usuario['cargo'] == 'Administrador' ? 'primary' : ($usuario['cargo'] == 'Recepcionista' ? 'success' : 'info') ?>">
                                            <?= htmlspecialchars($usuario['cargo']) ?>
                                        </span>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="row justify-content-lg-center">
                    <div class="col-12 col-sm-auto">
                        <a href="<?= $URL; ?>views/usuarios" class="btn btn-sm btn-info w-100">
                            <i class="fas fa-users mr-1"></i> Ver todos los usuarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

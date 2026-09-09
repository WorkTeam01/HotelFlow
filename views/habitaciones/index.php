<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'habitaciones')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
$module_styles = ['habitaciones/habitaciones'];
$module_scripts = ['habitaciones/cambiar-estado-habitaciones', 'habitaciones/index-habitaciones'];
include_once '../layouts/header.php';

$controller = new HabitacionController();

// Obtener todas las habitaciones - no hay filtros en el backend
$habitaciones = $controller->index();
$tipos_habitacion = $controller->getTiposHabitacion();
$pisos = $controller->getPisos();
$estadisticas = $controller->getEstadisticas();
$estados_ui = HabitacionController::estadosHabitacion();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Habitaciones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Habitaciones</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Resumen por estado: también actúan como filtro rápido del listado -->
        <div class="row" id="resumen-estados">
            <?php
            $tarjetas_estado = [
                ['filtro' => '',              'texto' => 'Total',         'icono' => 'bed',          'clase' => 'info',      'valor' => $estadisticas['total']],
                ['filtro' => 'disponible',    'texto' => 'Disponibles',   'icono' => $estados_ui['disponible']['icono'],    'clase' => $estados_ui['disponible']['clase'],    'valor' => $estadisticas['disponibles']],
                ['filtro' => 'ocupada',       'texto' => 'Ocupadas',      'icono' => $estados_ui['ocupada']['icono'],       'clase' => $estados_ui['ocupada']['clase'],       'valor' => $estadisticas['ocupadas']],
                ['filtro' => 'limpieza',      'texto' => 'Por limpiar',   'icono' => $estados_ui['limpieza']['icono'],      'clase' => $estados_ui['limpieza']['clase'],      'valor' => $estadisticas['limpieza']],
                ['filtro' => 'mantenimiento', 'texto' => 'Mantenimiento', 'icono' => $estados_ui['mantenimiento']['icono'], 'clase' => $estados_ui['mantenimiento']['clase'], 'valor' => $estadisticas['mantenimiento']],
            ];
            foreach ($tarjetas_estado as $tarjeta) :
            ?>
                <div class="col-6 col-md-4 col-lg">
                    <a href="#" role="button"
                        class="info-box filtro-estado<?= $tarjeta['filtro'] === '' ? ' filtro-estado--activo' : ''; ?>"
                        data-filtro-estado="<?= $tarjeta['filtro']; ?>"
                        aria-pressed="<?= $tarjeta['filtro'] === '' ? 'true' : 'false'; ?>"
                        aria-label="Filtrar habitaciones: <?= $tarjeta['texto']; ?>">
                        <span class="info-box-icon bg-<?= $tarjeta['clase']; ?> elevation-1"><i class="fas fa-<?= $tarjeta['icono']; ?>"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= $tarjeta['texto']; ?></span>
                            <span class="info-box-number" data-contador-estado="<?= $tarjeta['filtro']; ?>"><?= $tarjeta['valor']; ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
            <div class="col-6 col-md-4 col-lg">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tipos</span>
                        <span class="info-box-number"><?= count($estadisticas['por_tipo']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros avanzados -->
        <div class="row">
            <div class="col-md-12">
                <div class="card collapsed-card">
                    <div class="card-header card-outline card-info">
                        <h3 class="card-title">Filtros Avanzados</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">
                            El estado se filtra desde las tarjetas de resumen de arriba.
                        </p>
                        <!-- El valor lo controlan las tarjetas #resumen-estados -->
                        <input type="hidden" id="filtro-estado" value="">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filtro-tipo">Tipo de Habitación:</label>
                                    <select class="form-control select2" id="filtro-tipo">
                                        <option value="">Todos los tipos</option>
                                        <?php foreach ($tipos_habitacion as $tipo): ?>
                                            <option value="<?= htmlspecialchars($tipo['nombre']); ?>">
                                                <?= htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filtro-piso">Piso:</label>
                                    <select class="form-control select2" id="filtro-piso">
                                        <option value="">Todos los pisos</option>
                                        <?php foreach ($pisos as $piso): ?>
                                            <option value="<?= htmlspecialchars($piso['nombre']); ?>">
                                                <?= htmlspecialchars($piso['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filtro-precio">Precio base (mínimo):</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Bs</span>
                                        </div>
                                        <input type="number" class="form-control" id="filtro-precio" placeholder="Ej: 100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" id="btn-aplicar-filtros" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">
                                    <i class="fas fa-broom"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Habitaciones</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/habitaciones/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Habitación
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer listado">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaHabitaciones" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center">Habitación</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Piso</th>
                                        <th class="text-center">Capacidad</th>
                                        <th class="text-center">Precio Base (Bs)</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($habitaciones as $habitacion) :
                                        $estado = $habitacion['estado'];
                                        $estado_ui = HabitacionController::estadoHabitacion($estado);
                                        $numero = htmlspecialchars($habitacion['numero']);

                                        // Transiciones ofrecidas según el estado actual
                                        $acciones_estado = [];
                                        if ($estado !== 'disponible') {
                                            $acciones_estado[] = 'disponible';
                                        }
                                        if ($estado === 'disponible') {
                                            $acciones_estado[] = 'ocupada';
                                        }
                                        if ($estado !== 'limpieza') {
                                            $acciones_estado[] = 'limpieza';
                                        }
                                        if ($estado !== 'mantenimiento') {
                                            $acciones_estado[] = 'mantenimiento';
                                        }
                                    ?>
                                        <tr data-estado="<?= $estado; ?>">
                                            <td class="text-center"><?= $numero; ?></td>
                                            <td><?= htmlspecialchars($habitacion['tipo_nombre']); ?></td>
                                            <td><?= htmlspecialchars($habitacion['piso_nombre']); ?></td>
                                            <td class="text-center"><?= $habitacion['capacidad_actual']; ?></td>
                                            <td class="text-right precio-base"><?= number_format($habitacion['precio_base'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $estado_ui['badge']; ?> p-2">
                                                    <i class="fas fa-<?= $estado_ui['icono']; ?> mr-1"></i><?= $estado_ui['label']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/habitaciones/show.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-info btn-sm hab-touch" title="Ver detalle" aria-label="Ver detalle de la habitación <?= $numero; ?>">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/habitaciones/update.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-warning btn-sm hab-touch" title="Editar" aria-label="Editar la habitación <?= $numero; ?>">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                    </a>
                                                    <?php foreach ($acciones_estado as $destino) :
                                                        $destino_ui = HabitacionController::estadoHabitacion($destino);
                                                    ?>
                                                        <button type="button" class="btn btn-<?= $destino_ui['clase']; ?> btn-sm hab-touch cambiar-estado"
                                                            data-id="<?= $habitacion['id_habitacion']; ?>"
                                                            data-estado="<?= $destino; ?>"
                                                            data-estado-actual="<?= $estado; ?>"
                                                            title="Marcar como <?= $destino_ui['label']; ?>"
                                                            aria-label="Marcar la habitación <?= $numero; ?> como <?= $destino_ui['label']; ?>">
                                                            <i class="fas fa-<?= $destino_ui['icono']; ?>" aria-hidden="true"></i>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
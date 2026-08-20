<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Definir recursos del módulo - reutilizando CSS del index
$module_styles = ['recepciones/index-recepciones'];
$module_scripts = ['recepciones/index-recepciones'];

// Panel de tarjetas por piso, sin tabla: no necesita DataTables
$skip_datatables = true;

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado después de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Instanciar el controlador
$controller = new RecepcionController();

// Obtener datos para la vista
$datos = $controller->index();
$habitaciones_disponibles = $datos['habitaciones_disponibles'];
$recepciones_en_curso = $datos['recepciones_en_curso'];
$habitaciones_mantenimiento = $datos['habitaciones_mantenimiento'];
$llegadas_hoy = $datos['llegadas_hoy'];
$reservas_proximas = $datos['reservas_proximas'];
$estadisticas = $datos['estadisticas'];

// Agrupar las habitaciones por pisos para cada sección
$habitaciones_disponibles_por_piso = RecepcionController::agruparHabitacionesPorPiso($habitaciones_disponibles);
$habitaciones_mantenimiento_por_piso = RecepcionController::agruparHabitacionesPorPiso($habitaciones_mantenimiento);

// Agrupar recepciones en curso por piso
$recepciones_por_piso_ocupadas = [];
foreach ($recepciones_en_curso as $recepcion) {
    $piso = $recepcion['piso_nombre'] ?? 'Sin piso definido';
    if (!isset($recepciones_por_piso_ocupadas[$piso])) {
        $recepciones_por_piso_ocupadas[$piso] = [];
    }
    $recepciones_por_piso_ocupadas[$piso][] = $recepcion;
}

// Ordenar pisos alfabéticamente y habitaciones dentro de cada piso
ksort($recepciones_por_piso_ocupadas);
foreach ($recepciones_por_piso_ocupadas as &$recepciones_piso) {
    usort($recepciones_piso, function ($a, $b) {
        return strnatcmp($a['numero_habitacion'], $b['numero_habitacion']);
    });
}

// Obtener fecha actual
$fecha_actual = date('d/m/Y');
$hora_actual = date('H:i');

// Obtener todos los pisos únicos para los filtros
$pisos_unicos = [];
foreach (array_merge($habitaciones_disponibles, $habitaciones_mantenimiento) as $hab) {
    $piso = $hab['piso_nombre'] ?? 'Sin piso';
    if (!in_array($piso, $pisos_unicos)) {
        $pisos_unicos[] = $piso;
    }
}
foreach ($recepciones_en_curso as $rec) {
    if (isset($rec['piso_nombre'])) {
        $piso = $rec['piso_nombre'];
        if (!in_array($piso, $pisos_unicos)) {
            $pisos_unicos[] = $piso;
        }
    }
}
sort($pisos_unicos);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Recepciones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Recepción</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Filtros y botones de acción -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-primary collapsed-card">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h3 class="card-title mb-2 mb-sm-0">
                                <i class="fas fa-filter mr-2"></i>Filtros y Controles
                            </h3>
                            <div class="card-tools">
                                <a href="<?= $URL; ?>views/recepcion/lista-recepciones.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-list"></i> Ver todas las recepciones
                                </a>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir Filtros y Controles">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros de estado -->
                        <div class="row mb-3">
                            <div class="col-lg-12 col-md-12 mb-3 mb-lg-0">
                                <label class="form-label small text-muted mb-2">Estado de habitaciones:</label>
                                <div class="btn-group-responsive">
                                    <button type="button" class="btn btn-primary btn-filter active" data-status="todas">
                                        <i class="fas fa-home d-none d-sm-inline"></i>
                                        <span>Todas</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-filter" data-status="disponibles">
                                        <i class="fas fa-check-circle d-none d-sm-inline"></i>
                                        <span>Disponibles</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-filter" data-status="ocupadas">
                                        <i class="fas fa-bed d-none d-sm-inline"></i>
                                        <span>Ocupadas</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-filter" data-status="mantenimiento">
                                        <i class="fas fa-tools d-none d-sm-inline"></i>
                                        <span>Mantenimiento</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros de pisos -->
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label small text-muted mb-2">Filtrar por pisos:</label>
                                <div class="piso-filters-container">
                                    <button type="button" class="btn btn-secondary btn-sm btn-piso active" data-piso="todos">
                                        <i class="fas fa-building"></i> Todos los pisos
                                    </button>
                                    <?php foreach ($pisos_unicos as $piso): ?>
                                        <button type="button" class="btn btn-outline-info btn-sm btn-piso" data-piso="<?= htmlspecialchars($piso); ?>">
                                            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($piso); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <!-- Sección: Llegadas de hoy (reservas con fecha de entrada = hoy) -->
                <div class="section-llegadas">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-calendar-day text-info mr-2"></i> Llegadas de Hoy</h4>
                        <span class="badge badge-info badge-lg px-3 py-2"><?= count($llegadas_hoy); ?> reservadas</span>
                    </div>

                    <?php if (empty($llegadas_hoy)): ?>
                        <div class="card card-body">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay llegadas reservadas para hoy.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card piso-card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <?php $modo = 'reserva'; ?>
                                    <?php foreach ($llegadas_hoy as $recepcion): ?>
                                        <?php include __DIR__ . '/partials/card-habitacion.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reservas_proximas)): ?>
                        <div class="card card-outline card-secondary collapsed-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt mr-2"></i>Reservas próximas
                                </h5>
                                <div class="card-tools">
                                    <span class="badge badge-secondary mr-2"><?= count($reservas_proximas); ?></span>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir reservas próximas">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php $modo = 'reserva'; ?>
                                    <?php foreach ($reservas_proximas as $recepcion): ?>
                                        <?php include __DIR__ . '/partials/card-habitacion.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sección Principal: Habitaciones Disponibles -->
                <div class="section-disponibles">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-check-circle text-success mr-2"></i> Habitaciones Disponibles</h4>
                        <span class="badge badge-success badge-lg px-3 py-2"><?= count($habitaciones_disponibles); ?> disponibles</span>
                    </div>

                    <?php if (empty($habitaciones_disponibles)): ?>
                        <div class="card card-body">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay habitaciones disponibles en este momento.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($habitaciones_disponibles_por_piso as $nombrePiso => $habitaciones): ?>
                            <div class="card piso-card piso-section mb-4" data-piso="<?= htmlspecialchars($nombrePiso); ?>">
                                <div class="card-header card-outline card-success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>
                                            <?= htmlspecialchars($nombrePiso); ?>
                                        </h5>
                                        <div class="piso-stats">
                                            <span class="badge badge-secondary"><?= count($habitaciones); ?> habitaciones</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="row">
                                        <?php $modo = 'disponible'; ?>
                                        <?php foreach ($habitaciones as $habitacion): ?>
                                            <?php include __DIR__ . '/partials/card-habitacion.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Habitaciones Ocupadas -->
                <div class="section-ocupadas">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-bed text-warning mr-2"></i> Habitaciones Ocupadas</h4>
                        <span class="badge badge-warning badge-lg px-3 py-2"><?= count($recepciones_en_curso); ?> ocupadas</span>
                    </div>

                    <?php if (empty($recepciones_en_curso)): ?>
                        <div class="card card-body">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay habitaciones ocupadas en este momento.
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Habitaciones Ocupadas -->
                        <?php foreach ($recepciones_por_piso_ocupadas as $nombrePiso => $recepciones): ?>
                            <div class="card piso-card piso-section mb-4" data-piso="<?= htmlspecialchars($nombrePiso); ?>">
                                <div class="card-header card-outline card-warning">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>
                                            <?= htmlspecialchars($nombrePiso); ?>
                                        </h5>
                                        <div class="piso-stats">
                                            <span class="badge badge-secondary"><?= count($recepciones); ?> ocupadas</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="row">
                                        <?php $modo = 'ocupada'; ?>
                                        <?php foreach ($recepciones as $recepcion): ?>
                                            <?php include __DIR__ . '/partials/card-habitacion.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Habitaciones en Mantenimiento/Limpieza -->
                <div class="section-mantenimiento">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-tools text-danger mr-2"></i> Habitaciones en Mantenimiento</h4>
                        <span class="badge badge-danger badge-lg px-3 py-2"><?= $estadisticas['mantenimiento']; ?> en mantenimiento</span>
                    </div>

                    <?php if (empty($habitaciones_mantenimiento)): ?>
                        <div class="card card-body">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay habitaciones en mantenimiento en este momento.
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Habitaciones en Mantenimiento/Limpieza -->
                        <?php foreach ($habitaciones_mantenimiento_por_piso as $nombrePiso => $habitaciones): ?>
                            <div class="card piso-card piso-section mb-4" data-piso="<?= htmlspecialchars($nombrePiso); ?>">
                                <div class="card-header card-outline card-danger">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>
                                            <?= htmlspecialchars($nombrePiso); ?>
                                        </h5>
                                        <div class="piso-stats">
                                            <span class="badge badge-secondary"><?= count($habitaciones); ?> en mantenimiento</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="row">
                                        <?php $modo = 'mantenimiento'; ?>
                                        <?php foreach ($habitaciones as $habitacion): ?>
                                            <?php include __DIR__ . '/partials/card-habitacion.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
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

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de habitación no válido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

$id = (int) $_GET['id'];

// Instanciar el controlador
$controller = new HabitacionController();

// Obtener la habitación por su ID
$habitacion = $controller->getById($id);

if (!$habitacion) {
    $_SESSION['mensaje'] = 'Habitación no encontrada.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

// Obtener historial de ocupación y limpieza
$historial_ocupacion = $controller->getHistorialOcupacion($id);
$historial_limpieza = $controller->getHistorialLimpieza($id);

// Incluir el encabezado después de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['habitaciones/show-habitaciones'];
$module_scripts = ['habitaciones/cambiar-estado-habitaciones'];
include_once '../layouts/header.php';

// Presentación de estado (fuente única: HabitacionController::estadoHabitacion)
$estado_ui = HabitacionController::estadoHabitacion($habitacion['estado']);
$color_estado = $estado_ui['clase'];
$icono_estado = $estado_ui['icono'];

// Transiciones ofrecidas desde el detalle (mismo criterio que el listado:
// "ocupada" solo se ofrece si la habitación está disponible)
$estado_habitacion = $habitacion['estado'];
$transiciones = array_values(array_filter(
    ['disponible', 'ocupada', 'limpieza', 'mantenimiento'],
    fn($e) => $e !== $estado_habitacion && ($e !== 'ocupada' || $estado_habitacion === 'disponible')
));
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/habitaciones"><i class="fas fa-bed"></i> Habitaciones</a></li>
                    <li class="breadcrumb-item active">Detalle de Habitación</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <!-- Información principal de la habitación -->
                <div class="card card-<?= $color_estado; ?> card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bed mr-2"></i>
                            Habitación <?= htmlspecialchars($habitacion['numero']); ?>
                        </h3>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <span class="fa-stack fa-3x">
                                <i class="fas fa-circle fa-stack-2x text-<?= $color_estado; ?>"></i>
                                <i class="fas fa-<?= $icono_estado; ?> fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>

                        <h3 class="profile-username text-center"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></h3>
                        <p class="text-muted text-center"><?= htmlspecialchars($habitacion['piso_nombre']); ?></p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Número</b> <span class="float-right"><?= htmlspecialchars($habitacion['numero']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Tipo</b> <span class="float-right"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Piso</b> <span class="float-right"><?= htmlspecialchars($habitacion['piso_nombre']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Capacidad</b> <span class="float-right"><?= (int) $habitacion['capacidad_actual']; ?> <?= (int) $habitacion['capacidad_actual'] === 1 ? 'persona' : 'personas'; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Precio Base</b> <span class="float-right">Bs <?= number_format($habitacion['precio_base'], 2); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Estado</b>
                                <span class="float-right badge <?= $estado_ui['badge']; ?> p-2">
                                    <i class="fas fa-<?= $icono_estado; ?> mr-1"></i>
                                    <?= $estado_ui['label']; ?>
                                </span>
                            </li>
                        </ul>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <a href="<?= $URL; ?>views/habitaciones/update.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-warning btn-block">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $URL; ?>views/habitaciones/index.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($transiciones)) : ?>
                            <p class="text-muted small mb-1">Cambiar estado a:</p>
                            <div class="d-flex flex-wrap" role="group" aria-label="Cambiar estado de la habitación">
                                <?php foreach ($transiciones as $destino) :
                                    $destino_ui = HabitacionController::estadoHabitacion($destino);
                                ?>
                                    <button type="button" class="btn btn-<?= $destino_ui['clase']; ?> mb-1 mr-1 cambiar-estado"
                                        data-id="<?= $habitacion['id_habitacion']; ?>"
                                        data-estado="<?= $destino; ?>"
                                        data-estado-actual="<?= $habitacion['estado']; ?>"
                                        aria-label="Marcar como <?= $destino_ui['label']; ?>">
                                        <i class="fas fa-<?= $destino_ui['icono']; ?>" aria-hidden="true"></i>
                                        <?= $destino_ui['label']; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tabs de información adicional -->
                <div class="card card-tabs">
                    <div class="card-header card-outline card-info p-0">
                        <ul class="nav nav-tabs" id="habitacionTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="historial-ocupacion-tab" data-toggle="pill" href="#historial-ocupacion" role="tab" aria-controls="historial-ocupacion" aria-selected="true">
                                    <i class="fas fa-history mr-1"></i> Historial de Ocupación
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="historial-limpieza-tab" data-toggle="pill" href="#historial-limpieza" role="tab" aria-controls="historial-limpieza" aria-selected="false">
                                    <i class="fas fa-broom mr-1"></i> Historial de Limpieza
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content" id="habitacionTabsContent">
                            <!-- Historial de Ocupación -->
                            <div class="tab-pane fade show active" id="historial-ocupacion" role="tabpanel" aria-labelledby="historial-ocupacion-tab">
                                <?php if (empty($historial_ocupacion)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No hay registros de ocupación para esta habitación.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Cliente</th>
                                                    <th>Entrada</th>
                                                    <th>Salida</th>
                                                    <th>Estado</th>
                                                    <th>Monto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historial_ocupacion as $ocupacion): ?>
                                                    <?php $ocupacion_ui = HabitacionController::badgeEstadoOcupacion($ocupacion['estado']); ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ocupacion['nombre_cliente']); ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($ocupacion['fechaentrada'])); ?></td>
                                                        <td>
                                                            <?= !empty($ocupacion['fechasalida']) ? date('d/m/Y H:i', strtotime($ocupacion['fechasalida'])) : '<span class="text-muted">Pendiente</span>'; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= $ocupacion_ui['badge']; ?>">
                                                                <?= $ocupacion_ui['label']; ?>
                                                            </span>
                                                        </td>
                                                        <td>Bs <?= number_format($ocupacion['montototal'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Historial de Limpieza -->
                            <div class="tab-pane fade" id="historial-limpieza" role="tabpanel" aria-labelledby="historial-limpieza-tab">
                                <?php if (empty($historial_limpieza)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No hay registros de limpieza para esta habitación.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Hora</th>
                                                    <th>Responsable</th>
                                                    <th>Estado</th>
                                                    <th>Observaciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historial_limpieza as $limpieza): ?>
                                                    <?php $limpieza_ui = HabitacionController::badgeEstadoLimpieza($limpieza['estado']); ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y', strtotime($limpieza['fecha'])); ?></td>
                                                        <td><?= date('H:i', strtotime($limpieza['hora'])); ?></td>
                                                        <td><?= htmlspecialchars($limpieza['nombre_usuario']); ?></td>
                                                        <td>
                                                            <span class="badge <?= $limpieza_ui['badge']; ?>">
                                                                <?= $limpieza_ui['label']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?= !empty($limpieza['observaciones']) ? htmlspecialchars($limpieza['observaciones']) : '<span class="text-muted">Sin observaciones</span>'; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información detallada del tipo de habitación -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Detalles del Tipo
                        </h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Tipo:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></dd>

                            <dt class="col-sm-3">Descripción:</dt>
                            <dd class="col-sm-9">
                                <?= !empty($habitacion['tipo_descripcion']) ? htmlspecialchars($habitacion['tipo_descripcion']) : '<em class="text-muted">Sin descripción</em>'; ?>
                            </dd>

                            <dt class="col-sm-3">Capacidad Máxima:</dt>
                            <dd class="col-sm-9"><?= (int) $habitacion['capacidad_maxima']; ?> <?= (int) $habitacion['capacidad_maxima'] === 1 ? 'persona' : 'personas'; ?></dd>
                        </dl>
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


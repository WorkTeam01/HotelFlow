<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($auth->puedeAccederModulo($idusuario, 'habitaciones'))) {
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
$module_scripts = ['habitaciones/show-habitaciones'];
include_once '../layouts/header.php';

// Determinar el color según el estado
$color_estado = '';
$icono_estado = '';
switch ($habitacion['estado']) {
    case 'disponible':
        $color_estado = 'success';
        $icono_estado = 'check-circle';
        break;
    case 'ocupada':
        $color_estado = 'warning';
        $icono_estado = 'user';
        break;
    case 'mantenimiento':
        $color_estado = 'danger';
        $icono_estado = 'tools';
        break;
    case 'limpieza':
        $color_estado = 'primary';
        $icono_estado = 'broom';
        break;
    default:
        $color_estado = 'secondary';
        $icono_estado = 'info-circle';
}
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
                                <b>Número</b> <a class="float-right"><?= htmlspecialchars($habitacion['numero']); ?></a>
                            </li>
                            <li class="list-group-item">
                                <b>Tipo</b> <a class="float-right"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></a>
                            </li>
                            <li class="list-group-item">
                                <b>Piso</b> <a class="float-right"><?= htmlspecialchars($habitacion['piso_nombre']); ?></a>
                            </li>
                            <li class="list-group-item">
                                <b>Capacidad</b> <a class="float-right"><?= $habitacion['capacidad_actual']; ?> personas</a>
                            </li>
                            <li class="list-group-item">
                                <b>Precio Base</b> <a class="float-right">$<?= number_format($habitacion['precio_base'], 2); ?></a>
                            </li>
                            <li class="list-group-item">
                                <b>Estado</b>
                                <span class="float-right badge badge-<?= $color_estado; ?> p-2">
                                    <i class="fas fa-<?= $icono_estado; ?> mr-1"></i>
                                    <?= ucfirst($habitacion['estado']); ?>
                                </span>
                            </li>
                        </ul>

                        <div class="row mb-3">
                            <div class="col-12">
                                <a href="<?= $URL; ?>views/habitaciones/edit.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-warning btn-block">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <a href="<?= $URL; ?>views/habitaciones/index.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-primary btn-block" id="btnCambiarEstado">
                                    <i class="fas fa-exchange-alt"></i> Cambiar Estado
                                </button>
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
                        <dl class="row">
                            <dt class="col-sm-4">Tipo:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($habitacion['tipo_nombre']); ?></dd>

                            <dt class="col-sm-4">Descripción:</dt>
                            <dd class="col-sm-8">
                                <?= !empty($habitacion['tipo_descripcion']) ? htmlspecialchars($habitacion['tipo_descripcion']) : '<em class="text-muted">Sin descripción</em>'; ?>
                            </dd>

                            <dt class="col-sm-4">Capacidad Máxima:</dt>
                            <dd class="col-sm-8"><?= $habitacion['capacidad_maxima']; ?> personas</dd>
                        </dl>
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
                                                    <?php
                                                    $estado_clase = '';
                                                    switch ($ocupacion['estado']) {
                                                        case 'reservado':
                                                            $estado_clase = 'badge-info';
                                                            break;
                                                        case 'en_curso':
                                                            $estado_clase = 'badge-warning';
                                                            break;
                                                        case 'finalizado':
                                                            $estado_clase = 'badge-success';
                                                            break;
                                                        case 'cancelado':
                                                            $estado_clase = 'badge-danger';
                                                            break;
                                                        default:
                                                            $estado_clase = 'badge-secondary';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ocupacion['nombre_cliente']); ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($ocupacion['fechaentrada'])); ?></td>
                                                        <td>
                                                            <?= !empty($ocupacion['fechasalida']) ? date('d/m/Y H:i', strtotime($ocupacion['fechasalida'])) : '<span class="text-muted">Pendiente</span>'; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= $estado_clase; ?>">
                                                                <?= ucfirst(str_replace('_', ' ', $ocupacion['estado'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>$<?= number_format($ocupacion['montototal'], 2); ?></td>
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
                                                    <?php
                                                    $estado_clase = '';
                                                    switch ($limpieza['estado']) {
                                                        case 'pendiente':
                                                            $estado_clase = 'badge-warning';
                                                            break;
                                                        case 'enprogreso':
                                                            $estado_clase = 'badge-info';
                                                            break;
                                                        case 'completada':
                                                            $estado_clase = 'badge-success';
                                                            break;
                                                        case 'verificada':
                                                            $estado_clase = 'badge-secondary';
                                                            break;
                                                        default:
                                                            $estado_clase = 'badge-light';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y', strtotime($limpieza['fecha'])); ?></td>
                                                        <td><?= date('H:i', strtotime($limpieza['hora'])); ?></td>
                                                        <td><?= htmlspecialchars($limpieza['nombre_usuario']); ?></td>
                                                        <td>
                                                            <span class="badge <?= $estado_clase; ?>">
                                                                <?= ucfirst(str_replace('enprogreso', 'en progreso', $limpieza['estado'])); ?>
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
            </div>
        </div>
    </div>
</section>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" role="dialog" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCambiarEstadoLabel">Cambiar Estado de Habitación</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formCambiarEstado">
                    <input type="hidden" id="id_habitacion" value="<?= $habitacion['id_habitacion']; ?>">
                    <input type="hidden" id="estado_actual" value="<?= $habitacion['estado']; ?>">

                    <div class="form-group">
                        <label for="nuevo_estado">Nuevo Estado:</label>
                        <select class="form-control" id="nuevo_estado" name="nuevo_estado">
                            <option value="disponible" <?= $habitacion['estado'] == 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                            <option value="ocupada" <?= $habitacion['estado'] == 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
                            <option value="mantenimiento" <?= $habitacion['estado'] == 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                            <option value="limpieza" <?= $habitacion['estado'] == 'limpieza' ? 'selected' : ''; ?>>Limpieza</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarEstado">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>


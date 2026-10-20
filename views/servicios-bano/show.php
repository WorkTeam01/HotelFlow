<?php
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id']; // Obtener el ID del usuario loguea

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de servicio de baño no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Verificar si el usuario tiene permiso para crear servicios de baño
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'servicios_bano')) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

$controller = new ServicioBanoController();
$datos = $controller->obtener($id);

if (!$datos) {
    $_SESSION['mensaje'] = 'Servicio de baño no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['servicios-bano/cambiar-estado-servicios-bano'];
include_once '../layouts/header.php';

// Preparar la clase y texto según el estado
$clase_estado = '';
$texto_estado = '';

$servicio = $datos['servicio'];
$banos_disponibles = $datos['banos_disponibles'];

// Vocabulario de estado del módulo: `estado` es un enum de texto
// (iniciado | finalizado | cancelado), no un booleano.
switch ($servicio['estado']) {
    case 'cancelado':
        $clase_estado = 'badge-danger';
        $texto_estado = 'Cancelado';
        $card_estado = 'card-danger';
        break;
    case 'finalizado':
        $clase_estado = 'badge-info';
        $texto_estado = 'Finalizado';
        $card_estado = 'card-primary';
        break;
    case 'iniciado':
    default:
        $clase_estado = 'badge-success';
        $texto_estado = 'Iniciado';
        $card_estado = 'card-primary';
        break;
}
$esCancelado = $servicio['estado'] === 'cancelado';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Servicio de Baño</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano"><i class="fas fa-bath"></i> Servicios de Baños</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
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
                <!-- Tarjeta de información principal -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Información Principal</h3>
                    </div>
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">Servicio #<?= $servicio['idservicio']; ?></h3>
                        <p class="text-muted text-center">
                            <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Baño</b> <span class="float-right"><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Precio del Baño</b> <span class="float-right"><?= number_format((float)($servicio['precio_bano'] ?? 0), 2); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Tipo de Cliente</b> <span class="float-right"><?= htmlspecialchars($servicio['tipo_cliente'] ?? ''); ?></span>
                            </li>
                            <?php if ($servicio['tipo_cliente'] == 'Huesped' && !empty($servicio['nombre_cliente'])): ?>
                                <li class="list-group-item">
                                    <b>Cliente</b> <span class="float-right"><?= htmlspecialchars($servicio['nombre_cliente']); ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item">
                                <b>Cobro</b>
                                <span class="float-right">
                                    <?php if ($servicio['modalidad_cobro'] === 'folio'): ?>
                                        Cargado al folio · Hab. <?= htmlspecialchars($servicio['numero_habitacion'] ?? 'N/A'); ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($servicio['metodopago'] ?? 'N/A'); ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Fecha</b> <span class="float-right"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <?php if (!$esCancelado): ?>
                                <a href="<?= $URL; ?>views/servicios-bano/update.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-warning mb-3">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            <?php endif; ?>
                            <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-secondary mb-3">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <?php $accionesEstado = $controller->accionesEstado($servicio); ?>
                <?php if (!empty($accionesEstado)): ?>
                    <!-- Acciones adicionales -->
                    <div class="card <?= $card_estado; ?>">
                        <div class="card-header">
                            <h3 class="card-title">Cambiar Estado del Servicio</h3>
                        </div>
                        <div class="card-body">
                            <?php if (in_array('cancelado', $accionesEstado, true)): ?>
                                <button type="button" class="btn btn-danger btn-block cambiar-estado-servicio"
                                    data-id="<?= $servicio['idservicio']; ?>"
                                    data-estado="cancelado">
                                    <i class="fas fa-times-circle"></i> Cancelar Servicio
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles financieros -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Información Financiera</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total</span>
                                        <span class="info-box-number"><?= number_format($servicio['total'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-hand-holding-usd"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Pago Recibido</span>
                                        <span class="info-box-number"><?= number_format($servicio['pagorecibido'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-coins"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Cambio</span>
                                        <span class="info-box-number"><?= number_format($servicio['cambio'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($esCancelado): ?>
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Cancelación</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Motivo:</strong> <?= htmlspecialchars($servicio['motivo_cancelacion'] ?? 'N/A'); ?></p>
                            <p><strong>Cancelado por:</strong> <?= htmlspecialchars($servicio['nombre_usuario_cancelacion'] ?? 'N/A'); ?></p>
                            <p class="mb-0"><strong>Fecha:</strong> <?= !empty($servicio['fecha_cancelacion']) ? date('d/m/Y H:i', strtotime($servicio['fecha_cancelacion'])) : 'N/A'; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta de registro -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información de Registro</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($servicio['observaciones'])): ?>
                            <p><strong>Observaciones:</strong> <?= htmlspecialchars($servicio['observaciones']); ?></p>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Registrado por:</label>
                                    <p class="text-muted">
                                        <?= htmlspecialchars($servicio['nombre_usuario'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha y Hora de Registro:</label>
                                    <p class="text-muted">
                                        <?= date('d/m/Y H:i:s', strtotime($servicio['fecha'])); ?>
                                    </p>
                                </div>
                            </div>
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

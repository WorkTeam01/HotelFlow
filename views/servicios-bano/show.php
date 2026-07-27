<?php
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? 0; // Obtener el ID del usuario loguea

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

if (!($auth->puedeAccederModulo($idusuario, 'servicios_bano'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Instanciar el controlador y obtener los datos del servicio
$controller = new ServicioBanoController();
$datos = $controller->editar($id);

// Verificar si el servicio existe
if (!$datos) {
    $_SESSION['mensaje'] = 'Servicio de baño no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Preparar la clase y texto según el estado
$clase_estado = '';
$texto_estado = '';

$servicio = $datos['servicio'];
$banos_disponibles = $datos['banos_disponibles'];
$clientes = $datos['clientes'];

// Determinar estado
if ($servicio['estado'] == 1) {
    $clase_estado = 'badge-success';
    $texto_estado = 'Activo';
} else {
    $clase_estado = 'badge-danger';
    $texto_estado = 'Inactivo';
}
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
                                <b>Precio del Baño</b> <span class="float-right"><?= number_format($servicio['precio_bano'], 2 ?? 0); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Tipo de Cliente</b> <span class="float-right"><?= htmlspecialchars($servicio['tipo_cliente']); ?></span>
                            </li>
                            <?php if ($servicio['tipo_cliente'] == 'Huesped' && !empty($servicio['nombre_cliente'])): ?>
                                <li class="list-group-item">
                                    <b>Cliente</b> <span class="float-right"><?= htmlspecialchars($servicio['nombre_cliente']); ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item">
                                <b>Método de Pago</b> <span class="float-right"><?= htmlspecialchars($servicio['metodopago'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Fecha</b> <span class="float-right"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/servicios-bano/update.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-warning mb-3">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-secondary mb-3">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Acciones adicionales -->
                <div class="card card-<?= $servicio['estado'] == 1 ? 'danger' : 'success'; ?>">
                    <div class="card-header">
                        <h3 class="card-title">Cambiar Estado del Servicio</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <?php if ($servicio['estado'] == 1): ?>
                                    <button type="button" class="btn btn-danger btn-block btn-cambiar-estado"
                                        data-id="<?= $servicio['idservicio']; ?>"
                                        data-estado="<?= $servicio['estado']; ?>"
                                        data-accion="0">
                                        <i class="fas fa-times-circle"></i> Desactivar Servicio
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success btn-block btn-cambiar-estado"
                                        data-id="<?= $servicio['idservicio']; ?>"
                                        data-estado="<?= $servicio['estado']; ?>"
                                        data-accion="1">
                                        <i class="fas fa-check-circle"></i> Activar Servicio
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
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

                <!-- Tarjeta de registro -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información de Registro</h3>
                    </div>
                    <div class="card-body">
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

<script src="<?= $URL; ?>public/js/modules/servicios-bano/show-servicios-bano.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
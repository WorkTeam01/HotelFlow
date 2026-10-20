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
$skip_datatables = true;
$skip_chartjs = true;
$module_styles = ['servicios-bano/servicios-bano'];
$module_scripts = ['servicios-bano/cambiar-estado-servicios-bano', 'servicios-bano/show-servicios-bano'];
include_once '../layouts/header.php';

$servicio = $datos['servicio'];
$folio = $datos['folio'];
$ui = $servicio['estado_ui'];
$esCancelado = $servicio['estado'] === 'cancelado';
$acciones = $controller->accionesEstado($servicio);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Servicio de Baño #<?= (int)$servicio['idservicio']; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano"><i class="fas fa-bath" aria-hidden="true"></i> Servicios de Baños</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content servicios-bano-pagina">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-<?= $ui['clase']; ?> card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center"><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></h3>
                        <p class="text-center mb-3">
                            <span class="badge <?= $ui['badge']; ?>">
                                <i class="fas fa-<?= $ui['icono']; ?>" aria-hidden="true"></i> <?= $ui['label']; ?>
                            </span>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Cliente</b> <span class="float-right"><?= htmlspecialchars($servicio['cliente_label']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Cobro</b> <span class="float-right"><?= htmlspecialchars($servicio['cobro_label']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Total</b> <span class="float-right">Bs. <?= number_format((float)$servicio['total'], 2); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Inicio</b> <span class="float-right"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></span>
                            </li>
                            <?php if (!empty($servicio['fecha_fin'])): ?>
                                <li class="list-group-item">
                                    <b>Duración</b> <span class="float-right"><?= (int)($servicio['duracion_minutos'] ?? 0); ?> min</span>
                                </li>
                            <?php elseif ($servicio['minutos_en_uso'] !== null): ?>
                                <li class="list-group-item">
                                    <b>En uso hace</b> <span class="float-right"><?= (int)$servicio['minutos_en_uso']; ?> min</span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <?php if (!empty($acciones)): ?>
                            <?php $variante = 'detalle';
                            require __DIR__ . '/partials/acciones-estado.php'; ?>
                        <?php endif; ?>
                        <?php if (ServicioBanoController::esEditable($servicio)): ?>
                            <a href="<?= $URL; ?>views/servicios-bano/update.php?id=<?= (int)$servicio['idservicio']; ?>" class="btn btn-touch btn-warning btn-block">
                                <i class="fas fa-edit" aria-hidden="true"></i> Editar observaciones
                            </a>
                        <?php endif; ?>
                        <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-touch btn-secondary btn-block">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-outline-tabs card-outline card-info">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-detalle" data-toggle="pill" href="#detalle" role="tab" aria-controls="detalle" aria-selected="true">
                                    <i class="fas fa-info-circle" aria-hidden="true"></i> Detalle
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-folio" data-toggle="pill" href="#folio" role="tab" aria-controls="folio" aria-selected="false">
                                    <i class="fas fa-receipt" aria-hidden="true"></i> Folio
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="detalle" role="tabpanel" aria-labelledby="tab-detalle">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box bg-light">
                                            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total</span>
                                                <span class="info-box-number"><?= number_format((float)$servicio['total'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-light">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-hand-holding-usd" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Pago recibido</span>
                                                <span class="info-box-number"><?= number_format((float)$servicio['pagorecibido'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-light">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-coins" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Cambio</span>
                                                <span class="info-box-number"><?= number_format((float)$servicio['cambio'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($servicio['observaciones'])): ?>
                                    <p><strong>Observaciones:</strong> <?= htmlspecialchars($servicio['observaciones']); ?></p>
                                <?php endif; ?>
                                <p class="mb-0"><strong>Registrado por:</strong> <?= htmlspecialchars($servicio['nombre_usuario'] ?? 'N/A'); ?></p>

                                <?php if ($esCancelado): ?>
                                    <div class="callout callout-danger mt-3 mb-0">
                                        <h5>Cancelación</h5>
                                        <p class="mb-1"><strong>Motivo:</strong> <?= htmlspecialchars($servicio['motivo_cancelacion'] ?? 'N/A'); ?></p>
                                        <p class="mb-1"><strong>Cancelado por:</strong> <?= htmlspecialchars($servicio['nombre_usuario_cancelacion'] ?? 'N/A'); ?></p>
                                        <p class="mb-0"><strong>Fecha:</strong> <?= !empty($servicio['fecha_cancelacion']) ? date('d/m/Y H:i', strtotime($servicio['fecha_cancelacion'])) : 'N/A'; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="tab-pane fade" id="folio" role="tabpanel" aria-labelledby="tab-folio">
                                <?php require __DIR__ . '/partials/folio-servicio.php'; ?>
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
<?php
require_once __DIR__ . '/../../controllers/tarifas/TarifaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'tarifas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de tarifa no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos de la tarifa
$controller = new TarifaController();
$tarifa = $controller->editar($id);

// Verificar si la tarifa existe
if (!$tarifa) {
    $_SESSION['mensaje'] = 'Tarifa no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Definir scripts y estilos específicos para este módulo
$module_styles = ['tarifas/tarifas-styles'];
$module_scripts = ['tarifas/show-tarifas'];

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Tarifa</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tarifas"><i class="fas fa-list"></i> Tarifas</a></li>
                    <li class="breadcrumb-item active">Detalle de Tarifa</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna de información principal -->
            <div class="col-md-4">
                <!-- Tarjeta de datos básicos -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <div class="profile-user-img img-fluid img-circle bg-info d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-money-bill fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($tarifa['tipo_habitacion']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= ucfirst(htmlspecialchars($tarifa['tipo_estancia'])); ?> -
                            <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días'); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-tag mr-1"></i> Precio</b>
                                <span class="float-right">
                                    Bs. <?= number_format($tarifa['precio'], 2); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-clock mr-1"></i> Duración</b>
                                <span class="float-right">
                                    <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($tarifa['estado'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/tarifas/update.php?id=<?= $tarifa['idtarifa']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/tarifas/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tools"></i> Acciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <button onclick="cambiarEstado(<?= $tarifa['idtarifa']; ?>, <?= $tarifa['estado']; ?>)" class="nav-link">
                                    <?php if ($tarifa['estado'] == 1): ?>
                                        <i class="fas fa-toggle-off text-danger"></i> Desactivar Tarifa
                                    <?php else: ?>
                                        <i class="fas fa-toggle-on text-success"></i> Activar Tarifa
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Columna de información detallada -->
            <div class="col-md-8">
                <!-- Tarjeta de información detallada -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información de la Tarifa</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-bed"></i> Tipo de Habitación
                                </h5>

                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info"><i class="fas fa-bed"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Nombre del Tipo</span>
                                        <span class="info-box-number">
                                            <?= htmlspecialchars($tarifa['tipo_habitacion']); ?>
                                        </span>
                                        <span class="info-box-text text-sm">
                                            <a href="<?= $URL; ?>views/tipohabitacion/show.php?id=<?= $tarifa['id_tipo']; ?>" class="btn btn-sm btn-outline-info mt-2">
                                                <i class="fas fa-eye"></i> Ver detalle del tipo
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-info-circle"></i> Detalles de la Tarifa
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Tipo de Estancia</span>
                                        <span class="info-box-number">
                                            <?= ucfirst(htmlspecialchars($tarifa['tipo_estancia'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success"><i class="fas fa-hourglass-half"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Duración</span>
                                        <span class="info-box-number">
                                            <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-tag"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Precio</span>
                                        <span class="info-box-number">
                                            Bs. <?= number_format($tarifa['precio'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-<?= $tarifa['estado'] == 1 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?= $tarifa['estado'] == 1 ? 'check' : 'times'; ?>"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Estado</span>
                                        <span class="info-box-number">
                                            <?= $tarifa['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-align-left"></i> Descripción
                                </h5>

                                <div class="callout callout-info">
                                    <div class="text-wrap">
                                        <?php if (!empty($tarifa['descripcion'])): ?>
                                            <p><?= nl2br(htmlspecialchars($tarifa['descripcion'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">No hay descripción disponible para esta tarifa.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-clock"></i> Información del Sistema
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-secondary"><i class="fas fa-calendar-plus"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Fecha de Creación</span>
                                        <span class="info-box-number">
                                            <?= date('d/m/Y H:i', strtotime($tarifa['fechacreacion'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-secondary"><i class="fas fa-edit"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Última Actualización</span>
                                        <span class="info-box-number">
                                            <?= !empty($tarifa['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($tarifa['fechaactualizacion'])) : 'Sin actualizaciones'; ?>
                                        </span>
                                    </div>
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

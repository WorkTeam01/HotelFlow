<?php
require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'equipajes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de equipaje no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

// Datos resueltos ANTES del header (todas las validaciones/redirects primero)
$controller = new AlmacenamientoEquipajeController();
$equipaje = $controller->mostrar($id);

if (!$equipaje) {
    $_SESSION['mensaje'] = 'Equipaje no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

$estado_ui = $equipaje['estado_ui'];
$tiempo = $equipaje['tiempo_almacenado'];
$timeline = $equipaje['timeline'];
$acciones_estado = AlmacenamientoEquipajeController::accionesEstado($equipaje['estado']);

// Assets del módulo declarados ANTES de incluir header.php
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];
$module_scripts = ['almacenamiento-equipaje/cambiar-estado-equipajes', 'almacenamiento-equipaje/show-equipaje'];
$skip_datatables = true;
$skip_select2 = true;
$skip_chartjs = true;

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase" aria-hidden="true"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Detalle de Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content equipaje-pagina">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna de perfil del equipaje -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center mb-4">
                            <div class="d-inline-block position-relative">
                                <span class="profile-user-img img-circle bg-light d-flex align-items-center justify-content-center equipaje-avatar">
                                    <i class="fas fa-suitcase text-primary" aria-hidden="true"></i>
                                </span>
                                <span class="badge <?= $estado_ui['badge']; ?> equipaje-avatar__estado">
                                    <i class="fas fa-<?= $estado_ui['icono']; ?>" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($equipaje['codigo_ticket']); ?>
                        </h3>

                        <p class="text-muted text-center mb-3">
                            <span class="badge <?= $estado_ui['badge']; ?>">
                                <i class="fas fa-<?= $estado_ui['icono']; ?>" aria-hidden="true"></i> <?= $estado_ui['label']; ?>
                            </span>
                        </p>

                        <ul class="list-group list-group-unbordered mb-4">
                            <li class="list-group-item">
                                <b><i class="fas fa-user mr-2" aria-hidden="true"></i>Cliente</b>
                                <span class="float-right"><?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-suitcase mr-2" aria-hidden="true"></i>Tipo</b>
                                <span class="float-right"><?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-box mr-2" aria-hidden="true"></i>Piezas</b>
                                <span class="float-right"><?= (int)$equipaje['cantidad_piezas']; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-money-bill mr-2" aria-hidden="true"></i>Monto</b>
                                <span class="float-right">Bs. <?= number_format($equipaje['monto'], 2); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-credit-card mr-2" aria-hidden="true"></i>Método</b>
                                <span class="float-right"><?= htmlspecialchars($equipaje['metodopago'] ?? 'Efectivo'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt mr-2" aria-hidden="true"></i>Entrada</b>
                                <span class="float-right"><?= date('d/m/Y H:i', strtotime($equipaje['fechaentrada'])); ?></span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between flex-wrap">
                            <?php if (AlmacenamientoEquipajeController::esEditable($equipaje['estado'])): ?>
                                <a href="<?= $URL; ?>views/almacenamiento-equipaje/update.php?id=<?= $equipaje['idalmacen']; ?>"
                                    class="btn btn-warning mb-1">
                                    <i class="fas fa-edit" aria-hidden="true"></i> Editar
                                </a>
                            <?php endif; ?>
                            <a href="<?= $URL; ?>views/almacenamiento-equipaje/recibo.php?id=<?= $equipaje['idalmacen']; ?>"
                                class="btn btn-info mb-1" target="_blank">
                                <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                            </a>
                            <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-secondary mb-1">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones adicionales -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2" aria-hidden="true"></i>Acciones</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (!$acciones_estado): ?>
                                <span class="list-group-item text-muted">
                                    <i class="fas fa-info-circle mr-2" aria-hidden="true"></i>
                                    Equipaje retirado: sin cambios de estado disponibles.
                                </span>
                            <?php endif; ?>
                            <?php foreach ($acciones_estado as $destino => $accion): ?>
                                <button type="button"
                                    class="list-group-item list-group-item-action cambiar-estado"
                                    data-id="<?= $equipaje['idalmacen']; ?>"
                                    data-estado="<?= $destino; ?>">
                                    <i class="fas fa-<?= $accion['icono']; ?> text-<?= $accion['clase']; ?> mr-2" aria-hidden="true"></i>
                                    <?= $accion['accion_label']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna de información detallada -->
            <div class="col-md-8">
                <div class="card card-info card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-equipaje" data-toggle="pill" href="#equipaje" role="tab" aria-controls="equipaje" aria-selected="true">
                                    <i class="fas fa-suitcase mr-1" aria-hidden="true"></i> Equipaje
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-folio" data-toggle="pill" href="#folio" role="tab" aria-controls="folio" aria-selected="false">
                                    <i class="fas fa-receipt mr-1" aria-hidden="true"></i> Folio
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-sistema" data-toggle="pill" href="#sistema" role="tab" aria-controls="sistema" aria-selected="false">
                                    <i class="fas fa-cogs mr-1" aria-hidden="true"></i> Sistema
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Equipaje -->
                            <div class="tab-pane fade show active" id="equipaje" role="tabpanel" aria-labelledby="tab-equipaje">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box bg-light mb-0">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-suitcase" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Tipo de Equipaje</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'No especificado'); ?>
                                                </span>
                                                <span class="progress-description">
                                                    <?= htmlspecialchars($equipaje['cantidad_piezas']); ?> pieza(s)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box bg-light mb-0">
                                            <span class="info-box-icon bg-success"><i class="fas fa-money-bill" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Monto Pagado</span>
                                                <span class="info-box-number">
                                                    Bs. <?= number_format($equipaje['monto'], 2); ?>
                                                </span>
                                                <span class="progress-description">
                                                    Tarifa para <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'equipaje'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="info-box bg-light mb-0">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Fecha de Entrada</span>
                                                <span class="info-box-number">
                                                    <?= date('d/m/Y H:i:s', strtotime($equipaje['fechaentrada'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box bg-light mb-0">
                                            <span class="info-box-icon <?= !empty($equipaje['fechasalida']) ? 'bg-success' : 'bg-warning'; ?>">
                                                <i class="fas <?= !empty($equipaje['fechasalida']) ? 'fa-calendar-minus' : 'fa-hourglass-half'; ?>" aria-hidden="true"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">
                                                    <?= !empty($equipaje['fechasalida']) ? 'Fecha de Salida' : 'Tiempo Almacenado'; ?>
                                                </span>
                                                <span class="info-box-number">
                                                    <?php if (!empty($equipaje['fechasalida'])): ?>
                                                        <?= date('d/m/Y H:i:s', strtotime($equipaje['fechasalida'])); ?>
                                                    <?php else: ?>
                                                        <?= $tiempo['texto']; ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="info-box bg-light mb-0">
                                            <span class="info-box-icon bg-secondary"><i class="fas fa-user" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Cliente</span>
                                                <span class="info-box-number">
                                                    <?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'N/A'); ?>
                                                    —
                                                    <?= htmlspecialchars($equipaje['tipodoc_cliente'] ?? ''); ?>:
                                                    <?= htmlspecialchars($equipaje['numdoc_cliente'] ?? ''); ?>
                                                    <?php if (!empty($equipaje['telefono_cliente'])): ?>
                                                        · <a href="tel:<?= htmlspecialchars($equipaje['telefono_cliente']); ?>">
                                                            <?= htmlspecialchars($equipaje['telefono_cliente']); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="callout callout-light mt-3 mb-0">
                                    <h5 class="mb-1"><i class="fas fa-align-left" aria-hidden="true"></i> Descripción</h5>
                                    <?php if (!empty($equipaje['descripcion'])): ?>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($equipaje['descripcion'])); ?></p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No se proporcionó descripción.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tab Folio -->
                            <div class="tab-pane fade" id="folio" role="tabpanel" aria-labelledby="tab-folio">
                                <?php include __DIR__ . '/partials/folio-equipaje.php'; ?>
                            </div>

                            <!-- Tab Sistema (historial) -->
                            <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="tab-sistema">
                                <?php include __DIR__ . '/partials/timeline-equipaje.php'; ?>

                                <div class="mt-3">
                                    <h6><i class="fas fa-user-shield mr-1" aria-hidden="true"></i> Registro</h6>
                                    <dl class="equipaje-resumen__lista mb-0">
                                        <dt>Usuario que Registró</dt>
                                        <dd><?= htmlspecialchars($equipaje['nombre_usuario'] ?? 'Usuario no disponible'); ?></dd>
                                        <dt>Fecha de Registro</dt>
                                        <dd>
                                            <?= isset($equipaje['fechacreacion']) ? date('d/m/Y H:i:s', strtotime($equipaje['fechacreacion'])) : date('d/m/Y H:i:s', strtotime($equipaje['fechaentrada'])); ?>
                                        </dd>
                                        <?php if (isset($equipaje['fechaactualizacion'])): ?>
                                            <dt>Última Actualización</dt>
                                            <dd class="mb-0"><?= date('d/m/Y H:i:s', strtotime($equipaje['fechaactualizacion'])); ?></dd>
                                        <?php endif; ?>
                                    </dl>
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

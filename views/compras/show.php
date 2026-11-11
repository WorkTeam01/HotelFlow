<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de compra no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos de la compra
$controller = new CompraController();
$compra = $controller->ver($id);

// Verificar si la compra existe
if (!$compra) {
    $_SESSION['mensaje'] = 'Compra no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

if (!$authService->esAdministrador($idusuario) && $compra['idusuario'] != $idusuario) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver esta compra.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['compras/compras'];
$module_scripts = ['compras/show-compras'];
include_once '../layouts/header.php';

$estadoUi = $compra['estado_ui'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Compra <?= $compra['codigo']; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"> <i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active">Detalle de Compra</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content compras-pagina">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <!-- Tarjeta de información básica -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">
                            Compra <?= $compra['codigo']; ?>
                        </h3>

                        <p class="text-center">
                            <span class="badge <?= $estadoUi['badge']; ?>" role="status">
                                <i class="fas <?= $estadoUi['icono']; ?>" aria-hidden="true"></i>
                                <?= $estadoUi['label']; ?>
                            </span>
                        </p>
                        <?php if ($estadoUi['alerta'] !== '') : ?>
                            <p class="text-muted text-center small"><?= htmlspecialchars($estadoUi['alerta']); ?></p>
                        <?php endif; ?>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Fecha</b>
                                <span class="float-right">
                                    <?= date('d/m/Y', strtotime($compra['fechacompra'])); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Usuario</b>
                                <span class="float-right">
                                    <?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Total</b>
                                <span class="float-right">
                                    <?= number_format($compra['totalcompra'], 2); ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <?php if ($compra['estado'] == 'pendiente' && $authService->esAdministrador($idusuario)): ?>
                                <button class="btn btn-success btn-completar" data-id="<?= $compra['idcompra']; ?>">
                                    <i class="fas fa-check"></i> Completar
                                </button>
                                <button class="btn btn-danger btn-cancelar" data-id="<?= $compra['idcompra']; ?>">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            <?php endif; ?>
                            <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de observaciones -->
                <?php if (!empty($compra['observaciones'])): ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Observaciones</h3>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($compra['observaciones']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Productos Comprados</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">P. Unitario</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compra['detalles'] as $index => $detalle): ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_codigo'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= $detalle['cantidad']; ?></td>
                                            <td class="text-right"><?= number_format($detalle['preciocompra'], 2); ?></td>
                                            <td class="text-right"><?= number_format($detalle['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                        <td class="text-right"><strong><?= number_format($compra['totalcompra'], 2); ?></strong></td>
                                    </tr>
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
<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'productos')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de producto no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del producto
$controller = new ProductoController();
$producto = $controller->editar($id);

// Verificar si el producto existe
if (!$producto) {
    $_SESSION['mensaje'] = 'Producto no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Datos derivados del producto: el controlador resuelve el estado, el inventario,
// la rentabilidad y los movimientos antes de emitir HTML.
$estado_ui = ProductoController::estadoProducto($producto['estado']);
$inventario = ProductoController::calcularInventario($producto);
$rentabilidad = ProductoController::calcularRentabilidad($producto);
$movimientos = $controller->getUltimosMovimientos($producto['idproducto']);

// Incluir el encabezado DESPUÉS de todas las validaciones que puedan redirigir
$module_styles = ['productos/productos'];
$skip_datatables = true;
$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['productos/cambiar-estado-productos', 'productos/show-producto'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item active">Detalle de Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna izquierda - Imagen y datos básicos -->
            <div class="col-md-4">
                <!-- Tarjeta de imagen y datos básicos -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center position-relative mb-4">
                            <?php
                            $clase_img = 'img-fluid rounded prod-img-perfil';
                            $alt = 'Imagen del producto';
                            include __DIR__ . '/partials/imagen-producto.php';
                            ?>

                            <!-- Indicador de estado sobre la imagen -->
                            <span class="position-absolute badge prod-badge-estado <?= $estado_ui['badge']; ?>">
                                <i class="fas <?= $estado_ui['icono']; ?>"></i>
                                <?= $estado_ui['label']; ?>
                            </span>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($producto['nombre']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-4">
                            <li class="list-group-item">
                                <b><i class="fas fa-barcode mr-2"></i>Código</b>
                                <span class="float-right">
                                    <?= !empty($producto['codigo']) ? htmlspecialchars($producto['codigo']) : 'N/A'; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-tag mr-2"></i>Precio Venta</b>
                                <span class="float-right">
                                    <span class="badge badge-success">
                                        Bs <?= number_format($producto['precioventa'], 2); ?>
                                    </span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-shopping-basket mr-2"></i>Stock Actual</b>
                                <span class="float-right <?= $producto['stock'] < $producto['stock_minimo'] ? 'text-danger font-weight-bold' : ''; ?>">
                                    <?= $producto['stock']; ?> unidades
                                    <?php if ($producto['stock'] < $producto['stock_minimo']): ?>
                                        <i class="fas fa-exclamation-triangle ml-1" title="Stock bajo"></i>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                                <span class="float-right">
                                    <span class="badge <?= $estado_ui['badge']; ?>"><?= $estado_ui['label']; ?></span>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/productos/update.php?id=<?= $producto['idproducto']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/productos/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Acciones adicionales -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Acciones</h3>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-block <?= $estado_ui['accion_clase']; ?> cambiar-estado-producto" id="btnCambiarEstado"
                            data-id="<?= (int)$producto['idproducto']; ?>"
                            data-estado="<?= (int)$producto['estado']; ?>"
                            data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>">
                            <i class="fas <?= $estado_ui['accion_icono']; ?> mr-2"></i>
                            <?= $estado_ui['accion_label']; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna derecha - Información detallada en tabs -->
            <div class="col-md-8">
                <!-- Información detallada en pestañas -->
                <div class="card card-info card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-general" data-toggle="pill" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle mr-1"></i> Información General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-inventario" data-toggle="pill" href="#inventario" role="tab"
                                    aria-controls="inventario" aria-selected="false">
                                    <i class="fas fa-boxes mr-1"></i> Inventario
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-precios" data-toggle="pill" href="#precios" role="tab"
                                    aria-controls="precios" aria-selected="false">
                                    <i class="fas fa-money-bill-wave mr-1"></i> Precios
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="tab-general">
                                <?php include __DIR__ . '/partials/tab-general.php'; ?>
                            </div>

                            <!-- Tab Inventario -->
                            <div class="tab-pane fade" id="inventario" role="tabpanel" aria-labelledby="tab-inventario">
                                <?php include __DIR__ . '/partials/tab-inventario.php'; ?>
                            </div>

                            <!-- Tab Precios -->
                            <div class="tab-pane fade" id="precios" role="tabpanel" aria-labelledby="tab-precios">
                                <?php include __DIR__ . '/partials/tab-precios.php'; ?>
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
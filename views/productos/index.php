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
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$module_styles = ['productos/productos'];
$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['productos/cambiar-estado-productos', 'productos/index-productos'];
include_once '../layouts/header.php';

$controller = new ProductoController();
$productos = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Productos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Productos</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Productos</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/productos/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Producto
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaProductos" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Imagen</th>
                                        <th>Precio Venta</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($productos as $producto) :
                                        $estado_actual = $producto['estado'];
                                        $estado_ui = ProductoController::estadoProducto($estado_actual);
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($producto['codigo'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['categoria_nombre']); ?></td>
                                            <td class="text-center">
                                                <?php
                                                $clase_img = 'img-thumbnail prod-img-miniatura';
                                                $alt = 'Imagen';
                                                include __DIR__ . '/partials/imagen-producto.php';
                                                ?>
                                            </td>
                                            <td class="text-right"><?= number_format($producto['precioventa'], 2); ?></td>
                                            <td class="text-center <?= $producto['stock'] < $producto['stock_minimo'] ? 'text-danger font-weight-bold' : '' ?>">
                                                <?= $producto['stock']; ?>
                                                <?php if ($producto['stock'] < $producto['stock_minimo']): ?>
                                                    <i class="fas fa-exclamation-triangle ml-1" title="Stock bajo"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $estado_ui['badge']; ?>"><?= $estado_ui['label']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info btn-sm btn-touch">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/productos/update.php?id=<?= $producto['idproducto']; ?>" class="btn btn-warning btn-sm btn-touch">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $estado_ui['accion_clase']; ?> btn-sm btn-touch cambiar-estado-producto"
                                                        data-id="<?= $producto['idproducto']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>">
                                                        <i class="fas <?= $estado_ui['accion_icono']; ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

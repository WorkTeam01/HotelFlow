<?php
// Archivo: views/productos/buscar_codigo.php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->puedeAccederModulo($idusuario, 'productos'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

$controller = new ProductoController();

// Variable para almacenar el producto encontrado (si existe)
$producto = null;

// Verificar si se escaneó un código
if (isset($_POST['codigo']) && !empty($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);
    $producto = $controller->buscarPorCodigo($codigo);
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Búsqueda por Código</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos">Productos</a></li>
                    <li class="breadcrumb-item active">Buscar por Código</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header card-outline">
                        <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>Escáner de Códigos de Productos</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <form method="post" action="" id="form-escaner">
                                    <div class="form-group">
                                        <label for="codigo"><i class="fas fa-barcode"></i> Código del Producto:</label>
                                        <div class="input-group input-group-lg">
                                            <input type="text" class="form-control" id="codigo" name="codigo"
                                                placeholder="Coloque el cursor aquí y escanee el código..." autofocus
                                                value="<?= isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : '' ?>">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Coloque el cursor en el campo y escanee directamente con el lector de códigos.
                                        </small>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if (isset($_POST['codigo']) && empty($producto)): ?>
                            <div class="alert alert-warning mt-4">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Producto no encontrado</h5>
                                <p>No se encontró ningún producto con el código: <strong><?= htmlspecialchars($_POST['codigo']) ?></strong></p>
                                <a href="<?= $URL; ?>views/productos/create.php?codigo=<?= urlencode($_POST['codigo']) ?>" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus"></i> Registrar Nuevo Producto
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($producto): ?>
                    <!-- Información del producto encontrado -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>Producto Encontrado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="<?= $URL; ?>public/uploads/productos/<?= $producto['imagen']; ?>" alt="Imagen" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                    <?php else: ?>
                                        <img src="<?= $URL; ?>public/uploads/productos/producto_default.png" alt="Imagen por defecto" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-9">
                                    <h4><?= htmlspecialchars($producto['nombre']); ?></h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Código:</strong> <?= htmlspecialchars($producto['codigo']); ?></p>
                                            <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria_nombre']); ?></p>
                                            <p><strong>Precio de Venta:</strong> Bs <?= number_format($producto['precioventa'], 2); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Stock:</strong>
                                                <span class="<?= $producto['stock'] < $producto['stock_minimo'] ? 'text-danger font-weight-bold' : '' ?>">
                                                    <?= $producto['stock']; ?> unidades
                                                    <?php if ($producto['stock'] < $producto['stock_minimo']): ?>
                                                        <i class="fas fa-exclamation-triangle" title="Stock bajo"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </p>
                                            <p><strong>Stock Mínimo:</strong> <?= $producto['stock_minimo']; ?> unidades</p>
                                            <p><strong>Estado:</strong>
                                                <?php if ($producto['estado'] == 1): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (!empty($producto['descripcion'])): ?>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <p><strong>Descripción:</strong></p>
                                                <p><?= nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </a>
                                            <a href="<?= $URL; ?>views/productos/update.php?id=<?= $producto['idproducto']; ?>" class="btn btn-warning">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="<?= $URL; ?>views/ventas/create.php?producto=<?= $producto['idproducto']; ?>" class="btn btn-success">
                                                <i class="fas fa-shopping-cart"></i> Vender
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Historial de búsquedas (opcional) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Historial de Búsquedas</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="historial-busquedas">
                            <!-- El historial se cargará aquí con JavaScript -->
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary btn-sm" id="limpiar-historial">
                            <i class="fas fa-trash"></i> Limpiar Historial
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    <?php if (isset($_POST['codigo']) && !empty($_POST['codigo'])) : ?>
        const busquedaActual = {
            codigo: "<?= htmlspecialchars($_POST['codigo'], ENT_QUOTES); ?>",
            encontrado: <?= $producto ? 'true' : 'false'; ?>,
            nombre: <?= $producto ? '"' . htmlspecialchars($producto['nombre'], ENT_QUOTES) . '"' : 'null'; ?>
        };
    <?php else : ?>
        const busquedaActual = null;
    <?php endif; ?>
</script>
<script src="<?= $URL; ?>public/js/modules/productos/buscar-codigo.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
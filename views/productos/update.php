<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../controllers/categoria/CategoriaController.php';
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

// Obtener categorías desde el controlador
$categoriaController = new CategoriaController();
$categorias = $categoriaController->index();

// Verificar si el producto existe
if (!$producto) {
    $_SESSION['mensaje'] = 'Producto no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Estado del producto resuelto por el controlador (fuente única de badge/etiqueta)
$estado_ui = ProductoController::estadoProducto($producto['estado']);
$estados = ProductoController::estadosProducto();
$umbrales = ProductoController::umbralesMargen();

// Incluir el encabezado DESPUÉS de todas las validaciones que puedan redirigir
$module_styles = ['productos/productos'];
$skip_datatables = true;
$skip_chartjs = true;
$module_scripts = ['productos/producto-form', 'productos/cambiar-estado-productos', 'productos/update-productos'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Editar Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>"><i class="fas fa-box"></i> Ver Producto</a></li>
                    <li class="breadcrumb-item active">Editar Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna del formulario (8/12) -->
            <div class="col-md-8">
                <form action="<?= $URL; ?>controllers/productos/actualizar_producto.php" method="POST" enctype="multipart/form-data" id="formEditarProducto"
                    data-margen-alerta="<?= $umbrales['alerta']; ?>" data-margen-bueno="<?= $umbrales['bueno']; ?>">
                    <input type="hidden" name="idproducto" value="<?= $producto['idproducto']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                    <!-- Sección de Información Básica -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Información Básica</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Categoría -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcategoria">Categoría <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="idcategoria" name="idcategoria" required>
                                            <option value="">Seleccione una categoría</option>
                                            <?php
                                            foreach ($categorias as $categoria) :
                                                if ($categoria['estado'] == 1) : // Solo mostrar categorías activas
                                            ?>
                                                    <option value="<?= $categoria['idcategoria']; ?>"
                                                        <?= $producto['idcategoria'] == $categoria['idcategoria'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($categoria['nombre']); ?>
                                                    </option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                        <small class="form-text text-muted">Seleccione la categoría a la que pertenece el producto</small>
                                    </div>
                                </div>

                                <!-- Código -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="codigo">Código</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="codigo" name="codigo"
                                                placeholder="Ingrese el código del producto" value="<?= htmlspecialchars($producto['codigo'] ?? ''); ?>">
                                        </div>
                                        <small class="form-text text-muted">Opcional - Código único del producto (SKU, código de barras, etc.)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-12">
                                    <div class="form-group mb-md-0">
                                        <label for="nombre">Nombre del Producto <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                placeholder="Ingrese el nombre del producto"
                                                value="<?= htmlspecialchars($producto['nombre']); ?>" required>
                                        </div>
                                        <small class="form-text text-muted mb-0">Nombre completo y descriptivo del producto</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <!-- Descripción -->
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="descripcion">Descripción</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            placeholder="Ingrese una descripción detallada del producto"><?= htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted mb-0">Características, usos, dimensiones u otra información relevante</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Precios -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Información de Precios</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Precio de Compra -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="preciocompra">Precio de Compra <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="preciocompra" name="preciocompra"
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($producto['preciocompra'], 2, '.', ''); ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se adquiere el producto (costo)</small>
                                    </div>
                                </div>

                                <!-- Precio de Venta -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="precioventa">Precio de Venta <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="precioventa" name="precioventa"
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($producto['precioventa'], 2, '.', ''); ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se vende el producto</small>
                                        <div id="precio-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Inventario -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-warehouse mr-2"></i>Información de Inventario</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Stock Actual -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock">Stock Actual <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock" name="stock"
                                                min="0" value="<?= $producto['stock']; ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Cantidad actual disponible del producto</small>
                                    </div>
                                </div>

                                <!-- Stock Mínimo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock_minimo">Stock Mínimo</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-level-down-alt"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                                                min="0" value="<?= $producto['stock_minimo']; ?>">
                                        </div>
                                        <small class="form-text text-muted">Cantidad mínima para generar alertas de reposición</small>
                                    </div>
                                </div>

                                <!-- Stock Máximo -->
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="stock_maximo">Stock Máximo</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-level-up-alt"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock_maximo" name="stock_maximo"
                                                min="0" value="<?= $producto['stock_maximo'] ?? ''; ?>">
                                        </div>
                                        <small class="form-text text-muted">Cantidad máxima recomendada (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            <div id="stock-validation-feedback"></div>
                        </div>
                    </div>

                    <!-- Sección de Imagen -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-image mr-2"></i>Imagen y Estado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-7">
                                    <div class="form-group mb-0">
                                        <label for="imagen">Nueva Imagen del Producto</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                            </div>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="imagen" name="imagen"
                                                    accept="image/*">
                                                <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                        <small class="form-text text-muted mb-0">Deje vacío para mantener la imagen actual</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <?php foreach ($estados as $clave => $estado) : ?>
                                                <option value="<?= (int) $estado['valor']; ?>"
                                                    <?= (int) $producto['estado'] === (int) $estado['valor'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($estado['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted mb-0">Un producto inactivo no aparecerá en las ventas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-save mr-2"></i> Actualizar Producto
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-eye mr-2"></i> Ver Detalles
                                    </a>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/productos" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Columna lateral (4/12) -->
            <div class="col-md-4">
                <!-- Resumen del producto: datos de referencia mientras se edita -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Resumen del Producto</h3>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        $clase_img = 'img-thumbnail prod-img-sm';
                        $alt = 'Imagen actual del producto';
                        include __DIR__ . '/partials/imagen-producto.php';
                        ?>

                        <div id="preview-container" class="mt-3" style="display: none;">
                            <div class="small text-muted mb-1">Nueva imagen seleccionada</div>
                            <img id="preview-image" src="#" alt="Vista previa de la nueva imagen"
                                class="img-thumbnail prod-img-sm">
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-hashtag text-primary mr-2"></i>ID de Producto</span>
                            <span class="badge badge-primary"><?= $producto['idproducto']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-tag text-info mr-2"></i>Categoría actual</span>
                            <span class="badge badge-info"><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-barcode text-secondary mr-2"></i>Código actual</span>
                            <span class="badge badge-secondary"><?= htmlspecialchars($producto['codigo'] ?: 'Sin código'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-boxes text-success mr-2"></i>Stock actual</span>
                            <span class="badge <?= $producto['stock'] < $producto['stock_minimo'] ? 'badge-danger' : 'badge-success'; ?>">
                                <?= $producto['stock']; ?> unidades
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-toggle-on text-warning mr-2"></i>Estado actual</span>
                            <span class="badge <?= $estado_ui['badge']; ?>"><?= $estado_ui['label']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-money-bill-wave text-success mr-2"></i>Precio de compra</span>
                            <span class="badge badge-success">Bs <?= number_format($producto['preciocompra'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-money-bill-wave text-primary mr-2"></i>Precio de venta</span>
                            <span class="badge badge-primary">Bs <?= number_format($producto['precioventa'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="far fa-calendar-plus mr-2"></i>Creado</span>
                            <span class="badge badge-secondary">
                                <?= isset($producto['fechacreacion']) ? date('d/m/Y H:i', strtotime($producto['fechacreacion'])) : 'No disponible'; ?>
                            </span>
                        </li>
                        <?php if (isset($producto['fechaactualizacion']) && !empty($producto['fechaactualizacion'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="far fa-calendar-check mr-2"></i>Actualizado</span>
                                <span class="badge badge-secondary"><?= date('d/m/Y H:i', strtotime($producto['fechaactualizacion'])); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Tarjeta de acciones rápidas -->
                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="list-group-item list-group-item-action">
                                <i class="fas fa-eye mr-2 text-info"></i> Ver detalles del producto
                            </a>
                            <a href="#" class="list-group-item list-group-item-action cambiar-estado-producto"
                                data-id="<?= (int)$producto['idproducto']; ?>"
                                data-estado="<?= (int)$producto['estado']; ?>"
                                data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>">
                                <i class="fas <?= $estado_ui['accion_icono']; ?> mr-2 <?= $estado_ui['accion_texto']; ?>"></i> <?= $estado_ui['accion_label']; ?>
                            </a>
                            <a href="<?= $URL; ?>views/productos" class="list-group-item list-group-item-action">
                                <i class="fas fa-list mr-2 text-primary"></i> Volver a la lista de productos
                            </a>
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
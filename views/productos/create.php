<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/categoria/CategoriaController.php';
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
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

// Obtener categorías desde el controlador
$categoriaController = new CategoriaController();
$categorias = $categoriaController->index();

// Incluir el encabezado
$umbrales = ProductoController::umbralesMargen();
$estados = ProductoController::estadosProducto();
$module_styles = ['productos/productos'];
$skip_datatables = true;
$skip_chartjs = true;
$module_scripts = ['productos/producto-form', 'productos/create-producto'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Crear Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item active">Crear Producto</li>
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
                <form action="<?= $URL; ?>controllers/productos/crear_producto.php" method="POST" enctype="multipart/form-data" id="formCrearProducto"
                    data-margen-alerta="<?= $umbrales['alerta']; ?>" data-margen-bueno="<?= $umbrales['bueno']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">


                    <!-- Sección de Información Básica -->
                    <div class="card card-outline card-primary mb-3">
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
                                                    <option value="<?= $categoria['idcategoria']; ?>">
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
                                                placeholder="Ingrese el código del producto">
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
                                                placeholder="Ingrese el nombre del producto" required>
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
                                            placeholder="Ingrese una descripción detallada del producto"></textarea>
                                        <small class="form-text text-muted mb-0">Características, usos, dimensiones u otra información relevante</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Precios -->
                    <div class="card card-outline card-primary mb-3">
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
                                                step="0.01" min="0" placeholder="0.00" required>
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
                                                step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se vende el producto</small>
                                        <div id="precio-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Inventario -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-warehouse mr-2"></i>Información de Inventario</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Stock Inicial -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock">Stock Inicial <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock" name="stock"
                                                min="0" value="0" required>
                                        </div>
                                        <small class="form-text text-muted">Cantidad inicial disponible del producto</small>
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
                                                min="0" value="5">
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
                                                min="0">
                                        </div>
                                        <small class="form-text text-muted">Cantidad máxima recomendada (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            <div id="stock-validation-feedback"></div>
                        </div>
                    </div>

                    <!-- Sección de Imagen -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-image mr-2"></i>Imagen y Estado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-7">
                                    <div class="form-group mb-0">
                                        <label for="imagen">Imagen del Producto</label>
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
                                        <small class="form-text text-muted mb-0">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <?php foreach ($estados as $estado) : ?>
                                                <option value="<?= (int) $estado['valor']; ?>"
                                                    <?= (int) $estado['valor'] === 1 ? 'selected' : ''; ?>>
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
                                    <button type="submit" class="btn btn-primary w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-save mr-2"></i> Guardar Producto
                                    </button>
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
                <!-- Resumen en vivo del producto que se está creando -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Resumen</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="resumen-vivo"
                            data-estados='<?= htmlspecialchars(json_encode($estados), ENT_QUOTES); ?>'>
                            <li class="list-group-item">
                                <div class="small text-muted mb-1">Nombre</div>
                                <div id="rv-nombre" class="font-weight-bold">Sin nombre</div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Categoría</span>
                                <span id="rv-categoria" class="font-weight-bold text-right">—</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Código</span>
                                <span id="rv-codigo" class="font-weight-bold text-right">—</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Precio de compra</span>
                                <span id="rv-compra" class="font-weight-bold">Bs 0.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Precio de venta</span>
                                <span id="rv-venta" class="font-weight-bold">Bs 0.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Margen</span>
                                <span id="rv-margen" class="font-weight-bold">—</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Stock inicial</span>
                                <span id="rv-stock" class="font-weight-bold">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Estado</span>
                                <span id="rv-estado" class="badge badge-success font-weight-bold">Activo</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Vista previa de la imagen -->
                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-image mr-2"></i>Vista previa</h3>
                    </div>
                    <div class="card-body text-center">
                        <div id="preview-container" style="display: none;">
                            <img id="preview-image" src="#" alt="Vista previa de la imagen del producto"
                                class="img-thumbnail prod-img-preview">
                        </div>
                        <p id="preview-placeholder" class="text-muted mb-0">
                            Seleccione un archivo para ver la imagen aquí
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
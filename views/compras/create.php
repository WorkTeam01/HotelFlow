<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$usuarioController = new UsuarioController();
if (!isset($_SESSION['usuario_nombre'])) {
    $usuarioActual = $usuarioController->editar($idusuario);
    $_SESSION['usuario_nombre'] = $usuarioActual['nombre'];
}

// Incluir encabezado
$skip_chartjs = true;
include_once '../layouts/header.php';

$productoController = new ProductoController();
$productos = $productoController->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Registrar Nueva Compra</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"><i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active">Nueva Compra</li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Compra</h3>
                    </div>
                    <form action="<?= $URL; ?>controllers/compras/crear_compra.php" method="POST" id="form-compra" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Usuario Responsable (no editable) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nombre_usuario">Responsable <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario"
                                            value="<?= htmlspecialchars($_SESSION['usuario_nombre']); ?>" readonly>
                                        <input type="hidden" id="idusuario" name="idusuario" value="<?= $idusuario; ?>">
                                    </div>
                                </div>

                                <!-- Fecha de Compra -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fechacompra">Fecha de Compra <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fechacompra" name="fechacompra"
                                            value="<?= date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="estado">Estado <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="estado" name="estado" required>
                                            <option value="pendiente" selected>Pendiente</option>
                                            <option value="completada">Completada</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Observaciones -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones"
                                            placeholder="Ingrese observaciones adicionales (opcional)" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Detalle de Productos</h5>

                            <!-- Buscador de productos por código -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="buscar-producto">Buscar Producto por Código</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="buscar-producto" placeholder="Ingrese código del producto">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" id="btn-buscar-producto">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de productos -->
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40%">Producto</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="20%">Precio Unitario</th>
                                            <th width="20%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas de productos se agregarán dinámicamente -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                                            <td class="text-right"><strong><span id="total-compra">0.00</span></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">
                                                <input type="hidden" name="totalcompra" id="input-total-compra" value="0">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Compra
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/compras" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
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

<style>
    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 80%;
        color: #dc3545;
    }

    input[readonly] {
        background-color: #e9ecef;
        opacity: 1;
    }
</style>

<script>
    // Datos de productos disponibles inyectados desde PHP para la búsqueda por código
    const productosDisponibles = <?= json_encode($productos); ?>;
</script>
<script src="<?= $URL; ?>public/js/modules/compras/create-compras.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
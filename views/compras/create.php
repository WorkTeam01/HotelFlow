<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$skip_chartjs = true;
$skip_datatables = true;
$module_styles = ['compras/compras'];
$module_scripts = ['ventas/venta-util', 'compras/compra-selector', 'compras/compra-detalle', 'compras/create-compras'];
include_once '../layouts/header.php';

$datos = (new CompraController())->datosParaCrear();
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row">
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

<section class="content compras-pagina">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/compras/crear_compra.php" method="POST" id="form-compra" novalidate
            data-productos="<?= htmlspecialchars(json_encode($datos['productos']), ENT_QUOTES) ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

            <div class="row">
                <!-- Columna izquierda: detalle de productos y observaciones -->
                <div class="col-lg-8">
                    <?php include __DIR__ . '/partials/compra-detalle.php'; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="observaciones">Observaciones:</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Observaciones adicionales sobre la compra (opcional)..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: cabecera, total y acciones (fija) -->
                <div class="col-lg-4">
                    <div class="sidebar-sticky">
                        <?php include __DIR__ . '/partials/compra-cabecera.php'; ?>

                        <?php include __DIR__ . '/partials/compra-resumen.php'; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/partials/modal-productos.php'; ?>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
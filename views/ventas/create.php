<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'ventas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$skip_chartjs = true;
$skip_datatables = true;
$module_styles = ['ventas/ventas'];
$module_scripts = ['ventas/venta-util', 'ventas/venta-selectores', 'ventas/venta-pagos', 'ventas/venta-detalle', 'ventas/create-venta'];
include_once '../layouts/header.php';

$datos = (new VentaController())->datosParaCrear();
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Nueva Venta</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas"> <i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Nueva Venta</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content ventas-pagina">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/ventas/crear_venta.php" method="POST" id="form-venta" novalidate
            data-productos="<?= htmlspecialchars(json_encode($datos['productos']), ENT_QUOTES) ?>"
            data-clientes="<?= htmlspecialchars(json_encode($datos['clientes']), ENT_QUOTES) ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <input type="hidden" name="totalventa" id="totalventa-hidden" value="0">

            <div class="row">
                <!-- Columna izquierda: productos, forma de pago y observaciones -->
                <div class="col-lg-8">
                    <?php include __DIR__ . '/partials/venta-carrito.php'; ?>

                    <?php include __DIR__ . '/partials/venta-pago.php'; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="observacion">Observaciones:</label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Observaciones adicionales sobre la venta..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: cliente, fecha, totales y acciones (fija) -->
                <div class="col-lg-4">
                    <div class="sidebar-sticky">
                        <?php include __DIR__ . '/partials/venta-cliente.php'; ?>

                        <?php include __DIR__ . '/partials/venta-resumen.php'; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/partials/modales-venta.php'; ?>

<?php include __DIR__ . '/partials/plantilla-metodo-pago.php'; ?>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
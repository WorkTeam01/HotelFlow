<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();

if (!isset($URL)) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $URL = $config['app']['url'];
}

$idusuario = $_SESSION['usuario_id'] ?? '';
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'ventas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Verificar ID de venta
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $_SESSION['mensaje'] = 'ID de venta no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Obtener datos de la venta (ver() adjunta helpers: totales, info_metodo_pago, etc.)
$controller = new VentaController();
$venta = $controller->ver($id);

if (!$venta) {
    $_SESSION['mensaje'] = 'Venta no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// IDOR: no-admin solo puede ver sus propias ventas
if (!$authService->esAdministrador($idusuario) && $venta['idusuario'] != $idusuario) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver esta venta.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Helpers ya resueltos por ver()
$esActiva = (int)$venta['estado'] === 1;
$esPagoMixto = $venta['info_metodo_pago']['es_mixto'];
$codigoVenta = 'VENT-' . str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT);
$puedeAnular = $esActiva && $authService->esAdministrador($idusuario);

$skip_datatables = true;
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['ventas/ventas'];
$module_scripts = ['ventas/anular-venta'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Venta <?= $codigoVenta; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas/"><i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Detalle de Venta</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content ventas-pagina">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna izquierda: contexto de la venta (quién, cuándo, cómo se pagó) y acciones -->
            <div class="col-lg-4 mb-3">
                <div class="sidebar-sticky">
                    <?php include __DIR__ . '/partials/info-general.php'; ?>

                    <?php include __DIR__ . '/partials/metodos-pago.php'; ?>
                </div>
            </div>

            <!-- Columna derecha: qué se vendió -->
            <div class="col-lg-8">
                <?php include __DIR__ . '/partials/productos-vendidos.php'; ?>

                <?php include __DIR__ . '/partials/informacion-adicional.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
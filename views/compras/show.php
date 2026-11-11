<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
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

$esAdmin = $authService->esAdministrador($idusuario);
$acciones = CompraController::accionesEstado($compra['estado'], $esAdmin);

$skip_datatables = true;
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['compras/compras'];
$module_scripts = ['compras/cambiar-estado-compras'];
include_once '../layouts/header.php';
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
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
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
            <!-- Columna izquierda: contexto de la compra (quién, cuándo, estado) y acciones -->
            <div class="col-lg-4 mb-3">
                <div class="sidebar-sticky">
                    <?php include __DIR__ . '/partials/info-general.php'; ?>
                </div>
            </div>

            <!-- Columna derecha: qué se compró -->
            <div class="col-lg-8">
                <?php include __DIR__ . '/partials/productos-comprados.php'; ?>

                <?php include __DIR__ . '/partials/informacion-adicional.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
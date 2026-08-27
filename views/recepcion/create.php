<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$module_styles = ['recepciones/recepcion', 'recepciones/create-recepcion'];
$module_scripts = ['recepciones/create-recepcion'];

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

$idhabitacion = isset($_GET['idhabitacion']) ? (int) $_GET['idhabitacion'] : null;

$controller = new RecepcionController();
$datos = $controller->crear($idhabitacion);
$clientes = $datos['clientes'];
$tarifas = $datos['tarifas'];
$habitacion = $datos['habitacion'];
$habitaciones_disponibles = $datos['habitaciones_disponibles'];
$habitaciones_por_piso = $datos['habitaciones_por_piso'];
$pisos_unicos = $datos['pisos_unicos'];

// ?idhabitacion= inválida u ocupada: la reserva sigue siendo posible con otra
// habitación, así que solo se descarta la preselección (no se redirige).
if ($idhabitacion && !$habitacion) {
    $_SESSION['mensaje'] = 'La habitación indicada ya no está disponible; elija otra.';
    $_SESSION['icono'] = 'warning';
    $habitacion = null;
}

$skip_chartjs = true;
$skip_datatables = true;
include_once '../layouts/header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-5"><h1>Nueva reserva</h1></div>
            <div class="col-sm-7 d-flex flex-column flex-sm-row justify-content-sm-end align-items-sm-center">
                <?php include __DIR__ . '/partials/buscador-global.php'; ?>
                <a href="<?= $URL; ?>views/recepcion/index.php" class="btn btn-outline-secondary btn-sm ml-sm-2 mt-2 mt-sm-0">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <?php include __DIR__ . '/partials/form-reserva.php'; ?>
            </div>
            <div class="col-lg-4">
                <?php include __DIR__ . '/partials/resumen-reserva.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

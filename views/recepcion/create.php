<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Definir recursos del módulo - reutilizando CSS del index + específicos del create
$module_styles = ['recepciones/index-recepciones', 'recepciones/create-recepcion'];
$module_scripts = ['recepciones/create-recepcion'];

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Obtener ID de habitación si se proporcionó
$idhabitacion = isset($_GET['idhabitacion']) ? (int)$_GET['idhabitacion'] : null;

// Instanciar el controlador y obtener datos para la vista
$controller = new RecepcionController();
$datos = $controller->crear($idhabitacion);
$clientes = $datos['clientes'];
$tarifas = $datos['tarifas'];
$habitacion = $datos['habitacion'];
$habitaciones_disponibles = $datos['habitaciones_disponibles'];
$habitaciones_por_piso = $datos['habitaciones_por_piso'];
$pisos_unicos = $datos['pisos_unicos'];

// ID de habitación inválido/ocupado/inexistente: volver al paso 1 antes de emitir HTML
if ($idhabitacion && !$habitacion) {
    $_SESSION['mensaje'] = 'La habitación seleccionada ya no está disponible.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/create.php');
    exit;
}

// Incluir el encabezado después de verificar permisos y validar la habitación
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header" data-module="recepcion-create" data-step="<?= !$idhabitacion ? 'select-room' : 'create-checkin'; ?>">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <?php if (!$idhabitacion): ?>
                        Seleccionar Habitación
                    <?php else: ?>
                        Nueva Reserva
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion"><i class="fas fa-bed"></i> Recepción</a></li>
                    <li class="breadcrumb-item active">
                        <?= !$idhabitacion ? 'Seleccionar Habitación' : 'Nueva Reserva'; ?>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (!$idhabitacion): ?>
            <?php include __DIR__ . '/partials/paso-seleccion-habitacion.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/partials/paso-datos-checkin.php'; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Modal de confirmación de habitación -->
<div class="modal fade" id="modalConfirmRoom" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bed mr-2"></i>Confirmar Selección de Habitación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-bed fa-3x text-success mb-3"></i>
                    <h4>Habitación N° <span id="modal-room-number"></span></h4>
                    <p class="text-muted mb-3">Tipo: <span id="modal-room-type"></span></p>
                    <p><strong>Precio base: Bs <span id="modal-room-price"></span></strong></p>
                </div>
                <hr>
                <p class="mb-0">¿Desea proceder con esta habitación para crear la reserva?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btn-confirm-room">
                    <i class="fas fa-check mr-1"></i>Confirmar y Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

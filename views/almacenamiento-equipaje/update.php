<?php
require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'equipajes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de equipaje no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

// Datos resueltos ANTES del header (todas las validaciones/redirects primero)
$controller = new AlmacenamientoEquipajeController();
$datos = $controller->editar($id);

if (!$datos) {
    $_SESSION['mensaje'] = 'Equipaje no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

$equipaje = $datos['equipaje'];
$clientes = $datos['clientes'];

// No permitir editar equipajes retirados
if ($equipaje['estado'] === 'retirado') {
    $_SESSION['mensaje'] = 'No se puede editar un equipaje que ya ha sido retirado';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/show.php?id=' . $id);
    exit;
}

$estado_ui = $equipaje['estado_ui'];
$tiempo = $equipaje['tiempo_almacenado'];

// Assets del módulo declarados ANTES de incluir header.php
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];
$module_scripts = ['almacenamiento-equipaje/update-equipaje'];
$skip_chartjs = true;

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Editar Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Editar Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna principal: formulario de edición -->
            <div class="col-lg-8">
                <?php include __DIR__ . '/partials/form-actualizar-equipaje.php'; ?>
            </div>

            <!-- Columna lateral: resumen sticky -->
            <div class="col-lg-4">
                <?php include __DIR__ . '/partials/resumen-actualizar-equipaje.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

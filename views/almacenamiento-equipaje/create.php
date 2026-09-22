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

// Datos resueltos ANTES del header (patrón recepcion: ninguna salida antes de validar/consultar)
$controller = new AlmacenamientoEquipajeController();
$datos = $controller->crear();
$clientes = $datos['clientes'];
$precios_equipaje = $datos['precios_equipaje'];

// Assets del módulo declarados ANTES de incluir header.php
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];
$module_scripts = ['almacenamiento-equipaje/create-equipaje'];
$skip_chartjs = true;

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Registrar Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna principal: formulario de registro -->
            <div class="col-lg-8">
                <?php include __DIR__ . '/partials/form-registro-equipaje.php'; ?>
            </div>

            <!-- Columna lateral: resumen sticky + tarifas -->
            <div class="col-lg-4">
                <?php include __DIR__ . '/partials/resumen-registro-equipaje.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
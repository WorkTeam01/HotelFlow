<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'servicios_bano')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano/index.php');
    exit;
}

// Instanciar el controlador
$controller = new ServicioBanoController();

$id_servicio = null;
$nuevo_estado = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['nuevo_estado']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    $id_servicio = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $nuevo_estado = filter_var($_GET['nuevo_estado'], FILTER_SANITIZE_STRING);

    if ($id_servicio === false || $nuevo_estado === '') {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del servicio.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/servicios-bano/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstadoServicio($id_servicio, $nuevo_estado);
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/servicios-bano/index.php');
exit;

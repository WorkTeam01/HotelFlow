<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/TipoHabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'tipohabitaciones')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/tipohabitacion/index.php');
    exit;
}

// Instanciar el controlador
$controller = new TipoHabitacionController();

$id_tipo = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    $id_tipo = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id_tipo === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del tipo de habitación.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/tipohabitacion/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstado($id_tipo);
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/tipohabitacion/index.php');
exit;
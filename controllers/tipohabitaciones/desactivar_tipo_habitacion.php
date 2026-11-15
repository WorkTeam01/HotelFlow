<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/TipoHabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

requireModuloVista('tipohabitaciones', 'views/tipohabitacion/index.php', 'No tiene permisos para realizar esta acción.');

// Instanciar el controlador
$controller = new TipoHabitacionController();

$id_tipo = null;

if ($_SERVER['REQUEST_METHOD'] != 'GET' || !isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/tipohabitacion/index.php');
    exit;
}

if (!isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
    $_SESSION['mensaje'] = 'Token de seguridad inválido o expirado.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/tipohabitacion/index.php');
    exit;
}

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

header('Location: ' . $URL . 'views/tipohabitacion/index.php');
exit;
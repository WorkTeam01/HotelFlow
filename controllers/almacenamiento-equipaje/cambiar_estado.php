<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'equipajes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

// Obtener los parámetros de la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$nuevo_estado = isset($_GET['nuevo_estado']) ? $_GET['nuevo_estado'] : null;

// Validar los parámetros y token CSRF
if ($id === null || $nuevo_estado === null || !isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
    $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del equipaje.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

// Instanciar el controlador
$controller = new AlmacenamientoEquipajeController();

// Procesar el cambio de estado
$resultado = $controller->cambiarEstado($id, $nuevo_estado);

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir a la página anterior o a la página de detalles del equipaje
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
if ($referer && strpos($referer, $URL) !== false) {
    header('Location: ' . $referer);
} else {
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/show.php?id=' . $id);
}
exit;

<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Verificar token CSRF en solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token']))) {
    $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/create.php');
    exit;
}

// Instanciar el controlador
$controller = new RecepcionController();

// Procesar el formulario
$resultado = $controller->guardar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Si la operación fue exitosa y se generó un ID, redirigir a la página de detalles
if ($resultado['success'] && isset($resultado['id'])) {
    header('Location: ' . $URL . 'views/recepcion/show.php?id=' . $resultado['id']);
} else {
    // Redirigir según el resultado
    header('Location: ' . $URL . 'views/recepcion/' . $resultado['redirect']);
}
exit;

<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de Compras
require_once __DIR__ . '/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Definir la variable global URL si no existe
if (!isset($GLOBALS['URL'])) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $GLOBALS['URL'] = $config['app']['url'];
}

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $GLOBALS['URL'] . 'views/compras/index.php');
    exit;
}

// Instanciar el controlador de Compras
$controller = new CompraController();

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $GLOBALS['URL'] . 'views/compras/index.php');
        exit;
    }

    $resultado = $controller->guardar();
    
    // Guardar mensaje en la sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];

    // Redirigir según el resultado
    header('Location: ' . $GLOBALS['URL'] . 'views/compras/' . $resultado['redirect']);
    exit;
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $GLOBALS['URL'] . 'views/compras/index.php');
    exit;
}
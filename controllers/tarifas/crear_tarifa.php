<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Incluir el controlador de Tarifas
require_once __DIR__ . '/TarifaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Definir la variable global URL si no existe
if (!isset($GLOBALS['URL'])) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $GLOBALS['URL'] = $config['app']['url'];
}

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'tarifas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $GLOBALS['URL'] . 'views/tarifas/index.php');
    exit;
}

// Instanciar el controlador de Tarifas
$controller = new TarifaController();

// Determinar la acción basada en la URL o parámetros
$accion = 'index'; // Acción por defecto

// Verificar si se está enviando un formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determinar si es creación o actualización
    $accion = isset($_POST['idtarifa']) && !empty($_POST['idtarifa']) ? 'update' : 'create';
}

// Verificar token CSRF en solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token']))) {
    $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $GLOBALS['URL'] . 'views/tarifas/index.php');
    exit;
}

// Procesar la acción correspondiente
switch ($accion) {
    case 'create':
        $resultado = $controller->guardar();
        break;
    case 'update':
        $resultado = $controller->actualizar();
        break;
    default:
        // Redirigir a la lista si no se reconoce la acción
        header('Location: ' . $GLOBALS['URL'] . 'views/tarifas/index.php');
        exit;
}

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $GLOBALS['URL'] . 'views/tarifas/' . $resultado['redirect']);
exit;
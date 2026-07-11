<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de perfil
require_once __DIR__ . '/PerfilController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar token CSRF en solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token']))) {
    $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/usuarios/perfil.php');
    exit;
}

// Instanciar el controlador
$controller = new PerfilController();
$resultado = $controller->actualizarImagen();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . $resultado['redirect']);
exit;

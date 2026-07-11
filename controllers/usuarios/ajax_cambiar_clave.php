<?php
// Incluir el archivo de sesión para tener acceso a requireLogin()/verifyCSRFToken()
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de perfil
require_once __DIR__ . '/PerfilController.php';

header('Content-Type: application/json');

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido', 'icon' => 'error']);
    exit;
}

// Verificar que el usuario esté autenticado
requireLogin();

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargue la página e intente nuevamente.', 'icon' => 'error']);
    exit;
}

// Instanciar el controlador
$controller = new PerfilController();
$resultado = $controller->cambiarContrasena();

// Devolver respuesta JSON
echo json_encode($resultado);
exit;

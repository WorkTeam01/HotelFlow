<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el controlador de perfil
require_once __DIR__ . '/PerfilController.php';

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido', 'icon' => 'error']);
    exit;
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado', 'icon' => 'error']);
    exit;
}

// Instanciar el controlador
$controller = new PerfilController();
$resultado = $controller->cambiarContrasena();

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($resultado);
exit;

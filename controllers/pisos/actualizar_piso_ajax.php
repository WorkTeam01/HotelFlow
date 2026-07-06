<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/PisoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

// Verificar si el usuario está autenticado
requireLogin();

header('Content-Type: application/json');

// Verificar permisos
$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'pisos')) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción']);
    exit;
}

// Instanciar el controlador
$controller = new PisoController();

// Procesar la actualización
$resultado = $controller->actualizarAjax();

// Establecer mensaje en la sesión para mensajes.php
if ($resultado['success']) {
    $_SESSION['mensaje'] = 'Piso actualizado correctamente';
    $_SESSION['icono'] = 'success';
}

// Devolver respuesta JSON
echo json_encode($resultado);
exit;

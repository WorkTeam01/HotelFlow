<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/AsignacionLimpiezaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

// Verificar si el usuario tiene permiso
$idusuario = $_SESSION['usuario_id'] ?? 0;
$auth = new AuthorizationService();

// Este es un ejemplo, adapta la verificación según tus permisos específicos
// Podrías necesitar crear un permiso específico para asignaciones de limpieza
if (!$auth->puedeAccederModulo($idusuario, 'limpieza')) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para crear asignaciones de limpieza'
    ]);
    exit;
}

// Configurar respuesta como JSON
header('Content-Type: application/json');

// Instanciar el controlador
$controller = new AsignacionLimpiezaController();

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargue la página e intente nuevamente.']);
        exit;
    }

    // Llamar al método del controlador
    $resultado = $controller->crearAjax();

    // Guardar mensaje en sesión para mensajes.php
    if ($resultado['success']) {
        $_SESSION['mensaje'] = 'Asignación de limpieza creada correctamente';
        $_SESSION['icono'] = 'success';
    }

    echo json_encode($resultado);
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

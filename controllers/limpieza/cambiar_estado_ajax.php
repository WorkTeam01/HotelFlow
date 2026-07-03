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

// Configurar respuesta como JSON
header('Content-Type: application/json');

// Instanciar el controlador
$controller = new AsignacionLimpiezaController();

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';

    if (!$id || !$estado) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos para cambiar el estado'
        ]);
        exit;
    }

    // Verificar permisos específicos para ciertos estados
    $idusuario = $_SESSION['usuario_id'] ?? 0;
    $auth = new AuthorizationService();

    // Verificar permiso para estados que requieren ser administrador
    if ($estado === 'verificada' && !$auth->puedeAccederModulo($idusuario, 'limpieza')) {
        echo json_encode([
            'success' => false,
            'message' => 'Solo recepcionistas y administradores pueden verificar asignaciones'
        ]);
        exit;
    }

    // Llamar al método del controlador
    $resultado = $controller->cambiarEstadoAjax();

    // Guardar mensaje en sesión para mensajes.php
    if ($resultado['success']) {
        $_SESSION['mensaje'] = $resultado['message'];
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

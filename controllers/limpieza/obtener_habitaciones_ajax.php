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
if (!$auth->puedeAccederModulo($idusuario, 'limpieza')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para acceder a esta funcionalidad'
    ]);
    exit;
}

// Configurar respuesta como JSON
header('Content-Type: application/json');

// Instanciar el controlador
$controller = new AsignacionLimpiezaController();

// Obtener el ID de la asignación (será null para crear nueva asignación)
$id_asignacion = isset($_POST['id_asignacion']) && !empty($_POST['id_asignacion']) ? (int)$_POST['id_asignacion'] : null;

// Obtener las habitaciones
$habitaciones = $controller->getHabitaciones($id_asignacion);

// Verificar si se obtuvieron habitaciones
if (empty($habitaciones)) {
    echo json_encode([
        'success' => false,
        'message' => 'No hay habitaciones disponibles con estado limpieza'
    ]);
    exit;
}

// Devolver respuesta exitosa con las habitaciones
echo json_encode([
    'success' => true,
    'habitaciones' => $habitaciones
]);
exit;

<?php
// Incluir archivos necesarios
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Configurar respuesta como JSON
header('Content-Type: application/json');

// Verificar permisos
$idusuario = $_SESSION['usuario_id'] ?? 0;
$auth = new AuthorizationService();

if (!$auth->puedeAccederModulo($idusuario, 'servicios_bano')) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para crear servicios de baños',
        'icon' => 'error'
    ]);
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Token de seguridad inválido. Recargue la página e intente nuevamente.',
        'icon' => 'error'
    ]);
    exit;
}

// Procesar solicitud
try {
    // Instanciar el controlador
    $controller = new ServicioBanoController();

    // Obtener el ID del baño si se proporcionó
    $idbano = isset($_POST['idbano']) ? (int)$_POST['idbano'] : null;

    // Usar el método crearServicioRapido
    $resultado = $controller->crearServicioRapido($idbano);

    // Devolver respuesta JSON
    echo json_encode($resultado);
} catch (Exception $e) {
    // Capturar cualquier excepción
    error_log('Error en crear_servicio_rapido: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error en el servidor. Intente nuevamente.',
        'icon' => 'error'
    ]);
}
exit;

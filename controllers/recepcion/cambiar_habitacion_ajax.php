<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

requireLogin();

header('Content-Type: application/json');

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'recepcion')) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para realizar esta acción']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargue la página e intente nuevamente.']);
    exit;
}

$idrecepcion = isset($_POST['idrecepcion']) ? (int)$_POST['idrecepcion'] : 0;
$idHabitacionDestino = isset($_POST['idhabitacion_destino']) ? (int)$_POST['idhabitacion_destino'] : 0;
$motivo = isset($_POST['motivo']) && trim($_POST['motivo']) !== '' ? trim($_POST['motivo']) : null;

if ($idrecepcion <= 0 || $idHabitacionDestino <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos para cambiar de habitación.']);
    exit;
}

$controller = new RecepcionController();
$resultado = $controller->cambiarHabitacion(
    $idrecepcion,
    $idHabitacionDestino,
    $idusuario,
    $motivo !== null ? htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') : null
);

if ($resultado['success']) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
}

echo json_encode($resultado);
exit;

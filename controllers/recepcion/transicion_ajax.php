<?php

/**
 * Endpoint AJAX: transiciones simples de estado de una recepción por POST.
 * Solo admite 'en_curso' (check-in) y 'cancelado' (cancelar reserva/estancia).
 * El check-out va por checkout_ajax.php porque valida el saldo del folio.
 */

require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
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

$idrecepcion = isset($_POST['idrecepcion']) ? (int) $_POST['idrecepcion'] : 0;
$nuevoEstado = $_POST['nuevo_estado'] ?? '';

if ($idrecepcion <= 0) {
    echo json_encode(['success' => false, 'message' => 'Recepción no válida.']);
    exit;
}
if (!in_array($nuevoEstado, ['en_curso', 'cancelado'], true)) {
    echo json_encode(['success' => false, 'message' => 'Transición no permitida.']);
    exit;
}

$controller = new RecepcionController();
$resultado = $controller->cambiarEstado($idrecepcion, $nuevoEstado);

if (!empty($resultado['success'])) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;

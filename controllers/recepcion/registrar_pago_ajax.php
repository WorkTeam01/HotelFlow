<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/../../models/Pago.php';
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
$monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
$metodopago = $_POST['metodopago'] ?? '';
$concepto = isset($_POST['concepto']) && trim($_POST['concepto']) !== '' ? trim($_POST['concepto']) : 'Abono';

if ($idrecepcion <= 0 || !in_array($metodopago, ['Efectivo', 'QR', 'OTROS'], true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos para registrar el pago.']);
    exit;
}

$pagoModelo = new Pago();
$idLinea = $pagoModelo->registrarPago($idrecepcion, $monto, $metodopago, $idusuario, htmlspecialchars($concepto, ENT_QUOTES, 'UTF-8'));

if ($idLinea) {
    $_SESSION['mensaje'] = 'Pago registrado correctamente';
    $_SESSION['icono'] = 'success';
    echo json_encode(['success' => true, 'message' => 'Pago registrado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => $pagoModelo->getLastError()]);
}
exit;

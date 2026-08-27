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

$idrecepcion = isset($_POST['idrecepcion']) ? (int) $_POST['idrecepcion'] : 0;
if ($idrecepcion <= 0) {
    echo json_encode(['success' => false, 'message' => 'Recepción no válida.']);
    exit;
}

// El pago del saldo es opcional: si no llega, checkout() responde requiere_pago cuando haga falta
$pagoFinal = null;
if (isset($_POST['monto']) && $_POST['monto'] !== '') {
    $pagoFinal = [
        'monto' => (float) $_POST['monto'],
        'metodopago' => $_POST['metodopago'] ?? '',
    ];
}

$controller = new RecepcionController();
$resultado = $controller->checkout($idrecepcion, (int) $idusuario, $pagoFinal);

if (!empty($resultado['success'])) {
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = 'success';
}

echo json_encode($resultado);
exit;

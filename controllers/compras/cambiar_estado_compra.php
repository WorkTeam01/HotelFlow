<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

requireLogin();

// Contrato dual: JSON si llega con X-Requested-With, redirect + mensaje de sesión si no.
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$responder = function (bool $success, string $mensaje, string $icono = 'error', ?int $id = null) use ($esAjax, $URL) {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $mensaje]);
        exit;
    }

    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['icono'] = $icono;

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer !== '' && str_starts_with($referer, $URL)) {
        header('Location: ' . $referer);
    } elseif ($id) {
        header('Location: ' . $URL . 'views/compras/show.php?id=' . $id);
    } else {
        header('Location: ' . $URL . 'views/compras/index.php');
    }
    exit;
};

$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'compras')) {
    $responder(false, 'No tiene permisos para realizar esta acción.');
}

// Completar o cancelar mueve inventario: solo el Administrador
if (!$auth->esAdministrador($idusuario_sesion)) {
    $responder(false, 'Solo un administrador puede completar o cancelar una compra.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $responder(false, 'Método no permitido.');
}

if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $responder(false, 'Token de seguridad inválido. Recargue la página e intente nuevamente.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

if (!$id || !in_array($nuevo_estado, ['completada', 'cancelada'], true)) {
    $responder(false, 'Datos inválidos para cambiar el estado de la compra.');
}

$resultado = (new CompraController())->cambiarEstado($id, $nuevo_estado);

$responder($resultado['success'], $resultado['message'], $resultado['icon'], $id);

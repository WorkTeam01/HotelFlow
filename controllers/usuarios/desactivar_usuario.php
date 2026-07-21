<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'usuarios')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/usuarios/index.php');
    exit;
}

// Instanciar el controlador
$controller = new UsuarioController();

$id_usuario = null;
$estado_actual = null;
$esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    $id_usuario = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

    if ($id_usuario === false || $estado_actual === false) {
        if ($esAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Datos inválidos para cambiar el estado del usuario.']);
            exit;
        }
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del usuario.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/usuarios/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstadoUsuario($id_usuario, $estado_actual);

    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'nuevoEstado' => $resultado['nuevo_estado'] ?? null,
        ]);
        exit;
    }

    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acción no permitida.']);
        exit;
    }
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/usuarios/index.php');
exit;

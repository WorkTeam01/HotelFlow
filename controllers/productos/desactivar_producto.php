<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// La respuesta se emite como JSON cuando la petición llega por AJAX y como
// redirect + mensaje en sesión cuando llega de un formulario clásico.
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Verificar permisos
$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'productos')) {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para realizar esta acción'
        ]);
        exit;
    }

    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/productos/index.php');
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $_SESSION['mensaje'] = 'Método no permitido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/productos/index.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargue la página e intente nuevamente.']);
        exit;
    }

    $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/productos/index.php');
    exit;
}

// Obtener el producto a modificar. El nuevo estado se recalcula en el
// controlador a partir del valor real en BD: nunca se acepta del cliente.
$id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : false;

if ($id === false || $id <= 0) {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
        exit;
    }

    $_SESSION['mensaje'] = 'ID de producto inválido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/productos/index.php');
    exit;
}

// Instanciar el controlador y cambiar el estado
$controller = new ProductoController();
$resultado = $controller->cambiarEstadoProducto($id);

// Responder según el tipo de solicitud
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $resultado['success'],
        'message' => $resultado['message']
    ]);
    exit;
}

$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['success'] ? 'success' : 'error';

if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ' . $URL . 'views/productos/index.php');
}
exit;

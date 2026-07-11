<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'productos')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/productos/index.php');
    exit;
}

// Instanciar el controlador de productos
$controller = new ProductoController();

$id_producto = null;

// Validar método GET, parámetros y token CSRF
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    // Filtrar y validar el parámetro
    $id_producto = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si el parámetro es válido
    if ($id_producto === false) {
        $_SESSION['mensaje'] = 'ID de producto inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/productos/index.php');
        exit;
    }

    // Cambiar el estado del producto (recalculado a partir del valor real en BD)
    $resultado = $controller->cambiarEstadoProducto($id_producto);

    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de productos
header('Location: ' . $URL . 'views/productos/index.php');
exit;

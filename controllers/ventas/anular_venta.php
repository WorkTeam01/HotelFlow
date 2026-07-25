<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion)) {
    $_SESSION['mensaje'] = 'Solo un administrador puede anular una venta.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Instanciar el controlador de ventas
$controller = new VentaController();

$id_venta = null;

// Validar método GET, parámetros y token CSRF
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    // Filtrar y validar el ID
    $id_venta = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si el ID es válido
    if ($id_venta === false) {
        $_SESSION['mensaje'] = 'ID de venta inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/ventas/index.php');
        exit;
    }

    // Ejecutar la acción de anulación
    $resultado = $controller->anular($id_venta);
    
    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de ventas
header('Location: ' . $URL . 'views/ventas/index.php');
exit;
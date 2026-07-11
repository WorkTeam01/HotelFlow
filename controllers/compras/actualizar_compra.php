<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/compras/index.php');
    exit;
}

// Instanciar el controlador de compras
$controller = new CompraController();

$id_compra = null;

// Validar método GET, parámetros y token CSRF
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    // Filtrar y validar el parámetro
    $id_compra = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si el parámetro es válido
    if ($id_compra === false) {
        $_SESSION['mensaje'] = 'ID de compra inválido.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/compras/index.php');
        exit;
    }

    // Completar la compra
    $resultado = $controller->completar($id_compra);
    
    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de compras
header('Location: ' . $URL . 'views/compras/index.php');
exit;
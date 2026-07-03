<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/HabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'habitaciones')) {
    // Responder con error si es AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para realizar esta acción'
        ]);
        exit;
    }

    // Redirigir si no es AJAX
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Responder con error si es AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }

    // Redirigir si no es AJAX
    $_SESSION['mensaje'] = 'Método no permitido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

// Obtener datos
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

if (!$id || empty($estado)) {
    // Responder con error si es AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
        exit;
    }

    // Redirigir si no es AJAX
    $_SESSION['mensaje'] = 'Datos incompletos.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

// Instanciar el controlador
$controller = new HabitacionController();

// Cambiar estado
$resultado = $controller->cambiarEstado($id, $estado);

// Responder según el tipo de solicitud
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Respuesta AJAX
    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
} else {
    // Respuesta normal
    if ($resultado['success']) {
        $_SESSION['mensaje'] = $resultado['message'];
        $_SESSION['icono'] = 'success';
    } else {
        $_SESSION['mensaje'] = $resultado['message'];
        $_SESSION['icono'] = 'error';
    }

    // Redirigir a la página anterior o a la lista
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ' . $URL . 'views/habitaciones/index.php');
    }
    exit;
}

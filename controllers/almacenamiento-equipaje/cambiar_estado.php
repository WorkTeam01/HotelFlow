<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/AlmacenamientoEquipajeController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Obtener los parámetros de la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$nuevo_estado = isset($_GET['nuevo_estado']) ? $_GET['nuevo_estado'] : null;

// Validar los parámetros
if ($id === null || $nuevo_estado === null) {
    $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del equipaje.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/index.php');
    exit;
}

// Instanciar el controlador
$controller = new AlmacenamientoEquipajeController();

// Procesar el cambio de estado
$resultado = $controller->cambiarEstado($id, $nuevo_estado);

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir a la página anterior o a la página de detalles del equipaje
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
if ($referer && strpos($referer, $URL) !== false) {
    header('Location: ' . $referer);
} else {
    header('Location: ' . $URL . 'views/almacenamiento-equipaje/show.php?id=' . $id);
}
exit;

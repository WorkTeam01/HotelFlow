<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/TipoHabitacionController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Instanciar el controlador
$controller = new TipoHabitacionController();

$id_tipo = null;
$estado_actual = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    $id_tipo = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

    if ($id_tipo === false || $estado_actual === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del tipo de habitación.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/tipohabitacion/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstado($id_tipo, $estado_actual);
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/tipohabitacion/index.php');
exit;
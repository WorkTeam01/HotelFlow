<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/ServicioBanoController.php';

// Instanciar el controlador
$controller = new ServicioBanoController();

$id_servicio = null;
$estado_actual = null;
$nuevo_estado = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado_actual']) && isset($_GET['nuevo_estado'])) {
    $id_servicio = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado_actual'], FILTER_SANITIZE_STRING);
    $nuevo_estado = filter_var($_GET['nuevo_estado'], FILTER_SANITIZE_STRING);

    if ($id_servicio === false || $estado_actual === '' || $nuevo_estado === '') {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado del servicio.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/servicios-bano/index.php');
        exit;
    }

    $resultado = $controller->cambiarEstadoServicio($id_servicio, $estado_actual, $nuevo_estado);
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

header('Location: ' . $URL . 'views/servicios-bano/index.php');
exit;

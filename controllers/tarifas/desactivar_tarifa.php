<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/TarifaController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Instanciar el controlador de tarifas
$controller = new TarifaController();

$id_tarifa = null;
$estado_actual = null;

// Validar método GET y parámetros
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['estado'])) {
    // Filtrar y validar los parámetros
    $id_tarifa = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $estado_actual = filter_var($_GET['estado'], FILTER_VALIDATE_INT);

    // Verificar si los parámetros son válidos
    if ($id_tarifa === false || $estado_actual === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado de la tarifa.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/tarifas/index.php');
        exit;
    }

    // Cambiar el estado de la tarifa
    $resultado = $controller->cambiarEstadoTarifa($id_tarifa, $estado_actual);
    
    // Guardar mensaje en sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir al listado de tarifas
header('Location: ' . $URL . 'views/tarifas/index.php');
exit;
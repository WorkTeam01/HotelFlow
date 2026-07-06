<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Incluir el controlador de tarifas
require_once __DIR__ . '/TarifaController.php';

// Instanciar el controlador
$controller = new TarifaController();

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acción no permitida. Se requiere método POST.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/tarifas/index.php');
    exit;
}

// Procesar el formulario de actualización
$resultado = $controller->actualizar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/tarifas/' . $resultado['redirect']);
exit;
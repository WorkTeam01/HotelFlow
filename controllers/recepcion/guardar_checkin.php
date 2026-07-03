<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/RecepcionController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Instanciar el controlador
$controller = new RecepcionController();

// Procesar el formulario
$resultado = $controller->guardar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Si la operación fue exitosa y se generó un ID, redirigir a la página de detalles
if ($resultado['success'] && isset($resultado['id'])) {
    header('Location: ' . $URL . 'views/recepcion/show.php?id=' . $resultado['id']);
} else {
    // Redirigir según el resultado
    header('Location: ' . $URL . 'views/recepcion/' . $resultado['redirect']);
}
exit;

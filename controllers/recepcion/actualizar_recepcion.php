<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/RecepcionController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Instanciar el controlador
$controller = new RecepcionController();

// Procesar el formulario de actualización
$resultado = $controller->actualizar($_POST['idrecepcion'], $_POST);

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
if ($resultado['success']) {
    header('Location: ' . $URL . 'views/recepcion/show.php?id=' . $_POST['idrecepcion']);
} else {
    header('Location: ' . $URL . 'views/recepcion/update.php?id=' . $_POST['idrecepcion']);
}
exit;

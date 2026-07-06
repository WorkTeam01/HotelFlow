<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Incluir el controlador
require_once __DIR__ . '/ServicioBanoController.php';

// Instanciar el controlador
$controller = new ServicioBanoController();

// Procesar el formulario
$resultado = $controller->guardar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/servicios-bano/' . $resultado['redirect']);
exit;

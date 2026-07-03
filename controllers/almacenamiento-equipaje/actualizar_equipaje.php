<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/AlmacenamientoEquipajeController.php';

// Verificar si el usuario está autenticado
requireLogin();

// Instanciar el controlador
$controller = new AlmacenamientoEquipajeController();

// Procesar el formulario de actualización
$resultado = $controller->actualizar();

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
header('Location: ' . $URL . 'views/almacenamiento-equipaje/' . $resultado['redirect']);
exit;

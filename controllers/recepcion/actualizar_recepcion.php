<?php
// Incluir el archivo de sesión para tener acceso a la variable $URL
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador
require_once __DIR__ . '/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['mensaje'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Validar el ID de recepción (nunca pasar $_POST crudo al controlador)
$idrecepcion = (int) ($_POST['idrecepcion'] ?? 0);
if ($idrecepcion <= 0) {
    $_SESSION['mensaje'] = 'ID de recepción no válido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Instanciar el controlador
$controller = new RecepcionController();

// Procesar el formulario: solo datos de estancia (el dinero se gestiona por el folio,
// el estado por cambiar_estado.php/checkout_ajax.php, la habitación por cambiar_habitacion_ajax.php)
$resultado = $controller->actualizarEstancia($idrecepcion, $_POST);

// Guardar mensaje en la sesión
$_SESSION['mensaje'] = $resultado['message'];
$_SESSION['icono'] = $resultado['icon'];

// Redirigir según el resultado
if ($resultado['success']) {
    header('Location: ' . $URL . 'views/recepcion/show.php?id=' . $idrecepcion);
} else {
    header('Location: ' . $URL . 'views/recepcion/update.php?id=' . $idrecepcion);
}
exit;

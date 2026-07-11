<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/PersonaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

// Verificar si el usuario está autenticado
requireLogin();

// Verificar permisos
$idusuario_sesion = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario_sesion) && !$auth->puedeAccederModulo($idusuario_sesion, 'personas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/clientes/index.php');
    exit;
}

// Instanciar el controlador de Persona
$controller = new PersonaController();

$id_persona = null;

// Verificar método GET, parámetros y token CSRF
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['csrf_token']) && verifyCSRFToken($_GET['csrf_token'])) {
    // Validar y filtrar los parámetros
    $id_persona = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // Verificar si los datos son válidos
    if ($id_persona === false) {
        $_SESSION['mensaje'] = 'Datos inválidos para cambiar el estado de la persona.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . 'views/clientes/index.php');
        exit;
    }

    // Cambiar el estado de la persona
    $resultado = $controller->cambiarEstado($id_persona);
    
    // Establecer mensaje de sesión
    $_SESSION['mensaje'] = $resultado['message'];
    $_SESSION['icono'] = $resultado['icon'];
} else {
    // Acceso no permitido
    $_SESSION['mensaje'] = 'Acción no permitida.';
    $_SESSION['icono'] = 'warning';
}

// Redirigir a la lista de personas
header('Location: ' . $URL . 'views/clientes/index.php');
exit;
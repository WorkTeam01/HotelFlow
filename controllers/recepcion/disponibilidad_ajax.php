<?php

/**
 * Endpoint AJAX (solo lectura): comprueba si una habitación está libre en un rango
 * de fechas antes de crear la reserva. Devuelve JSON { disponible: bool }.
 * La validación autoritativa vuelve a correr dentro de Recepcion::crear() (FOR UPDATE);
 * esto solo alimenta el aviso temprano en la UI.
 */

require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso no permitido');
}

requireLogin();
header('Content-Type: application/json');

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'recepcion')) {
    echo json_encode(['disponible' => false, 'error' => 'Sin permisos']);
    exit;
}

$idhabitacion = (int) ($_GET['idhabitacion'] ?? 0);
$entrada = str_replace('T', ' ', trim($_GET['fechaentrada'] ?? ''));
$salida = str_replace('T', ' ', trim($_GET['fechasalida_prevista'] ?? ''));
$excluir = isset($_GET['excluir']) ? (int) $_GET['excluir'] : null;

if ($idhabitacion <= 0 || $entrada === '' || $salida === '' || strtotime($salida) <= strtotime($entrada)) {
    echo json_encode(['disponible' => false, 'error' => 'Datos incompletos']);
    exit;
}

$controller = new RecepcionController();
$solapa = $controller->modelo->existeSolape($idhabitacion, $entrada, $salida, $excluir);

echo json_encode(['disponible' => !$solapa]);
exit;

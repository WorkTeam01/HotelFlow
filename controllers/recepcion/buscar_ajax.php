<?php

/**
 * Endpoint AJAX: búsqueda global de reserva/huésped para el Select2 remoto del módulo.
 * Devuelve JSON { results: [{ id, text, url }] } consumible directamente por Select2.
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
    echo json_encode(['results' => []]);
    exit;
}

$termino = trim($_GET['q'] ?? '');
$controller = new RecepcionController();
$filas = $controller->buscar($termino, 20);

$base = rtrim($GLOBALS['URL'] ?? '', '/') . '/';
$results = [];
foreach ($filas as $f) {
    $estado = RecepcionController::estadoRecepcion($f['estado']);
    $texto = '#' . $f['idrecepcion'] . ' · ' . ($f['huesped'] ?: 'Sin huésped')
        . ' · Hab. ' . ($f['numero_habitacion'] ?: 'N/A')
        . ' · ' . $estado['label'];
    $results[] = [
        'id' => (int) $f['idrecepcion'],
        'text' => $texto,
        'url' => $base . 'views/recepcion/show.php?id=' . (int) $f['idrecepcion'],
    ];
}

echo json_encode(['results' => $results]);
exit;

<?php
require_once __DIR__ . '/../../views/layouts/session.php';
require_once __DIR__ . '/VentaController.php';

// Instanciar el controlador de ventas
$controller = new VentaController();

// Validar parámetros de fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Validar formato de fechas
if (!DateTime::createFromFormat('Y-m-d', $fecha_inicio) || !DateTime::createFromFormat('Y-m-d', $fecha_fin)) {
    $_SESSION['mensaje'] = 'Formato de fecha inválido. Use YYYY-MM-DD.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Obtener reporte de ventas
$ventas = $controller->obtenerPorRangoFechas($fecha_inicio, $fecha_fin);

// Incluir vista de reporte
require_once __DIR__ . '/../../views/ventas/reporte.php';
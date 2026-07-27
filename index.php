<?php

/**
 * Dashboard Principal - HotelFlow
 * 
 * Este archivo muestra el dashboard principal con las estadísticas
 * y herramientas principales del sistema.
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir los archivos necesarios
require_once 'views/layouts/session.php';
require_once 'controllers/dashboard/DashboardController.php';

// Verificar autenticación y rol
requireLogin();

// Si el usuario no es administrador, redirigir según su rol
$currentUser = getCurrentUser();
$rol = $currentUser['cargo'];

// Instanciar el controlador del dashboard
$dashboardController = new DashboardController();

// Obtener estadísticas según el rol del usuario
if ($rol == 'Administrador') {
    $stats = $dashboardController->getEstadisticasAdmin();
    // Definir recursos específicos del módulo
    $module_styles = ['dashboard/dashboard-admin'];
    $module_scripts = ['dashboard/dashboard-admin'];
} elseif ($rol == 'Recepcionista') {
    $stats = $dashboardController->getEstadisticasRecepcionista();
    $skip_chartjs = true;
} elseif ($rol == 'Limpieza') {
    $stats = $dashboardController->getEstadisticasLimpieza($currentUser['id']);
    $skip_chartjs = true;
} else {
    $stats = $dashboardController->getEstadisticasBasicas();
    $skip_chartjs = true;
}
$skip_select2 = true;
$skip_datatables = true;

// Incluir el header
include 'views/layouts/header.php';
?>

<!-- Contenido principal -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Panel de Control</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php
        // Cargar el dashboard específico según el rol del usuario
        if ($rol == 'Administrador') {
            include 'views/dashboard/administrador_dashboard.php';
        } elseif ($rol == 'Recepcionista') {
            include 'views/dashboard/recepcionista_dashboard.php';
        } elseif ($rol == 'Limpieza') {
            include 'views/dashboard/limpieza_dashboard.php';
        } else {
            include 'views/dashboard/default_dashboard.php';
        }
        ?>
    </div>
</section>

<!-- Script para inicializar componentes JS -->
<script>
    $(document).ready(function() {
        // Inicializar tooltips
        initializeTooltips();

        // Inicializar Select2 en elementos select
        initializeSelect2();
    });
</script>

<?php
include 'views/layouts/mensajes.php';
include 'views/layouts/footer.php';
?>
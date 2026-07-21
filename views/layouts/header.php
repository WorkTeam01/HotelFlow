<?php
// Incluir el archivo de sesión
require_once __DIR__ . '/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Obtener datos del usuario actual
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

// Incluir el servicio de autorización
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();

// Usar la variable global URL
global $URL;

?>

<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        const APP_NAME = '<?= $APP_NAME; ?>';
        const CSRF_TOKEN = '<?= generateCSRFToken(); ?>';
        const BASE_URL = '<?= $URL; ?>';
    </script>
    <title><?= $APP_NAME; ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/bootstrap/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/adminlte/adminlte.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/styles.css">
    <!-- Font Awesome Webfonts -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/webfonts.css">
    <link rel="icon" type="image/png" href="<?= $URL; ?>public/img/hotel.png">
    <!-- Datatables -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/datatables.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/buttons.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2-bootstrap4.min.css">
    <!-- Sweetalert2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= $URL; ?>public/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <!-- jQuery -->
    <script src="<?= $URL; ?>public/js/lib/jquery/jquery.min.js"></script>
    <!-- ChartJS -->
    <script src="<?= $URL; ?>public/js/plugins/chart/Chart.js"></script>

    <!-- Estilos específicos por módulo -->
    <?php if (isset($module_styles) && is_array($module_styles)): ?>
        <?php foreach ($module_styles as $style): ?>
            <link rel="stylesheet" href="<?= $URL; ?>public/css/modules/<?= $style; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= $URL; ?>" class="nav-link">Sistema de Gestión</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-user"></i>
                    </a>

                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'perfil')) : ?>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <!-- User image -->
                            <li class="user-header">
                                <img src="<?= $URL; ?>public/uploads/usuarios/<?= $currentUser['imagen']; ?>" loading="eager" class="img-circle elevation-2" alt="User Image">
                                <p>
                                    <?= $currentUser['nombre']; ?>
                                    <small><?= $currentUser['cargo']; ?></small>
                                </p>
                            </li>
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <a href="<?= $URL; ?>views/usuarios/perfil.php?id=<?= $currentUser['id']; ?>" class="btn btn-default btn-flat">Perfil</a>
                                <a href="<?= $URL; ?>controllers/auth/logout.php" class="btn btn-default btn-flat float-right">Cerrar Sesión</a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-2">
            <!-- Brand Logo -->
            <a href="<?= $URL; ?>" class="brand-link">
                <img src="<?= $URL; ?>public/img/bunk-bed.png" loading="eager" alt="AdminLTE Logo" class="brand-image img-circle elevation-2" style="opacity: .8">
                <span class="brand-text font-weight-light"><?= $APP_NAME; ?></span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">

                <!-- Sidebar Menu -->
                <nav class="mt-4">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="<?= $URL; ?>" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- Recepción -->
                        <?php if ($authService->puedeAccederModulo($idusuariosesion, 'recepcion') || $authService->puedeAccederModulo($idusuariosesion, 'habitaciones')) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-concierge-bell"></i>
                                    <p>
                                        Recepción
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">

                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'recepcion')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/recepcion" class="nav-link">
                                                <i class="fas fa-home-user nav-icon"></i>
                                                <p>Panel de recepciones</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/recepcion/create.php" class="nav-link">
                                                <i class="fas fa-plus nav-icon"></i>
                                                <p>Nueva Reserva/Check-in</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/recepcion/lista-recepciones.php" class="nav-link">
                                                <i class="fas fa-list nav-icon"></i>
                                                <p>Historial de recepciones</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Habitaciones -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'habitaciones')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/habitaciones" class="nav-link">
                                                <i class="fas fa-bed nav-icon"></i>
                                                <p>Estado de habitaciones</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Servicios -->
                        <?php if (
                            $authService->puedeAccederModulo($idusuariosesion, 'servicios_bano') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'banos') ||
                            $authService->tienePermisoNombre($idusuariosesion, 'equipajes') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'limpieza')
                        ) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-hands-helping"></i>
                                    <p>
                                        Servicios
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Servicios de baño -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'servicios_bano')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/servicios-bano" class="nav-link">
                                                <i class="fas fa-shower nav-icon"></i>
                                                <p>Servicios de baño</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Equipaje -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'equipajes')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="nav-link">
                                                <i class="fas fa-luggage-cart nav-icon"></i>
                                                <p>Almacenamiento equipaje</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Limpieza -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'limpieza')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/limpieza" class="nav-link">
                                                <i class="fas fa-broom nav-icon"></i>
                                                <p>Limpieza de habitaciones</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Inventario y Ventas -->
                        <?php if (
                            $authService->puedeAccederModulo($idusuariosesion, 'productos') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'categorias') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'nueva_venta') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'ventas') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'compras') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'nueva_compra') ||
                            $authService->puedeAccederModulo($idusuariosesion, 'clientes')
                        ) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-store"></i>
                                    <p>
                                        Inventario y Ventas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Productos -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'productos')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/productos" class="nav-link">
                                                <i class="fas fa-box-open nav-icon"></i>
                                                <p>Productos</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'categorias')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/categorias" class="nav-link">
                                                <i class="fas fa-list-alt nav-icon"></i>
                                                <p>Categorías</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Ventas -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'nueva_venta')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/ventas/nueva.php" class="nav-link">
                                                <i class="fas fa-cash-register nav-icon"></i>
                                                <p>Nueva venta</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'ventas')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/ventas" class="nav-link">
                                                <i class="fas fa-receipt nav-icon"></i>
                                                <p>Historial de ventas</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>controllers/ventas/reporte_ventas.php" class="nav-link">
                                                <i class="fas fa-chart-bar nav-icon"></i>
                                                <p>Reporte de ventas</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Clientes -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'clientes')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/clientes" class="nav-link">
                                                <i class="fas fa-user-friends nav-icon"></i>
                                                <p>Lista de clientes</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Compras -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'compras')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/compras" class="nav-link">
                                                <i class="fas fa-truck-loading nav-icon"></i>
                                                <p>Compras e ingresos</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Nueva Compra -->
                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'nueva_compra')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/compras/ingresar.php" class="nav-link">
                                                <i class="fas fa-shopping-cart nav-icon"></i>
                                                <p>Nueva compra</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Configuración -->
                        <?php if (
                            $authService->esAdministrador($idusuariosesion) ||
                            $authService->puedeAccederModulo($idusuariosesion, 'banos') ||
                            $authService->tienePermisoNombre($idusuariosesion, 'precios_equipaje')
                        ) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>
                                        Configuración
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <?php if ($authService->esAdministrador($idusuariosesion)): ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/pisos" class="nav-link">
                                                <i class="fas fa-building nav-icon"></i>
                                                <p>Pisos</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/tipohabitacion" class="nav-link">
                                                <i class="fas fa-tags nav-icon"></i>
                                                <p>Tipos de habitación</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/tarifas" class="nav-link">
                                                <i class="fas fa-money-bill nav-icon"></i>
                                                <p>Tarifas</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($authService->puedeAccederModulo($idusuariosesion, 'banos')): ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/banos" class="nav-link">
                                                <i class="fas fa-toilet nav-icon"></i>
                                                <p>Baños</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'precios_equipaje')): ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/precios-equipaje" class="nav-link">
                                                <i class="fas fa-dollar-sign nav-icon"></i>
                                                <p>Precios de equipaje</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Administración -->
                        <?php if ($authService->puedeAccederModulo($idusuariosesion, 'usuarios') && $authService->esAdministrador($idusuariosesion)) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-user-shield"></i>
                                    <p>
                                        Administración
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= $URL; ?>views/usuarios" class="nav-link">
                                            <i class="fas fa-user-alt nav-icon"></i>
                                            <p>Lista de usuarios</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= $URL; ?>views/usuarios/create.php" class="nav-link">
                                            <i class="fas fa-user-plus nav-icon"></i>
                                            <p>Crear usuarios</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
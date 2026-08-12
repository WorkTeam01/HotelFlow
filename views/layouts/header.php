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
    <link rel="icon" type="image/svg+xml" href="<?= $URL; ?>public/img/hotel-logo.svg">
    <?php
    // $skip_datatables: opt-out para vistas sin tabla (ver mismo condicional en footer.php).
    $cargar_datatables = !(isset($skip_datatables) && $skip_datatables === true);
    ?>
    <?php if ($cargar_datatables): ?>
        <!-- Datatables -->
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/datatables.min.css">
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/dataTables.bootstrap4.min.css">
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/responsive.bootstrap4.min.css">
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/buttons.bootstrap4.min.css">
    <?php endif; ?>
    <?php
    // $skip_select2 / $skip_chartjs: opt-out para vistas que no usan estas librerías.
    $cargar_select2 = !(isset($skip_select2) && $skip_select2 === true);
    $cargar_chartjs = !(isset($skip_chartjs) && $skip_chartjs === true);
    ?>
    <?php if ($cargar_select2): ?>
        <!-- Select2 -->
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2.min.css">
        <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2-bootstrap4.min.css">
    <?php endif; ?>
    <!-- Sweetalert2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= $URL; ?>public/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <!-- jQuery -->
    <script src="<?= $URL; ?>public/js/lib/jquery/jquery.min.js"></script>
    <?php if ($cargar_chartjs): ?>
        <!-- ChartJS -->
        <script src="<?= $URL; ?>public/js/plugins/chart/Chart.js"></script>
    <?php endif; ?>

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

        <?php require __DIR__ . '/sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
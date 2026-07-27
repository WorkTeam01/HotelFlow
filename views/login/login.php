<?php
// Incluir archivo de sesión
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si ya hay una sesión activa
if (isAuthenticated()) {
    header('Location: ../../index.php');
    exit;
}

// Incluir la configuración
require_once __DIR__ . '/../../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $APP_NAME; ?> | Iniciar Sesión</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/adminlte/adminlte.min.css">
    <!-- Font Awesome Webfonts -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/webfonts.css">
    <link rel="icon" type="image/svg+xml" href="<?= $URL; ?>public/img/hotel-logo.svg">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Custom login styles -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/modules/login/login.css">
    <!-- Sweetalert2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= $URL; ?>public/js/plugins/sweetalert2/sweetalert2.min.js"></script>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="<?= $URL; ?>public/img/hotel-logo.svg" alt="Logo" class="img-circle" width="100" height="100">
        </div>
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1 class="h3"><?= $APP_NAME; ?></h1>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Ingrese sus credenciales para acceder</p>

                <form action="<?= $URL; ?>controllers/auth/login.php" method="post" id="login-form" novalidate>
                    <!-- Token CSRF para protección contra CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="input-group mb-1">
                        <label for="identifier-field" class="sr-only">Email o número de documento</label>
                        <input type="text" name="identifier" id="identifier-field" class="form-control" placeholder="Email o Número de documento"
                            autocomplete="username" required aria-describedby="identifier-error">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                    <div id="identifier-error" class="invalid-feedback" role="alert"></div>

                    <div class="input-group mb-1">
                        <label for="password-field" class="sr-only">Contraseña</label>
                        <input type="password" name="clave" id="password-field" class="form-control" placeholder="Contraseña"
                            autocomplete="current-password" required aria-describedby="password-error">
                        <div class="input-group-append">
                            <button type="button" class="input-group-text password-toggle" data-target="#password-field"
                                title="Mostrar contraseña" aria-label="Mostrar contraseña" aria-pressed="false">
                                <span class="fas fa-eye toggle-password-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div id="password-error" class="invalid-feedback" role="alert"></div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer text-center mt-3">
            <p>&copy; <?= date('Y'); ?> <?= $APP_NAME; ?>. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="<?= $URL; ?>public/js/lib/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= $URL; ?>public/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= $URL; ?>public/js/lib/adminlte/adminlte.min.js"></script>
    <!-- Utilidades comunes (incluye el toggle centralizado de mostrar/ocultar contraseña) -->
    <script src="<?= $URL; ?>public/js/core/common-utils.js"></script>

    <!-- Custom login script -->
    <script src="<?= $URL; ?>public/js/modules/login/login.js"></script>

    <?php
    // Incluir mensajes
    require_once __DIR__ . '/../layouts/mensajes.php';
    ?>
</body>

</html>
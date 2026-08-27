<?php

/**
 * Sidebar principal.
 *
 * Requiere que el include ya haya definido $URL, $authService e $idusuariosesion
 * (ver views/layouts/header.php, que es el único punto de inclusión).
 */

if (!function_exists('sidebarNormalizarRuta')) {
    function sidebarNormalizarRuta(string $ruta): string
    {
        $ruta = parse_url($ruta, PHP_URL_PATH) ?? '';
        $ruta = rtrim($ruta, '/');
        if (substr($ruta, -10) === '/index.php') {
            $ruta = substr($ruta, 0, -10);
        }
        return $ruta;
    }
}

if (!function_exists('sidebarEsActivo')) {
    function sidebarEsActivo(string $href, string $rutaActual): bool
    {
        return sidebarNormalizarRuta($href) === $rutaActual;
    }
}

if (!function_exists('sidebarClaseActiva')) {
    function sidebarClaseActiva(string $href, string $rutaActual): string
    {
        return sidebarEsActivo($href, $rutaActual) ? ' active' : '';
    }
}

$sidebarRutaActual = sidebarNormalizarRuta($_SERVER['REQUEST_URI'] ?? '');
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-primary elevation-2">
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
                    <a href="<?= $URL; ?>" class="nav-link<?= sidebarClaseActiva($URL, $sidebarRutaActual); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Recepción -->
                <?php if ($authService->puedeAccederModulo($idusuariosesion, 'recepcion') || $authService->puedeAccederModulo($idusuariosesion, 'habitaciones')) : ?>
                    <?php
                    $sidebarGrupoRecepcionActivo = sidebarEsActivo($URL . 'views/recepcion', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/recepcion/create.php', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/habitaciones', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoRecepcionActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoRecepcionActivo ? ' active' : ''; ?>">
                            <i class="nav-icon fas fa-concierge-bell"></i>
                            <p>
                                Recepción
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">

                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'recepcion')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/recepcion" class="nav-link<?= sidebarClaseActiva($URL . 'views/recepcion', $sidebarRutaActual); ?>">
                                        <i class="fas fa-home-user nav-icon"></i>
                                        <p>Panel de recepciones</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/recepcion/create.php" class="nav-link<?= sidebarClaseActiva($URL . 'views/recepcion/create.php', $sidebarRutaActual); ?>">
                                        <i class="fas fa-plus nav-icon"></i>
                                        <p>Nueva Reserva/Check-in</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Habitaciones -->
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'habitaciones')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/habitaciones" class="nav-link<?= sidebarClaseActiva($URL . 'views/habitaciones', $sidebarRutaActual); ?>">
                                        <i class="fas fa-bed nav-icon"></i>
                                        <p>Estado de habitaciones</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Housekeeping / Limpieza -->
                <?php if ($authService->puedeAccederModulo($idusuariosesion, 'limpieza')) : ?>
                    <li class="nav-item">
                        <a href="<?= $URL; ?>views/limpieza" class="nav-link<?= sidebarClaseActiva($URL . 'views/limpieza', $sidebarRutaActual); ?>">
                            <i class="nav-icon fas fa-broom"></i>
                            <p>Limpieza</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Servicios al huésped -->
                <?php if (
                    $authService->puedeAccederModulo($idusuariosesion, 'servicios_bano') ||
                    $authService->tienePermisoNombre($idusuariosesion, 'equipajes')
                ) : ?>
                    <?php
                    $sidebarGrupoServiciosActivo = sidebarEsActivo($URL . 'views/servicios-bano', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/almacenamiento-equipaje', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoServiciosActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoServiciosActivo ? ' active' : ''; ?>">
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
                                    <a href="<?= $URL; ?>views/servicios-bano" class="nav-link<?= sidebarClaseActiva($URL . 'views/servicios-bano', $sidebarRutaActual); ?>">
                                        <i class="fas fa-shower nav-icon"></i>
                                        <p>Servicios de baño</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Equipaje -->
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'equipajes')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="nav-link<?= sidebarClaseActiva($URL . 'views/almacenamiento-equipaje', $sidebarRutaActual); ?>">
                                        <i class="fas fa-luggage-cart nav-icon"></i>
                                        <p>Almacenamiento equipaje</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Inventario y Compras -->
                <?php if (
                    $authService->puedeAccederModulo($idusuariosesion, 'productos') ||
                    $authService->puedeAccederModulo($idusuariosesion, 'categorias') ||
                    $authService->esAdministrador($idusuariosesion) ||
                    $authService->puedeAccederModulo($idusuariosesion, 'compras')
                ) : ?>
                    <?php
                    $sidebarGrupoInventarioActivo = sidebarEsActivo($URL . 'views/productos', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/categorias', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/compras/create.php', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/compras', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoInventarioActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoInventarioActivo ? ' active' : ''; ?>">
                            <i class="nav-icon fas fa-warehouse"></i>
                            <p>
                                Inventario y Compras
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'productos')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/productos" class="nav-link<?= sidebarClaseActiva($URL . 'views/productos', $sidebarRutaActual); ?>">
                                        <i class="fas fa-box-open nav-icon"></i>
                                        <p>Productos</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'categorias')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/categorias" class="nav-link<?= sidebarClaseActiva($URL . 'views/categorias', $sidebarRutaActual); ?>">
                                        <i class="fas fa-list-alt nav-icon"></i>
                                        <p>Categorías</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($authService->esAdministrador($idusuariosesion) || $authService->puedeAccederModulo($idusuariosesion, 'compras')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/compras/create.php" class="nav-link<?= sidebarClaseActiva($URL . 'views/compras/create.php', $sidebarRutaActual); ?>">
                                        <i class="fas fa-shopping-cart nav-icon"></i>
                                        <p>Nueva compra</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'compras')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/compras" class="nav-link<?= sidebarClaseActiva($URL . 'views/compras', $sidebarRutaActual); ?>">
                                        <i class="fas fa-truck-loading nav-icon"></i>
                                        <p>Compras e ingresos</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Ventas -->
                <?php if (
                    $authService->esAdministrador($idusuariosesion) ||
                    $authService->puedeAccederModulo($idusuariosesion, 'ventas') ||
                    $authService->puedeAccederModulo($idusuariosesion, 'clientes')
                ) : ?>
                    <?php
                    $sidebarGrupoVentasActivo = sidebarEsActivo($URL . 'views/ventas/create.php', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/ventas', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'controllers/ventas/reporte_ventas.php', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/clientes', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoVentasActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoVentasActivo ? ' active' : ''; ?>">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>
                                Ventas
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if ($authService->esAdministrador($idusuariosesion) || $authService->puedeAccederModulo($idusuariosesion, 'ventas')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/ventas/create.php" class="nav-link<?= sidebarClaseActiva($URL . 'views/ventas/create.php', $sidebarRutaActual); ?>">
                                        <i class="fas fa-cash-register nav-icon"></i>
                                        <p>Nueva venta</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'ventas')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/ventas" class="nav-link<?= sidebarClaseActiva($URL . 'views/ventas', $sidebarRutaActual); ?>">
                                        <i class="fas fa-receipt nav-icon"></i>
                                        <p>Historial de ventas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>controllers/ventas/reporte_ventas.php" class="nav-link<?= sidebarClaseActiva($URL . 'controllers/ventas/reporte_ventas.php', $sidebarRutaActual); ?>">
                                        <i class="fas fa-chart-bar nav-icon"></i>
                                        <p>Reporte de ventas</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'clientes')) : ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/clientes" class="nav-link<?= sidebarClaseActiva($URL . 'views/clientes', $sidebarRutaActual); ?>">
                                        <i class="fas fa-user-friends nav-icon"></i>
                                        <p>Lista de clientes</p>
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
                    <?php
                    $sidebarGrupoConfiguracionActivo = sidebarEsActivo($URL . 'views/pisos', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/tipohabitacion', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/tarifas', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/banos', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/precios-equipaje', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoConfiguracionActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoConfiguracionActivo ? ' active' : ''; ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Configuración
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if ($authService->esAdministrador($idusuariosesion)): ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/pisos" class="nav-link<?= sidebarClaseActiva($URL . 'views/pisos', $sidebarRutaActual); ?>">
                                        <i class="fas fa-building nav-icon"></i>
                                        <p>Pisos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/tipohabitacion" class="nav-link<?= sidebarClaseActiva($URL . 'views/tipohabitacion', $sidebarRutaActual); ?>">
                                        <i class="fas fa-tags nav-icon"></i>
                                        <p>Tipos de habitación</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/tarifas" class="nav-link<?= sidebarClaseActiva($URL . 'views/tarifas', $sidebarRutaActual); ?>">
                                        <i class="fas fa-money-bill nav-icon"></i>
                                        <p>Tarifas</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($authService->puedeAccederModulo($idusuariosesion, 'banos')): ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/banos" class="nav-link<?= sidebarClaseActiva($URL . 'views/banos', $sidebarRutaActual); ?>">
                                        <i class="fas fa-toilet nav-icon"></i>
                                        <p>Baños</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'precios_equipaje')): ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/precios-equipaje" class="nav-link<?= sidebarClaseActiva($URL . 'views/precios-equipaje', $sidebarRutaActual); ?>">
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
                    <?php
                    $sidebarGrupoAdministracionActivo = sidebarEsActivo($URL . 'views/usuarios', $sidebarRutaActual)
                        || sidebarEsActivo($URL . 'views/usuarios/create.php', $sidebarRutaActual);
                    ?>
                    <li class="nav-item<?= $sidebarGrupoAdministracionActivo ? ' menu-open' : ''; ?>">
                        <a href="#" class="nav-link<?= $sidebarGrupoAdministracionActivo ? ' active' : ''; ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>
                                Administración
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= $URL; ?>views/usuarios" class="nav-link<?= sidebarClaseActiva($URL . 'views/usuarios', $sidebarRutaActual); ?>">
                                    <i class="fas fa-user-alt nav-icon"></i>
                                    <p>Lista de usuarios</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= $URL; ?>views/usuarios/create.php" class="nav-link<?= sidebarClaseActiva($URL . 'views/usuarios/create.php', $sidebarRutaActual); ?>">
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
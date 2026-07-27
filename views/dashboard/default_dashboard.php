<!-- Indicadores clave para Dashboard por Defecto -->
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= $stats['servicios_bano']['total_hoy'] ?? 0 ?></h3>
                <p>Servicios de Baño Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-shower"></i>
            </div>
            <a href="<?= $URL; ?>views/servicios-bano" class="small-box-footer">
                Ver servicios <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $stats['equipajes']['activos'] ?? 0 ?></h3>
                <p>Equipajes Almacenados</p>
            </div>
            <div class="icon">
                <i class="fas fa-luggage-cart"></i>
            </div>
            <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="small-box-footer">
                Ver equipajes <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $stats['productos']['total'] ?? 0 ?></h3>
                <p>Productos Registrados</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <a href="<?= $URL; ?>views/productos" class="small-box-footer">
                Ver productos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Información del sistema -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Información del Sistema
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-success"><i class="fas fa-user"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Usuario Actual</span>
                                <span class="info-box-number"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                                <span class="text-muted">Rol: <?= htmlspecialchars($_SESSION['usuario_cargo']) ?></span>
                            </div>
                        </div>

                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Último Acceso</span>
                                <span class="info-box-number">
                                    <?= date('d/m/Y H:i:s', $_SESSION['ultimo_acceso']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info"></i> Información:</h5>
                            <p>Bienvenido al <?= $APP_NAME; ?>. Este sistema le permite gestionar servicios de baño, almacenamiento de equipaje, productos y más.</p>
                            <p>Si necesita ayuda o encuentra algún problema, comuníquese con el administrador del sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accesos rápidos para todos los usuarios -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Accesos Rápidos
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/servicios-bano" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-shower"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Servicios de Baño</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-luggage-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Equipajes</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/productos" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Productos</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <a href="<?= $URL; ?>views/usuarios/perfil.php?id=<?= $_SESSION['usuario_id'] ?>" class="text-dark">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-user-cog"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Mi Perfil</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
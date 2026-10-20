<?php
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];

$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'servicios_bano')) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['servicios-bano/servicios-bano'];
$module_scripts = ['servicios-bano/index-servicios-bano', 'servicios-bano/servicio-bano-rapido', 'servicios-bano/cambiar-estado-servicios-bano'];
include_once '../layouts/header.php';

$controller = new ServicioBanoController();
$servicios = $controller->index();
$estadisticas = $controller->getEstadisticas();

$disponibilidad = $controller->validarDisponibilidadServicio();

// Rack de baños: libres, en uso (con minutos) y fuera de operación
$banos = $controller->getBanosConOcupacion();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Servicios de Baños</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Servicios de Baños</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content servicios-bano-pagina">
    <div class="container-fluid">
        <?php require __DIR__ . '/partials/kpi-servicios.php'; ?>

        <?php require __DIR__ . '/partials/rack-banos.php'; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Servicios de Baños</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir el listado">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaServicios" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Baño</th>
                                        <th>Cliente</th>
                                        <th>Cobro</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicios as $servicio) :
                                        $ui = $servicio['estado_ui'];
                                        $acciones = $controller->accionesEstado($servicio);
                                        $duracion = $servicio['minutos_en_uso'] ?? $servicio['duracion_minutos'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= (int)$servicio['idservicio']; ?></td>
                                            <td><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($servicio['cliente_label']); ?></td>
                                            <td><?= htmlspecialchars($servicio['cobro_label']); ?></td>
                                            <td>Bs. <?= number_format((float)($servicio['total'] ?? 0), 2); ?></td>
                                            <td><?= !empty($servicio['fecha']) ? date('d/m/Y H:i', strtotime($servicio['fecha'])) : 'N/A'; ?></td>
                                            <td><?= $duracion !== null ? (int)$duracion . ' min' : '—'; ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $ui['badge']; ?>">
                                                    <i class="fas fa-<?= $ui['icono']; ?>" aria-hidden="true"></i> <?= $ui['label']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/servicios-bano/show.php?id=<?= (int)$servicio['idservicio']; ?>"
                                                        class="btn btn-touch btn-info btn-sm" aria-label="Ver detalle" title="Ver detalle">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <?php if (ServicioBanoController::esEditable($servicio)): ?>
                                                        <a href="<?= $URL; ?>views/servicios-bano/update.php?id=<?= (int)$servicio['idservicio']; ?>"
                                                            class="btn btn-touch btn-warning btn-sm" aria-label="Editar observaciones" title="Editar observaciones">
                                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php $variante = 'fila';
                                                    require __DIR__ . '/partials/acciones-estado.php'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
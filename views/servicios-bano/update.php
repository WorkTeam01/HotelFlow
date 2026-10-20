<?php
// Incluir el encabezado
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de servicio de baño no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Verificar si el usuario tiene permiso para editar servicios de baño
$auth = new AuthorizationService();

if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'servicios_bano')) {
    $_SESSION['mensaje'] = 'No tiene permisos para editar servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

$controller = new ServicioBanoController();
$datos = $controller->obtener($id);

if (!$datos) {
    $_SESSION['mensaje'] = 'Servicio de baño no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

if ($datos['servicio']['estado'] === 'cancelado') {
    $_SESSION['mensaje'] = 'Un servicio cancelado no se puede editar.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/servicios-bano/show.php?id=' . $id);
    exit;
}

// Agregar los estilos específicos para este módulo
$module_styles = ['servicios-bano/servicios-bano'];

$module_scripts = ['servicios-bano/update-servicios-bano'];

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

$servicio = $datos['servicio'];

$ui = $servicio['estado_ui'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Editar Servicio de Baño</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano"><i class="fas fa-bath" aria-hidden="true"></i> Servicios de Baño</a></li>
                    <li class="breadcrumb-item active">Editar Servicio</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content servicios-bano-pagina">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <!-- form start -->
                <form action="<?= $URL; ?>controllers/servicios-bano/actualizar_servicio.php" method="POST" id="formEditarServicio">
                    <input type="hidden" name="idservicio" value="<?= $servicio['idservicio']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <div class="card card-outline card-<?= $ui['clase']; ?>">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-edit" aria-hidden="true"></i> Formulario de Edición de Servicio de Baño</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <!-- Estado actual del servicio -->
                            <div class="mb-3 text-center">
                                <span class="badge <?= $ui['badge']; ?>">
                                    <i class="fas fa-<?= $ui['icono']; ?>" aria-hidden="true"></i> Estado actual: <?= $ui['label']; ?>
                                </span>
                            </div>

                            <!-- Datos cobrados (solo lectura): el dinero se corrige cancelando el servicio -->
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Baño</b> <span class="float-right"><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Cliente</b>
                                    <span class="float-right">
                                        <?= htmlspecialchars($servicio['tipo_cliente'] === 'Publico' ? 'Cliente público' : ($servicio['nombre_cliente'] ?? 'N/A')); ?>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <b>Cobro</b>
                                    <span class="float-right">
                                        <?= $servicio['modalidad_cobro'] === 'folio' ? 'Cargado al folio · Hab. ' . htmlspecialchars($servicio['numero_habitacion'] ?? 'N/A') : htmlspecialchars($servicio['metodopago'] ?? 'N/A'); ?>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <b>Total cobrado</b> <span class="float-right">Bs. <?= number_format((float)$servicio['total'], 2); ?></span>
                                </li>
                            </ul>

                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <input type="text" class="form-control" id="observaciones" name="observaciones"
                                    maxlength="255" value="<?= htmlspecialchars($servicio['observaciones'] ?? ''); ?>">
                            </div>

                            <!-- El estado del servicio no se edita aquí: use el botón de acción
                                 en el listado (Finalizar / Cancelar) para cambiarlo. -->

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->

                    <!-- Información adicional (solo lectura) -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Información del Sistema</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir la información del sistema">
                                            <i class="fas fa-minus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-calendar-alt" aria-hidden="true"></i> Fecha de Servicio:</strong>
                                            <p class="text-muted"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-user" aria-hidden="true"></i> Usuario que Registró:</strong>
                                            <p class="text-muted"><?= htmlspecialchars($servicio['nombre_usuario'] ?? 'N/A'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-tag" aria-hidden="true"></i> Estado Actual:</strong>
                                            <p>
                                                <span class="badge <?= $ui['badge']; ?>">
                                                    <i class="fas fa-<?= $ui['icono']; ?>" aria-hidden="true"></i> <?= $ui['label']; ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <div class="row g-1">
                                        <div class="col-12 col-sm-auto">
                                            <button type="submit" class="btn btn-touch btn-warning w-100">
                                                <i class="fas fa-save" aria-hidden="true"></i> Guardar observaciones
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-auto">
                                            <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-touch btn-secondary w-100">
                                                <i class="fas fa-times" aria-hidden="true"></i> Cancelar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- /.col -->

            <div class="col-md-4">
                <!-- Resumen del servicio -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Resumen del Servicio</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir el resumen del servicio">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-bath fa-3x text-info" aria-hidden="true"></i>
                            <h3 class="profile-username text-center mt-2">Servicio #<?= $servicio['idservicio']; ?></h3>
                        </div>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-toilet text-info" aria-hidden="true"></i> Baño</b>
                                <span class="float-right"><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt text-info" aria-hidden="true"></i> Fecha</b>
                                <span class="float-right"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-dollar-sign text-info" aria-hidden="true"></i> Total</b>
                                <span class="float-right"><?= number_format($servicio['total'], 0); ?></span>
                            </li>
                        </ul>

                        <a href="<?= $URL; ?>views/servicios-bano/show.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-touch btn-info btn-block">
                            <i class="fas fa-eye" aria-hidden="true"></i> Ver Detalle Completo
                        </a>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-question-circle" aria-hidden="true"></i> Ayuda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir la ayuda">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5>Instrucciones:</h5>
                            <ul>
                                <li>Solo se pueden editar las observaciones</li>
                                <li>Para corregir un cobro, cancele el servicio y regístrelo de nuevo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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
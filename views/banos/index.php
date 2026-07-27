<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/banos/BanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo ANTES de incluir el header
if (!$auth->puedeAccederModulo($idusuario, 'banos')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
include_once '../layouts/header.php';

$controller = new BanoController();
$banos = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Baños</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Baños</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-toilet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Baños</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Disponibles</span>
                        <span class="info-box-number"><?= $estadisticas['disponibles']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">En Mantenimiento</span>
                        <span class="info-box-number"><?= $estadisticas['mantenimiento']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-ban"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Fuera de Servicio</span>
                        <span class="info-box-number"><?= $estadisticas['fuera_servicio']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h3 class="card-title mb-2 mb-sm-0">Baños registrados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevoBano">
                                    <i class="fas fa-plus"></i> Nuevo Baño
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table id="tablaBanos" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 8%">Nro</th>
                                        <th class="text-center" style="width: 20%">Nombre</th>
                                        <th class="text-center" style="width: 25%">Ubicación</th>
                                        <th class="text-center" style="width: 12%">Precio</th>
                                        <th class="text-center" style="width: 15%">Estado</th>
                                        <th class="text-center" style="width: 20%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($banos as $bano) :
                                        $estado_actual = $bano['estado'];
                                        $clase_estado = '';
                                        $texto_estado = '';

                                        switch ($estado_actual) {
                                            case 'disponible':
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'Disponible';
                                                break;
                                            case 'mantenimiento':
                                                $clase_estado = 'badge-warning';
                                                $texto_estado = 'Mantenimiento';
                                                break;
                                            case 'fuera_servicio':
                                                $clase_estado = 'badge-danger';
                                                $texto_estado = 'Fuera de Servicio';
                                                break;
                                            default:
                                                $clase_estado = 'badge-secondary';
                                                $texto_estado = 'Desconocido';
                                                break;
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($bano['nombre']); ?></td>
                                            <td><?= htmlspecialchars($bano['ubicacion']); ?></td>
                                            <td class="text-right"><?= number_format($bano['precio'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                        data-id="<?= $bano['idbano']; ?>"
                                                        data-nombre="<?= htmlspecialchars($bano['nombre']); ?>"
                                                        data-ubicacion="<?= htmlspecialchars($bano['ubicacion']); ?>"
                                                        data-precio="<?= $bano['precio']; ?>"
                                                        data-estado="<?= $bano['estado']; ?>">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                                                        <i class="fas fa-sync-alt"></i> Estado
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item text-success cambiar-estado" href="#"
                                                            data-id="<?= $bano['idbano']; ?>"
                                                            data-estado="<?= $estado_actual; ?>"
                                                            data-nuevo-estado="disponible"
                                                            data-nombre="<?= htmlspecialchars($bano['nombre']); ?>">
                                                            <i class="fas fa-check-circle"></i> Marcar como Disponible
                                                        </a>
                                                        <a class="dropdown-item text-warning cambiar-estado" href="#"
                                                            data-id="<?= $bano['idbano']; ?>"
                                                            data-estado="<?= $estado_actual; ?>"
                                                            data-nuevo-estado="mantenimiento"
                                                            data-nombre="<?= htmlspecialchars($bano['nombre']); ?>">
                                                            <i class="fas fa-tools"></i> Marcar en Mantenimiento
                                                        </a>
                                                        <a class="dropdown-item text-danger cambiar-estado" href="#"
                                                            data-id="<?= $bano['idbano']; ?>"
                                                            data-estado="<?= $estado_actual; ?>"
                                                            data-nuevo-estado="fuera_servicio"
                                                            data-nombre="<?= htmlspecialchars($bano['nombre']); ?>">
                                                            <i class="fas fa-ban"></i> Marcar Fuera de Servicio
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Unificado para Baños -->
<div class="modal fade" id="modalBano" tabindex="-1" role="dialog" aria-labelledby="modalBanoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalBanoHeader">
                <h5 class="modal-title" id="modalBanoLabel">Gestión de Baño</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formBano" method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                <div class="modal-body">
                    <input type="hidden" id="banoAction" name="action" value="create">
                    <input type="hidden" id="idBano" name="idbano" value="">

                    <div class="form-group">
                        <label for="nombreBano">Nombre del baño <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombreBano" name="nombre" maxlength="50" required>
                        <small class="form-text text-muted">Máximo 50 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="ubicacionBano">Ubicación <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ubicacionBano" name="ubicacion" maxlength="100" required>
                        <small class="form-text text-muted">Máximo 100 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="precioBano">Precio (Bs) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Bs</span>
                            </div>
                            <input type="number" step="0.01" min="0" class="form-control" id="precioBano" name="precio" required>
                        </div>
                        <small class="form-text text-muted">Precio en bolivianos (Bs).</small>
                    </div>
                    <div class="form-group">
                        <label for="estadoBano">Estado <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="estadoBano" name="estado" required>
                            <option value="disponible">Disponible</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="fuera_servicio">Fuera de Servicio</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn" id="btnGuardarBano">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

<script src="<?= $URL; ?>public/js/modules/banos/index-banos.js"></script>
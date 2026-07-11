<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/precios-equipaje/PrecioEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo ANTES de incluir el header
if (!$auth->puedeAccederModulo($idusuario, 'precios_equipaje')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

$controller = new PrecioEquipajeController();
$precios = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Precios de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Precios de Equipaje</li>
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
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-suitcase"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Precios</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Precios Activos</span>
                        <span class="info-box-number"><?= $estadisticas['activos']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Precios Inactivos</span>
                        <span class="info-box-number"><?= $estadisticas['inactivos']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Rango de Precios</span>
                        <span class="info-box-number">$<?= number_format($estadisticas['min_precio'], 2); ?> - $<?= number_format($estadisticas['max_precio'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h3 class="card-title mb-2 mb-sm-0">Precios de equipaje registrados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevoPrecio">
                                    <i class="fas fa-plus"></i> Nuevo Precio
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table id="tablaPrecios" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 8%">Nro</th>
                                        <th class="text-center" style="width: 15%">Tamaño</th>
                                        <th class="text-center" style="width: 35%">Descripción</th>
                                        <th class="text-center" style="width: 15%">Precio</th>
                                        <th class="text-center" style="width: 12%">Estado</th>
                                        <th class="text-center" style="width: 15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($precios as $precio) :
                                        $estado_actual = $precio['estado'];
                                        $clase_estado = $estado_actual == 1 ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado_actual == 1 ? 'Activo' : 'Inactivo';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($precio['tamano']); ?></td>
                                            <td><?= !empty($precio['descripcion']) ? htmlspecialchars($precio['descripcion']) : '<em class="text-muted">Sin descripción</em>'; ?></td>
                                            <td class="text-right">$<?= number_format($precio['precio'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                        data-id="<?= $precio['idprecioe']; ?>"
                                                        data-tamano="<?= htmlspecialchars($precio['tamano']); ?>"
                                                        data-descripcion="<?= htmlspecialchars($precio['descripcion'] ?? ''); ?>"
                                                        data-precio="<?= $precio['precio']; ?>"
                                                        data-estado="<?= $precio['estado']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn <?= $estado_actual == 1 ? 'btn-danger' : 'btn-success'; ?> btn-sm cambiar-estado"
                                                        data-id="<?= $precio['idprecioe']; ?>"
                                                        data-estado-actual="<?= $estado_actual; ?>"
                                                        data-toggle="tooltip"
                                                        title="<?= $estado_actual == 1 ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?= $estado_actual == 1 ? 'fa-times' : 'fa-check'; ?>"></i>
                                                    </button>
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

<!-- Modal para Precio de Equipaje -->
<div class="modal fade" id="modalPrecio" tabindex="-1" role="dialog" aria-labelledby="modalPrecioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalPrecioHeader">
                <h5 class="modal-title" id="modalPrecioLabel">Gestión de Precio de Equipaje</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formPrecio" method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                <div class="modal-body">
                    <input type="hidden" id="precioAction" name="action" value="create">
                    <input type="hidden" id="idPrecio" name="idprecioe" value="">

                    <div class="form-group">
                        <label for="tamano">Tamaño <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="tamano" name="tamano" required>
                            <option value="">Seleccione un tamaño</option>
                            <option value="Pequeño">Pequeño</option>
                            <option value="Mediano">Mediano</option>
                            <option value="Grande">Grande</option>
                            <option value="Extra_Grande">Extra Grande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="100"></textarea>
                        <small class="form-text text-muted">Máximo 100 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="estado" name="estado" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPrecio">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var baseUrl = "<?= $URL; ?>";
</script>

<!-- Script para la gestión de precios de equipaje -->
<script src="<?= $URL; ?>public/js/modules/precios-equipaje/index-precios.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
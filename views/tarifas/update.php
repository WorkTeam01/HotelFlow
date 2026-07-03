<?php
require_once __DIR__ . '/../../controllers/tarifas/TarifaController.php';
require_once __DIR__ . '/../../controllers/tipohabitaciones/TipoHabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->tieneAccesoCritico($idusuario, 'tarifas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de tarifa no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Obtener tipos de habitación desde la base de datos

$tipoController = new TipoHabitacionController();
$tipos = $tipoController->index();

// Instanciar el controlador y obtener los datos de la tarifa
$controller = new TarifaController();
$tarifa = $controller->editar($id);

// Verificar si la tarifa existe
if (!$tarifa) {
    $_SESSION['mensaje'] = 'Tarifa no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Definir scripts y estilos específicos para este módulo
$module_scripts = ['tarifas/update-tarifa'];
$module_styles = ['tarifas/tarifas-styles'];

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Tarifa</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tarifas"><i class="fas fa-list"></i> Tarifas</a></li>
                    <li class="breadcrumb-item active">Editar Tarifa</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna del formulario -->
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill"></i> Datos de la Tarifa</h3>
                    </div>

                    <!-- form start -->
                    <form id="formTarifa" action="<?= $URL; ?>controllers/tarifas/actualizar_tarifa.php" method="POST" novalidate>
                        <input type="hidden" name="idtarifa" value="<?= $tarifa['idtarifa']; ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Tipo de Habitación -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_tipo">
                                            <i class="fas fa-bed"></i> Tipo de Habitación <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="id_tipo" name="id_tipo" required>
                                            <option value="">Seleccione un tipo</option>
                                            <?php
                                            foreach ($tipos as $tipo) :
                                                if ($tipo['estado'] == 1 || $tipo['id_tipo'] == $tarifa['id_tipo']) : // Mostrar tipos activos y el tipo actual aunque esté inactivo
                                            ?>
                                                    <option value="<?= $tipo['id_tipo']; ?>" <?= $tarifa['id_tipo'] == $tipo['id_tipo'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($tipo['nombre']); ?> (<?= $tipo['capacidad_maxima']; ?> persona<?= $tipo['capacidad_maxima'] > 1 ? 's' : ''; ?>)
                                                        <?= $tipo['estado'] == 0 ? ' - Inactivo' : ''; ?>
                                                    </option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione un tipo de habitación</div>
                                    </div>
                                </div>

                                <!-- Tipo de Estancia -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_estancia">
                                            <i class="fas fa-clock"></i> Tipo de Estancia <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="tipo_estancia" name="tipo_estancia" required>
                                            <option value="horas" <?= $tarifa['tipo_estancia'] == 'horas' ? 'selected' : ''; ?>>Por Horas</option>
                                            <option value="dias" <?= $tarifa['tipo_estancia'] == 'dias' ? 'selected' : ''; ?>>Por Días</option>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione un tipo de estancia</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Duración -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="duracion">
                                            <i class="fas fa-hourglass-half"></i> Duración <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="duracion" name="duracion"
                                                min="1" value="<?= $tarifa['duracion']; ?>" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="unidad_duracion">
                                                    <?= $tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días'; ?>
                                                </span>
                                            </div>
                                            <div class="invalid-feedback">La duración debe ser un número positivo</div>
                                        </div>
                                        <small id="duracionHelp" class="form-text text-muted">Cantidad de horas o días según el tipo de estancia</small>
                                    </div>
                                </div>

                                <!-- Precio -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="precio">
                                            <i class="fas fa-tag"></i> Precio <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="precio" name="precio"
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($tarifa['precio'], 2, '.', ''); ?>" required>
                                            <div class="invalid-feedback">Por favor ingrese un precio válido</div>
                                        </div>
                                        <small id="precioHelp" class="form-text text-muted">Precio en bolivianos (Bs.)</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="estado">
                                            <i class="fas fa-toggle-on"></i> Estado
                                        </label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" <?= $tarifa['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?= $tarifa['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                        <small class="form-text text-muted">Las tarifas inactivas no pueden ser seleccionadas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Descripción -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion">
                                            <i class="fas fa-align-left"></i> Descripción
                                        </label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            placeholder="Ingrese una descripción de la tarifa"><?= htmlspecialchars($tarifa['descripcion'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Información adicional sobre esta tarifa (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista previa de la tarifa -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-eye"></i> Vista Previa de la Tarifa</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-center align-items-center p-3">
                                                <div class="text-center">
                                                    <h4 id="preview-title"><?= htmlspecialchars($tarifa['tipo_habitacion']); ?></h4>
                                                    <h5 id="preview-duration" class="text-warning">
                                                        <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'hora' : 'día'); ?>
                                                        <?= $tarifa['duracion'] > 1 ? 's' : ''; ?>
                                                    </h5>
                                                    <div class="pricing-price">
                                                        <span id="preview-price" class="h2">Bs. <?= number_format($tarifa['precio'], 2); ?></span>
                                                    </div>
                                                    <p id="preview-status" class="<?= $tarifa['estado'] == 1 ? 'text-success' : 'text-danger'; ?>">
                                                        <i class="fas fa-<?= $tarifa['estado'] == 1 ? 'check' : 'times'; ?>-circle"></i>
                                                        <?= $tarifa['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Sistema -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">Información del Sistema</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong><i class="fas fa-calendar-plus mr-1"></i> Fecha de Creación:</strong>
                                                    <?= date('d/m/Y H:i', strtotime($tarifa['fechacreacion'])); ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong><i class="fas fa-edit mr-1"></i> Última Actualización:</strong>
                                                    <?= !empty($tarifa['fechaactualizacion']) ?
                                                        date('d/m/Y H:i', strtotime($tarifa['fechaactualizacion'])) :
                                                        'No disponible'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save"></i> Actualizar Tarifa
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/tarifas" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Columna de información adicional -->
            <div class="col-md-4">
                <!-- Resumen de la tarifa -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Resumen de la Tarifa</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <div class="profile-user-img img-fluid img-circle bg-warning d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-money-bill fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($tarifa['tipo_habitacion']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= ucfirst(htmlspecialchars($tarifa['tipo_estancia'])); ?> -
                            <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días'); ?>
                        </p>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Está editando la información de esta tarifa. Los cambios serán efectivos una vez que presione "Actualizar Tarifa".
                        </div>
                    </div>
                </div>

                <!-- Guía de edición -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Guía de Edición</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-warning">
                            <h5><i class="fas fa-info"></i> Información importante:</h5>
                            <p class="text-wrap">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Recomendaciones</span>
                                <div class="info-box-text text-sm text-wrap">
                                    <ul class="pl-3 mb-0">
                                        <li>Revise el precio antes de actualizar.</li>
                                        <li>Si cambia el tipo de estancia, ajuste la duración adecuadamente.</li>
                                        <li>Considere si hay reservas activas que utilicen esta tarifa antes de desactivarla.</li>
                                    </ul>
                                </div>
                            </div>
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
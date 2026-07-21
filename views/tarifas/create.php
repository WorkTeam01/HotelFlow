<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/tipohabitaciones/TipoHabitacionController.php';
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

// Obtener tipos de habitación desde la base de datos
$tipoController = new TipoHabitacionController();
$tipos = $tipoController->index(true);

// Definir scripts y estilos específicos para este módulo
$module_scripts = ['tarifas/create-tarifa'];
$module_styles = ['tarifas/tarifas-styles'];

// Incluir el encabezado
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Crear Tarifa</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tarifas"><i class="fas fa-list"></i> Tarifas</a></li>
                    <li class="breadcrumb-item active">Crear Tarifa</li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill"></i> Datos de la Tarifa</h3>
                    </div>

                    <!-- form start -->
                    <form id="formTarifa" action="<?= $URL; ?>controllers/tarifas/crear_tarifa.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
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
                                            <?php foreach ($tipos as $tipo) : ?>
                                                <option value="<?= $tipo['id_tipo']; ?>">
                                                    <?= htmlspecialchars($tipo['nombre']); ?> (<?= $tipo['capacidad_maxima']; ?> persona<?= $tipo['capacidad_maxima'] > 1 ? 's' : ''; ?>)
                                                </option>
                                            <?php endforeach; ?>
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
                                            <option value="horas">Por Horas</option>
                                            <option value="dias">Por Días</option>
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
                                                min="1" value="1" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="unidad_duracion">horas</span>
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
                                                step="0.01" min="0" placeholder="0.00" required>
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
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
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
                                            placeholder="Ingrese una descripción de la tarifa"></textarea>
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
                                                    <h4 id="preview-title">Seleccione un tipo de habitación</h4>
                                                    <h5 id="preview-duration" class="text-primary">1 hora</h5>
                                                    <div class="pricing-price">
                                                        <span id="preview-price" class="h2">Bs. 0.00</span>
                                                    </div>
                                                    <p id="preview-status" class="text-success"><i class="fas fa-check-circle"></i> Activo</p>
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
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Guardar Tarifa
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/tarifas/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Columna de guía e información -->
            <div class="col-md-4">
                <!-- Ayuda del formulario -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Guía de Registro</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info"></i> Información importante:</h5>
                            <p class="text-wrap">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-info-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">¿Qué es una tarifa?</span>
                                <span class="info-box-text text-sm text-wrap">
                                    Una tarifa define el precio que se cobrará por un tipo específico de habitación durante un período de tiempo determinado.
                                </span>
                            </div>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Recomendaciones</span>
                                <div class="info-box-text text-sm text-wrap">
                                    <ul class="pl-3 mb-0">
                                        <li>Elija correctamente el tipo de estancia (horas o días).</li>
                                        <li>El precio debe reflejar la calidad y servicios del tipo de habitación.</li>
                                        <li>Verifique que no exista una tarifa idéntica para evitar duplicados.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarifas de Ejemplo -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Tarifas de Ejemplo</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bed text-primary"></i> Individual
                                    <span class="badge badge-primary">12 horas</span>
                                </div>
                                <span class="badge badge-info">Bs. 100.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bed text-success"></i> Doble
                                    <span class="badge badge-success">1 día</span>
                                </div>
                                <span class="badge badge-info">Bs. 180.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bed text-warning"></i> Familiar
                                    <span class="badge badge-warning">1 día</span>
                                </div>
                                <span class="badge badge-info">Bs. 250.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bed text-danger"></i> Suite
                                    <span class="badge badge-danger">1 día</span>
                                </div>
                                <span class="badge badge-info">Bs. 350.00</span>
                            </li>
                        </ul>
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
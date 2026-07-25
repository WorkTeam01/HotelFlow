<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'tipos_habitacion')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Definir scripts y estilos específicos para este módulo
$module_scripts = ['tipohabitacion/create-tipo-habitacion'];
$module_styles = ['tipohabitacion/tipo-habitacion-styles'];

// Incluir el encabezado
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Crear Tipo de Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tipohabitacion"> <i class="fas fa-bed"></i> Tipos de Habitación</a></li>
                    <li class="breadcrumb-item active">Crear Tipo</li>
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
                <div class="card card-primary sticky-top">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bed"></i> Datos del Tipo de Habitación</h3>
                    </div>

                    <!-- form start -->
                    <form id="formTipoHabitacion" action="<?= $URL; ?>controllers/tipohabitaciones/crear_tipo_habitacion.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">
                                            <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ingrese el nombre del tipo" required>
                                        <div class="invalid-feedback">Por favor ingrese el nombre del tipo de habitación</div>
                                        <small class="form-text text-muted">Ejemplo: Individual, Doble, Suite, Familiar</small>
                                    </div>
                                </div>

                                <!-- Capacidad Máxima -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="capacidad_maxima">
                                            <i class="fas fa-users"></i> Capacidad <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima"
                                            min="1" max="10" value="1" required>
                                        <div class="invalid-feedback">Debe ser entre 1 y 10 personas</div>
                                        <small class="form-text text-muted">Número máximo de personas</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="estado">
                                            <i class="fas fa-toggle-on"></i> Estado
                                        </label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
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
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4"
                                            placeholder="Ingrese una descripción detallada del tipo de habitación"></textarea>
                                        <small class="form-text text-muted">Incluya características relevantes de este tipo de habitación</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Guardar Tipo
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/tipohabitacion/index.php" class="btn btn-secondary w-100">
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
                            <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-bed"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tipo de Habitación</span>
                                <span class="info-box-text text-sm text-wrap">
                                    Define las características básicas de un tipo de habitación en el sistema.
                                </span>
                            </div>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Recomendaciones</span>
                                <span class="info-box-text text-sm text-wrap">
                                    <ul class="pl-3">
                                        <li>Elija un nombre claro y descriptivo.</li>
                                        <li>La capacidad debe reflejar el número máximo de huéspedes permitidos.</li>
                                        <li>Incluya una descripción detallada para el personal.</li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Tipos de Ejemplo -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Tipos de Ejemplo</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0 pb-1">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-bed text-primary"></i> Individual
                                <span class="badge badge-primary float-right">1 persona</span>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-bed text-success"></i> Doble
                                <span class="badge badge-success float-right">2 personas</span>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-bed text-info"></i> Matrimonial
                                <span class="badge badge-info float-right">2 personas</span>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-bed text-warning"></i> Familiar
                                <span class="badge badge-warning float-right">4 personas</span>
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
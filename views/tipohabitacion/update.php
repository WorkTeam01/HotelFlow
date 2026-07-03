<?php
require_once __DIR__ . '/../../controllers/tipohabitaciones/TipoHabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->tieneAccesoCritico($idusuario, 'tipos_habitacion')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de tipo de habitación no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del tipo de habitación
$controller = new TipoHabitacionController();
$tipoHabitacion = $controller->editar($id);

// Verificar si el tipo de habitación existe
if (!$tipoHabitacion) {
    $_SESSION['mensaje'] = 'Tipo de habitación no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Definir scripts y estilos específicos para este módulo
$module_scripts = ['tipohabitacion/update-tipo-habitacion'];
$module_styles = ['tipohabitacion/tipo-habitacion-styles'];

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Tipo de Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tipohabitacion"><i class="fas fa-bed"></i> Tipos de Habitación</a></li>
                    <li class="breadcrumb-item active">Editar Tipo</li>
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
                        <h3 class="card-title"><i class="fas fa-bed"></i> Datos del Tipo de Habitación</h3>
                    </div>

                    <!-- form start -->
                    <form id="formTipoHabitacion" action="<?= $URL; ?>controllers/tipohabitaciones/actualizar_tipo_habitacion.php" method="POST" novalidate>
                        <input type="hidden" name="id_tipo" value="<?= $tipoHabitacion['id_tipo']; ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">
                                            <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ingrese el nombre del tipo" value="<?= htmlspecialchars($tipoHabitacion['nombre']); ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese el nombre del tipo de habitación</div>
                                    </div>
                                </div>

                                <!-- Capacidad Máxima -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="capacidad_maxima">
                                            <i class="fas fa-users"></i> Capacidad <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima"
                                            min="1" max="10" value="<?= $tipoHabitacion['capacidad_maxima']; ?>" required>
                                        <div class="invalid-feedback">Debe ser entre 1 y 10 personas</div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="estado">
                                            <i class="fas fa-toggle-on"></i> Estado
                                        </label>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="estadoSwitch" <?= $tipoHabitacion['estado'] == 1 ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="estadoSwitch">
                                                <span id="estadoLabel"><?= $tipoHabitacion['estado'] == 1 ? 'Activo' : 'Inactivo'; ?></span>
                                            </label>
                                            <input type="hidden" name="estado" id="estado" value="<?= $tipoHabitacion['estado']; ?>">
                                        </div>
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
                                            placeholder="Ingrese una descripción detallada del tipo de habitación"><?= htmlspecialchars($tipoHabitacion['descripcion'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Incluya características relevantes de este tipo de habitación</small>
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
                                                    <?= date('d/m/Y H:i', strtotime($tipoHabitacion['fechacreacion'])); ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong><i class="fas fa-edit mr-1"></i> Última Actualización:</strong>
                                                    <?= !empty($tipoHabitacion['fechaactualizacion']) ?
                                                        date('d/m/Y H:i', strtotime($tipoHabitacion['fechaactualizacion'])) :
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
                                        <i class="fas fa-save"></i> Actualizar Tipo
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

            <!-- Columna de información y ayuda -->
            <div class="col-md-4">
                <!-- Resumen del tipo de habitación -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bed"></i> Resumen del Tipo</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <div class="profile-user-img img-fluid img-circle bg-warning d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-bed fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($tipoHabitacion['nombre']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            Capacidad: <?= $tipoHabitacion['capacidad_maxima']; ?> persona<?= $tipoHabitacion['capacidad_maxima'] > 1 ? 's' : ''; ?>
                        </p>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Está editando la información de este tipo de habitación. Los cambios serán efectivos una vez que presione "Actualizar Tipo".
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
                                        <li>Mantenga nombres claros y descriptivos.</li>
                                        <li>Verifique la capacidad máxima antes de actualizar.</li>
                                        <li>Al cambiar el estado, considere si hay habitaciones que utilizan este tipo.</li>
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
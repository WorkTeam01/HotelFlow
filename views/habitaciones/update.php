<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$auth->esAdministrador($idusuario) && !$auth->puedeAccederModulo($idusuario, 'habitaciones')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de habitación no válido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

$id = (int) $_GET['id'];

// Instanciar el controlador
$controller = new HabitacionController();

// Obtener datos para el formulario
$datos = $controller->editar($id);

if (!$datos) {
    $_SESSION['mensaje'] = 'Habitación no encontrada.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/habitaciones/index.php');
    exit;
}

$habitacion = $datos['habitacion'];
$tipos_habitacion = $datos['tipos_habitacion'];
$pisos = $datos['pisos'];

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
$module_scripts = ['habitaciones/update-habitaciones'];
include_once '../layouts/header.php';

$estado_ui = HabitacionController::estadoHabitacion($habitacion['estado']);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/habitaciones"><i class="fas fa-bed"></i> Habitaciones</a></li>
                    <li class="breadcrumb-item active">Editar Habitación</li>
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
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Información de la Habitación</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form method="post" action="<?= $URL; ?>controllers/habitaciones/actualizar_habitacion.php" id="formHabitacion">
                        <input type="hidden" name="id_habitacion" value="<?= $habitacion['id_habitacion']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numero">Número de Habitación <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="numero" name="numero"
                                                placeholder="Ej: 101" required maxlength="10"
                                                value="<?= htmlspecialchars($habitacion['numero']); ?>">
                                        </div>
                                        <small class="form-text text-muted">Identificador único de la habitación (máx. 10 caracteres)</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="id_tipo">Tipo de Habitación <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="id_tipo" name="id_tipo" required>
                                            <option value="">Seleccione un tipo</option>
                                            <?php foreach ($tipos_habitacion as $tipo): ?>
                                                <option value="<?= $tipo['id_tipo']; ?>"
                                                    <?= ($habitacion['id_tipo'] == $tipo['id_tipo']) ? 'selected' : ''; ?>
                                                    data-capacidad="<?= $tipo['capacidad_maxima']; ?>">
                                                    <?= htmlspecialchars($tipo['nombre']); ?> (Cap. <?= $tipo['capacidad_maxima']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="idpiso">Piso <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="idpiso" name="idpiso" required>
                                            <option value="">Seleccione un piso</option>
                                            <?php foreach ($pisos as $piso): ?>
                                                <option value="<?= $piso['idpiso']; ?>"
                                                    <?= ($habitacion['idpiso'] == $piso['idpiso']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($piso['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="capacidad_actual">Capacidad Actual <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="capacidad_actual" name="capacidad_actual"
                                                min="0" step="1" required
                                                value="<?= $habitacion['capacidad_actual']; ?>">
                                        </div>
                                        <small class="form-text text-muted">Número de personas que pueden ocupar la habitación</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="precio_base">Precio Base <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="precio_base" name="precio_base"
                                                min="0.01" step="0.01" required
                                                value="<?= number_format($habitacion['precio_base'], 2, '.', ''); ?>">
                                        </div>
                                        <small class="form-text text-muted">Precio base por noche de la habitación</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="estado">Estado <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="estado" name="estado" required>
                                            <option value="disponible" <?= ($habitacion['estado'] == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                                            <option value="ocupada" <?= ($habitacion['estado'] == 'ocupada') ? 'selected' : ''; ?>>Ocupada</option>
                                            <option value="limpieza" <?= ($habitacion['estado'] == 'limpieza') ? 'selected' : ''; ?>>Por limpiar</option>
                                            <option value="mantenimiento" <?= ($habitacion['estado'] == 'mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de sistema (solo lectura) -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-secondary">
                                        <strong>Fecha de Creación:</strong>
                                        <?= isset($habitacion['fechacreacion']) ? date('d/m/Y H:i', strtotime($habitacion['fechacreacion'])) : 'No disponible'; ?>
                                        &nbsp;|&nbsp;
                                        <strong>Última Actualización:</strong>
                                        <?= isset($habitacion['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($habitacion['fechaactualizacion'])) : 'No disponible'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-2">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save"></i> Actualizar Habitación
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/habitaciones/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna de información y ayuda -->
            <div class="col-md-4">
                <!-- Tarjeta de información de la habitación -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Detalles de la Habitación</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer detalles">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <span class="fa-stack fa-2x">
                                <i class="fas fa-circle fa-stack-2x text-<?= $estado_ui['clase']; ?>"></i>
                                <i class="fas fa-<?= $estado_ui['icono']; ?> fa-stack-1x fa-inverse"></i>
                            </span>
                        </div>

                        <h3 class="profile-username text-center">Habitación <?= htmlspecialchars($habitacion['numero']); ?></h3>
                        <p class="text-muted text-center">
                            <?= htmlspecialchars($habitacion['tipo_nombre']); ?> |
                            <?= htmlspecialchars($habitacion['piso_nombre']); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Estado Actual</b>
                                <span class="float-right badge <?= $estado_ui['badge']; ?> p-2"><?= $estado_ui['label']; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Capacidad</b> <span class="float-right"><?= (int) $habitacion['capacidad_actual']; ?> <?= (int) $habitacion['capacidad_actual'] === 1 ? 'persona' : 'personas'; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b>Precio Base</b> <span class="float-right">Bs <?= number_format($habitacion['precio_base'], 2); ?></span>
                            </li>
                        </ul>

                        <a href="<?= $URL; ?>views/habitaciones/show.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-info btn-block">
                            <i class="fas fa-eye"></i> Ver Detalles Completos
                        </a>
                    </div>
                </div>
                <!-- /.card -->

                <!-- Tarjeta de ayuda -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle mr-1"></i> Ayuda</h3>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-warning mb-0">
                            <h5 class="mb-1"><i class="fas fa-exclamation-triangle"></i> Estado "Ocupada"</h5>
                            <p class="mb-0">Cambiarlo aquí no crea un registro de ocupación. Para un check-in completo, use el módulo de Recepción. Todo cambio queda registrado en el historial con fecha y usuario.</p>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-4 -->
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


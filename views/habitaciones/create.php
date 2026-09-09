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

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
$module_scripts = ['habitaciones/create-habitaciones'];
include_once '../layouts/header.php';

// Instanciar el controlador
$controller = new HabitacionController();

// Obtener tipos de habitación y pisos para el formulario
$datos = $controller->crear();
$tipos_habitacion = $datos['tipos_habitacion'];
$pisos = $datos['pisos'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Crear Nueva Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/habitaciones"><i class="fas fa-bed"></i> Habitaciones</a></li>
                    <li class="breadcrumb-item active">Nueva Habitación</li>
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
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información de la Habitación</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form method="post" action="<?= $URL; ?>controllers/habitaciones/crear_habitacion.php" id="formHabitacion">
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
                                                placeholder="Ej: 101" required maxlength="10" autofocus>
                                        </div>
                                        <small class="form-text text-muted">Numérelas por piso (101, 102, 201…). Máx. 10 caracteres.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="id_tipo">Tipo de Habitación <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="id_tipo" name="id_tipo" required>
                                            <option value="">Seleccione un tipo</option>
                                            <?php foreach ($tipos_habitacion as $tipo): ?>
                                                <option value="<?= $tipo['id_tipo']; ?>" data-capacidad="<?= $tipo['capacidad_maxima']; ?>">
                                                    <?= htmlspecialchars($tipo['nombre']); ?> (Cap. <?= $tipo['capacidad_maxima']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Seleccione el tipo de habitación.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="idpiso">Piso <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="idpiso" name="idpiso" required>
                                            <option value="">Seleccione un piso</option>
                                            <?php foreach ($pisos as $piso): ?>
                                                <option value="<?= $piso['idpiso']; ?>">
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
                                                min="0" step="1" value="0" required>
                                        </div>
                                        <small class="form-text text-muted">Se completa según el tipo elegido; puede ajustarla.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="precio_base">Precio Base <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="precio_base" name="precio_base"
                                                min="0.01" step="0.01" value="0.00" required>
                                        </div>
                                        <small class="form-text text-muted">Precio base por noche, en bolivianos.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="estado">Estado <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="estado" name="estado" required>
                                            <option value="disponible" selected>Disponible</option>
                                            <option value="ocupada">Ocupada</option>
                                            <option value="limpieza">Por limpiar</option>
                                            <option value="mantenimiento">Mantenimiento</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-2">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Guardar Habitación
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
                <!-- Tarjeta de tipos de habitación -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Tipos de Habitación</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Capacidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tipos_habitacion as $tipo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tipo['nombre']); ?></td>
                                        <td><span class="badge badge-info"><?= (int) $tipo['capacidad_maxima']; ?> <?= (int) $tipo['capacidad_maxima'] === 1 ? 'persona' : 'personas'; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
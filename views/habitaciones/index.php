<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/habitaciones/HabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($auth->puedeAccederModulo($idusuario, 'habitaciones'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

$controller = new HabitacionController();

// Obtener todas las habitaciones - no hay filtros en el backend
$habitaciones = $controller->index();
$tipos_habitacion = $controller->getTiposHabitacion();
$pisos = $controller->getPisos();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Habitaciones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Habitaciones</li>
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
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-bed"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Disponibles</span>
                        <span class="info-box-number"><?= $estadisticas['disponibles']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ocupadas</span>
                        <span class="info-box-number"><?= $estadisticas['ocupadas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Mantenimiento</span>
                        <span class="info-box-number"><?= $estadisticas['mantenimiento']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-broom"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Limpieza</span>
                        <span class="info-box-number"><?= $estadisticas['limpieza']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tipos</span>
                        <span class="info-box-number"><?= count($estadisticas['por_tipo']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros avanzados -->
        <div class="row">
            <div class="col-md-12">
                <div class="card collapsed-card">
                    <div class="card-header card-outline card-info">
                        <h3 class="card-title">Filtros Avanzados</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-estado">Estado:</label>
                                    <select class="form-control select2" id="filtro-estado">
                                        <option value="">Todos los estados</option>
                                        <option value="disponible">Disponible</option>
                                        <option value="ocupada">Ocupada</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                        <option value="limpieza">Limpieza</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-tipo">Tipo de Habitación:</label>
                                    <select class="form-control select2" id="filtro-tipo">
                                        <option value="">Todos los tipos</option>
                                        <?php foreach ($tipos_habitacion as $tipo): ?>
                                            <option value="<?= htmlspecialchars($tipo['nombre']); ?>">
                                                <?= htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-piso">Piso:</label>
                                    <select class="form-control select2" id="filtro-piso">
                                        <option value="">Todos los pisos</option>
                                        <?php foreach ($pisos as $piso): ?>
                                            <option value="<?= htmlspecialchars($piso['nombre']); ?>">
                                                <?= htmlspecialchars($piso['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-precio">Precio base:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="filtro-precio" placeholder="Mínimo...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" id="btn-aplicar-filtros" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">
                                    <i class="fas fa-broom"></i> Limpiar Filtros
                                </button>
                                <a href="<?= $URL; ?>views/habitaciones/create.php" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Nueva Habitación
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista rápida de estados - Versión responsiva -->
        <div class="row">
            <div class="col-md-12">
                <div class="card collapsed-card">
                    <div class="card-header card-outline card-success">
                        <h3 class="card-title">Vista Rápida por Estado</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="row">
                            <div class="col-12 text-center">
                                <div class="btn-group btn-group-toggle d-flex flex-wrap justify-content-center" data-toggle="buttons">
                                    <label class="btn btn-outline-secondary active m-1" id="btn-filtro-todos">
                                        <input type="radio" name="options" autocomplete="off" checked>
                                        <i class="fas fa-bed mr-1"></i> Todos
                                        <span class="badge badge-secondary"><?= $estadisticas['total']; ?></span>
                                    </label>
                                    <label class="btn btn-outline-success m-1" id="btn-filtro-disponible">
                                        <input type="radio" name="options" autocomplete="off">
                                        <i class="fas fa-check-circle mr-1"></i> Disponibles
                                        <span class="badge badge-success"><?= $estadisticas['disponibles']; ?></span>
                                    </label>
                                    <label class="btn btn-outline-warning m-1" id="btn-filtro-ocupada">
                                        <input type="radio" name="options" autocomplete="off">
                                        <i class="fas fa-user mr-1"></i> Ocupadas
                                        <span class="badge badge-warning"><?= $estadisticas['ocupadas']; ?></span>
                                    </label>
                                    <label class="btn btn-outline-danger m-1" id="btn-filtro-mantenimiento">
                                        <input type="radio" name="options" autocomplete="off">
                                        <i class="fas fa-tools mr-1"></i> Mantenimiento
                                        <span class="badge badge-danger"><?= $estadisticas['mantenimiento']; ?></span>
                                    </label>
                                    <label class="btn btn-outline-primary m-1" id="btn-filtro-limpieza">
                                        <input type="radio" name="options" autocomplete="off">
                                        <i class="fas fa-broom mr-1"></i> Limpieza
                                        <span class="badge badge-primary"><?= $estadisticas['limpieza']; ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Habitaciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaHabitaciones" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">Habitación</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Piso</th>
                                        <th class="text-center">Capacidad</th>
                                        <th class="text-center">Precio Base</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($habitaciones as $habitacion) :
                                        $estado = $habitacion['estado'];
                                        $clase_estado = '';
                                        $texto_estado = '';

                                        switch ($estado) {
                                            case 'disponible':
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'Disponible';
                                                break;
                                            case 'ocupada':
                                                $clase_estado = 'badge-warning';
                                                $texto_estado = 'Ocupada';
                                                break;
                                            case 'mantenimiento':
                                                $clase_estado = 'badge-danger';
                                                $texto_estado = 'Mantenimiento';
                                                break;
                                            case 'limpieza':
                                                $clase_estado = 'badge-primary';
                                                $texto_estado = 'Limpieza';
                                                break;
                                            default:
                                                $clase_estado = 'badge-secondary';
                                                $texto_estado = ucfirst($estado);
                                        }
                                    ?>
                                        <tr data-estado="<?= $estado; ?>">
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td class="text-center"><?= htmlspecialchars($habitacion['numero']); ?></td>
                                            <td><?= htmlspecialchars($habitacion['tipo_nombre']); ?></td>
                                            <td><?= htmlspecialchars($habitacion['piso_nombre']); ?></td>
                                            <td class="text-center"><?= $habitacion['capacidad_actual']; ?></td>
                                            <td class="text-right precio-base"><?= number_format($habitacion['precio_base'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?> p-2"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <!-- Botones siempre visibles: Ver y Editar -->
                                                    <a href="<?= $URL; ?>views/habitaciones/show.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-info btn-sm" title="Ver detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/habitaciones/update.php?id=<?= $habitacion['id_habitacion']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <!-- Botón para marcar como Disponible (oculto si ya está disponible) -->
                                                    <?php if ($estado != 'disponible'): ?>
                                                        <button type="button" class="btn btn-success btn-sm cambiar-estado"
                                                            data-id="<?= $habitacion['id_habitacion']; ?>"
                                                            data-estado="disponible"
                                                            data-estado-actual="<?= $estado; ?>"
                                                            title="Marcar como Disponible">
                                                            <i class="fas fa-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Botón para marcar como Limpieza (oculto si ya está en limpieza) -->
                                                    <?php if ($estado != 'limpieza'): ?>
                                                        <button type="button" class="btn btn-primary btn-sm cambiar-estado"
                                                            data-id="<?= $habitacion['id_habitacion']; ?>"
                                                            data-estado="limpieza"
                                                            data-estado-actual="<?= $estado; ?>"
                                                            title="Marcar para Limpieza">
                                                            <i class="fas fa-broom"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Botón para marcar como Mantenimiento (oculto si ya está en mantenimiento) -->
                                                    <?php if ($estado != 'mantenimiento'): ?>
                                                        <button type="button" class="btn btn-danger btn-sm cambiar-estado"
                                                            data-id="<?= $habitacion['id_habitacion']; ?>"
                                                            data-estado="mantenimiento"
                                                            data-estado-actual="<?= $estado; ?>"
                                                            title="Marcar para Mantenimiento">
                                                            <i class="fas fa-tools"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Botón para marcar como Ocupada (solo visible si está disponible) -->
                                                    <?php if ($estado == 'disponible'): ?>
                                                        <button type="button" class="btn btn-warning btn-sm cambiar-estado"
                                                            data-id="<?= $habitacion['id_habitacion']; ?>"
                                                            data-estado="ocupada"
                                                            data-estado-actual="<?= $estado; ?>"
                                                            title="Marcar como Ocupada">
                                                            <i class="fas fa-user"></i>
                                                        </button>
                                                    <?php endif; ?>
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

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

<script>
    var baseUrl = "<?= $URL; ?>";
</script>

<!-- Script para la gestión de habitaciones -->
<script src="<?= $URL; ?>public/js/modules/habitaciones/index-habitaciones.js"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2
        initializeSelect2();
    });
</script>
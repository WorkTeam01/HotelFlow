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
$module_styles = ['tipohabitacion/tipo-habitacion-styles'];

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Tipo de Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/tipohabitacion"><i class="fas fa-bed"></i> Tipos de Habitación</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna de información general -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <div class="profile-user-img img-fluid img-circle bg-info d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-bed fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($tipoHabitacion['nombre']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            Capacidad: <?= $tipoHabitacion['capacidad_maxima']; ?> persona<?= $tipoHabitacion['capacidad_maxima'] > 1 ? 's' : ''; ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($tipoHabitacion['estado'] == 1): ?>
                                        <span class="badge badge-info">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt mr-1"></i> Fecha de creación</b>
                                <span class="float-right">
                                    <?= isset($tipoHabitacion['fechacreacion']) ? date('d/m/Y', strtotime($tipoHabitacion['fechacreacion'])) : 'No disponible'; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-sync-alt mr-1"></i> Última actualización</b>
                                <span class="float-right">
                                    <?= isset($tipoHabitacion['fechaactualizacion']) && !empty($tipoHabitacion['fechaactualizacion']) ? date('d/m/Y', strtotime($tipoHabitacion['fechaactualizacion'])) : 'No disponible'; ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/tipohabitacion/update.php?id=<?= $tipoHabitacion['id_tipo']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/tipohabitacion/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tools"></i> Acciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <button onclick="cambiarEstado(<?= $tipoHabitacion['id_tipo']; ?>, <?= $tipoHabitacion['estado']; ?>)" class="nav-link">
                                    <?php if ($tipoHabitacion['estado'] == 1): ?>
                                        <i class="fas fa-toggle-off text-danger"></i> Desactivar Tipo
                                    <?php else: ?>
                                        <i class="fas fa-toggle-on text-info"></i> Activar Tipo
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Columna de información detallada -->
            <div class="col-md-8">
                <!-- Tarjeta de descripción -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Tipo de Habitación</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-align-left"></i> Descripción
                                </h5>

                                <div class="callout callout-info">
                                    <div class="text-wrap">
                                        <?php if (!empty($tipoHabitacion['descripcion'])): ?>
                                            <p><?= nl2br(htmlspecialchars($tipoHabitacion['descripcion'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">No hay descripción disponible para este tipo de habitación.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Características del tipo de habitación -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-list-ul"></i> Características
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Capacidad Máxima</span>
                                        <span class="info-box-number">
                                            <?= $tipoHabitacion['capacidad_maxima']; ?> persona<?= $tipoHabitacion['capacidad_maxima'] > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-<?= $tipoHabitacion['estado'] == 1 ? 'info' : 'danger'; ?>">
                                        <i class="fas fa-<?= $tipoHabitacion['estado'] == 1 ? 'check' : 'times'; ?>"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Estado del Tipo</span>
                                        <span class="info-box-number">
                                            <?= $tipoHabitacion['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    /**
     * Función para cambiar el estado del tipo de habitación
     * @param {number} tipoId - ID del tipo
     * @param {number} estadoActual - Estado actual (1: activo, 0: inactivo)
     */
    function cambiarEstado(tipoId, estadoActual) {
        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const accion = estadoActual == 1 ? 'desactivar' : 'activar';
        const titulo = estadoActual == 1 ? '¿Desactivar Tipo de Habitación?' : '¿Activar Tipo de Habitación?';
        const texto = estadoActual == 1 ?
            'Este tipo de habitación no podrá ser seleccionado para nuevas habitaciones.' :
            'Este tipo de habitación podrá ser utilizado nuevamente.';

        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${accion}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redireccionar a la URL de cambio de estado
                window.location.href = `<?= $URL; ?>controllers/tipohabitaciones/desactivar_tipo_habitacion.php?id=${tipoId}&estado=${estadoActual}&csrf_token=<?= generateCSRFToken(); ?>`;
            }
        });
    }
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
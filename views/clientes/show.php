<?php
require_once __DIR__ . '/../../controllers/personas/PersonaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos para el módulo de personas
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'clientes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de cliente no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del cliente
$controller = new PersonaController();
$persona = $controller->editar($id);

// Verificar si el cliente existe
if (!$persona) {
    $_SESSION['mensaje'] = 'Cliente no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Definir scripts y estilos específicos para este módulo
$module_styles = ['clientes/cliente-styles'];
$module_scripts = ['clientes/show-persona'];

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-circle text-info"></i> Detalle del Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"> <i class="fas fa-user-friends"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Detalle del Cliente</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Información principal del cliente -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <div class="profile-user-img img-fluid img-circle bg-info d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-user-tie fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellidopaterno'] . ' ' . ($persona['apellidomaterno'] ?? '')); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($persona['tipodocumento']) ?>: <?= htmlspecialchars($persona['numdocumento']); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i> Teléfono</b>
                                <a href="tel:<?= htmlspecialchars($persona['telefono'] ?? ''); ?>" class="float-right">
                                    <?= !empty($persona['telefono']) ? htmlspecialchars($persona['telefono']) : 'No registrado'; ?>
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <a href="mailto:<?= htmlspecialchars($persona['email'] ?? ''); ?>" class="float-right">
                                    <?= !empty($persona['email']) ? htmlspecialchars($persona['email']) : 'No registrado'; ?>
                                </a>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($persona['estado'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/clientes/update.php?id=<?= $persona['idpersona']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/clientes/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones rápidas -->
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
                                <button onclick="cambiarEstado(<?= $persona['idpersona']; ?>, <?= $persona['estado']; ?>)" class="nav-link">
                                    <?php if ($persona['estado'] == 1): ?>
                                        <i class="fas fa-user-slash text-danger"></i> Desactivar Cliente
                                    <?php else: ?>
                                        <i class="fas fa-user-check text-success"></i> Activar Cliente
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Información detallada -->
            <div class="col-md-8">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Cliente</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Datos Personales -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-user"></i> Datos Personales
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Nombre Completo</span>
                                        <span class="info-box-number">
                                            <?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellidopaterno'] . ' ' . ($persona['apellidomaterno'] ?? '')); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Documento de Identidad</span>
                                        <span class="info-box-number">
                                            <?= htmlspecialchars($persona['tipodocumento'] . ': ' . $persona['numdocumento']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Fecha de Nacimiento</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['fechanacimiento']) ? date('d/m/Y', strtotime($persona['fechanacimiento'])) : 'No registrada'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Género</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['genero']) ? htmlspecialchars($persona['genero']) : 'No especificado'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección y Contacto -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-map-marker-alt"></i> Dirección y Contacto
                                </h5>
                            </div>

                            <div class="col-md-12">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Dirección</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['direccion']) ? htmlspecialchars($persona['direccion']) : 'No registrada'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Email</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['email']) ? htmlspecialchars($persona['email']) : 'No registrado'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Teléfono</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['telefono']) ? htmlspecialchars($persona['telefono']) : 'No registrado'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info border-bottom pb-2">
                                    <i class="fas fa-clock"></i> Información del Sistema
                                </h5>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Fecha de Registro</span>
                                        <span class="info-box-number">
                                            <?= date('d/m/Y H:i', strtotime($persona['fechacreacion'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Última Actualización</span>
                                        <span class="info-box-number">
                                            <?= !empty($persona['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($persona['fechaactualizacion'])) : 'Sin actualizaciones'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted">Estado</span>
                                        <span class="info-box-number">
                                            <?php if ($persona['estado'] == 1): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
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

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

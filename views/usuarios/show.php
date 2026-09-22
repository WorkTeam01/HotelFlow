<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->puedeAccederModulo($idusuario, 'usuarios')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$module_styles = ['usuarios/usuarios'];
$module_scripts = ['usuarios/show-usuario'];
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de usuario no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del usuario
$controller = new UsuarioController();
$usuario = $controller->editar($id);
// Verificar si el usuario existe
if (!$usuario) {
    $_SESSION['mensaje'] = 'Usuario no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios"><i class="fas fa-users" aria-hidden="true"></i> Usuarios</a></li>
                    <li class="breadcrumb-item active">Detalle de Usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna de perfil -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil con imagen -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center mb-4">
                            <div class="d-inline-block position-relative">
                                <?php if (isset($usuario['imagen']) && !empty($usuario['imagen'])): ?>
                                    <img class="profile-user-img img-fluid img-circle usuario-avatar-perfil"
                                        src="<?= $URL; ?>public/uploads/usuarios/<?= htmlspecialchars($usuario['imagen']); ?>"
                                        alt="Foto de <?= htmlspecialchars($usuario['nombre']); ?>">
                                <?php else: ?>
                                    <img class="profile-user-img img-fluid img-circle usuario-avatar-perfil"
                                        src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                        alt="Foto de perfil por defecto">
                                <?php endif; ?>

                                <span id="avatarEstadoBadge" class="badge <?= $usuario['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>"
                                    style="position: absolute; top: -4px; right: -4px;">
                                    <i class="fas <?= $usuario['estado'] == 1 ? 'fa-check' : 'fa-times'; ?>" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($usuario['cargo'] ?? 'Sin cargo asignado'); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-4">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-2" aria-hidden="true"></i><?= htmlspecialchars($usuario['tipodocumento']); ?></b>
                                <span class="float-right"><?= htmlspecialchars($usuario['numdocumento']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-2" aria-hidden="true"></i>Correo</b>
                                <span class="float-right"><?= htmlspecialchars($usuario['correo']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-2" aria-hidden="true"></i>Teléfono</b>
                                <span class="float-right">
                                    <?php if (!empty($usuario['telefono'])): ?>
                                        <a href="https://wa.me/591<?= htmlspecialchars(preg_replace('/[^0-9]/', '', ltrim($usuario['telefono'], '0'))); ?>"
                                            target="_blank" class="badge badge-success p-1">
                                            <i class="fab fa-whatsapp"></i>
                                            <?= htmlspecialchars($usuario['telefono']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No registrado</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt mr-2" aria-hidden="true"></i>Fecha Registro</b>
                                <span class="float-right">
                                    <?= date('d/m/Y', strtotime($usuario['fechacreacion'])); ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/usuarios/update.php?id=<?= $usuario['idusuario']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit" aria-hidden="true"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/usuarios/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones adicionales -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2" aria-hidden="true"></i>Acciones</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#" id="btnCambiarEstado" class="list-group-item list-group-item-action"
                                data-id="<?= $usuario['idusuario']; ?>"
                                data-estado="<?= $usuario['estado']; ?>"
                                data-nombre="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop']); ?>">
                                <i class="fas <?= $usuario['estado'] == 1 ? 'fa-user-slash text-danger' : 'fa-user-check text-success'; ?> mr-2" aria-hidden="true"></i>
                                <span id="btnCambiarEstadoTexto"><?= $usuario['estado'] == 1 ? 'Desactivar usuario' : 'Activar usuario'; ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna de información detallada -->
            <div class="col-md-8">
                <!-- Información personal -->
                <div class="card card-info card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-personal" data-toggle="pill" href="#personal" role="tab" aria-controls="personal" aria-selected="true">
                                    <i class="fas fa-user mr-1" aria-hidden="true"></i> Información Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-contacto" data-toggle="pill" href="#contacto" role="tab" aria-controls="contacto" aria-selected="false">
                                    <i class="fas fa-address-book mr-1" aria-hidden="true"></i> Contacto
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-sistema" data-toggle="pill" href="#sistema" role="tab" aria-controls="sistema" aria-selected="false">
                                    <i class="fas fa-cogs mr-1" aria-hidden="true"></i> Sistema
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Información Personal -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="tab-personal">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <h5 class="info-box-text text-center text-muted">Nombre Completo</h5>
                                                <h6 class="info-box-number text-center text-muted mb-0">
                                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop'] . ' ' . $usuario['apellidom']); ?>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%"><i class="fas fa-id-badge mr-2" aria-hidden="true"></i>Tipo Documento</th>
                                                <td><?= htmlspecialchars($usuario['tipodocumento']); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-hashtag mr-2" aria-hidden="true"></i>Número Documento</th>
                                                <td><?= htmlspecialchars($usuario['numdocumento']); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-map-marker-alt mr-2" aria-hidden="true"></i>Dirección</th>
                                                <td>
                                                    <?php if (!empty($usuario['direccion'])): ?>
                                                        <?= htmlspecialchars($usuario['direccion']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No registrada</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab Información de Contacto -->
                            <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="tab-contacto">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Correo Electrónico</span>
                                                <span class="info-box-number">
                                                    <a href="mailto:<?= htmlspecialchars($usuario['correo']); ?>"
                                                        class="text-info">
                                                        <?= htmlspecialchars($usuario['correo']); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-phone" aria-hidden="true"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Teléfono</span>
                                                <span class="info-box-number">
                                                    <?php if (!empty($usuario['telefono'])): ?>
                                                        <a href="https://wa.me/591<?= htmlspecialchars(preg_replace('/[^0-9]/', '', ltrim($usuario['telefono'], '0'))); ?>"
                                                            target="_blank" class="text-success">
                                                            <?= htmlspecialchars($usuario['telefono']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No registrado</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Información de Contacto Adicional</h3>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted">
                                                    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                                                    Para contactar a este usuario, puede utilizar cualquiera de los medios
                                                    de comunicación proporcionados anteriormente.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Información del Sistema -->
                            <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="tab-sistema">
                                <div class="timeline">
                                    <!-- Fecha de Creación -->
                                    <div>
                                        <i class="fas fa-user-plus bg-primary" aria-hidden="true"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock" aria-hidden="true"></i> <?= date('H:i', strtotime($usuario['fechacreacion'])); ?></span>
                                            <h3 class="timeline-header"><strong>Registro en el Sistema</strong></h3>
                                            <div class="timeline-body">
                                                Este usuario fue registrado el <?= date('d/m/Y', strtotime($usuario['fechacreacion'])); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Última Actualización -->
                                    <?php if (!empty($usuario['fechaactualizacion'])): ?>
                                        <div>
                                            <i class="fas fa-edit bg-warning" aria-hidden="true"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="fas fa-clock" aria-hidden="true"></i> <?= date('H:i', strtotime($usuario['fechaactualizacion'])); ?></span>
                                                <h3 class="timeline-header"><strong>Última Actualización</strong></h3>
                                                <div class="timeline-body">
                                                    La información fue actualizada por última vez el <?= date('d/m/Y', strtotime($usuario['fechaactualizacion'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Estado del Usuario -->
                                    <div>
                                        <i id="timelineEstadoIcono" class="fas <?= $usuario['estado'] == 1 ? 'fa-check-circle bg-success' : 'fa-times-circle bg-danger'; ?>"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header"><strong>Estado de la Cuenta</strong></h3>
                                            <div class="timeline-body">
                                                El usuario se encuentra actualmente
                                                <span id="timelineEstadoBadge" class="badge <?= $usuario['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?= $usuario['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cargo del Usuario -->
                                    <div>
                                        <i class="fas fa-user-tag bg-info" aria-hidden="true"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header"><strong>Cargo en el Sistema</strong></h3>
                                            <div class="timeline-body">
                                                <span class="badge badge-info"><?= htmlspecialchars($usuario['cargo']); ?></span>
                                                <p class="mt-2">
                                                    <?php
                                                    // Descripción basada en el cargo
                                                    $descripcion = '';
                                                    switch ($usuario['cargo']) {
                                                        case 'Administrador':
                                                            $descripcion = 'Usuario con permisos completos para administrar el sistema.';
                                                            break;
                                                        case 'Recepcionista':
                                                            $descripcion = 'Usuario con permisos para gestionar reservas y clientes.';
                                                            break;
                                                        case 'Limpieza':
                                                            $descripcion = 'Usuario con permisos para ver y actualizar estado de habitaciones.';
                                                            break;
                                                        default:
                                                            $descripcion = 'Usuario con permisos personalizados.';
                                                    }
                                                    echo $descripcion;
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <i class="fas fa-clock bg-gray" aria-hidden="true"></i>
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
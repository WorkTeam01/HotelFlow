<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->puedeAccederModulo($idusuario, 'usuarios') && !$authService->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$module_styles = ['usuarios/usuarios'];
$module_scripts = ['usuarios/update-usuario', 'usuarios/permisos'];
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
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
                <h1>Editar Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios/show.php?id=<?= $usuario['idusuario']; ?>"><i class="fas fa-user"></i> Ver Usuario</a></li>
                    <li class="breadcrumb-item active">Editar Usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/usuarios/actualizar_usuario.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="idusuario" value="<?= $usuario['idusuario']; ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <div class="row">
                <!-- Columna del formulario (8/12) -->
                <div class="col-md-8">
                    <div class="callout callout-warning">
                        <h5><i class="fas fa-info-circle"></i> Editando usuario ID: <?= $usuario['idusuario']; ?></h5>
                        <p class="mb-0">Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los campos sin modificar mantendrán su valor actual.</p>
                    </div>

                    <!-- Información Personal -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Información Personal</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                placeholder="Ingrese el nombre" value="<?= htmlspecialchars($usuario['nombre']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Apellido Paterno -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="apellidop">Apellido Paterno <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="apellidop" name="apellidop"
                                                placeholder="Ingrese el apellido paterno" value="<?= htmlspecialchars($usuario['apellidop']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Apellido Materno -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="apellidom">Apellido Materno</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="apellidom" name="apellidom"
                                                placeholder="Ingrese el apellido materno" value="<?= htmlspecialchars($usuario['apellidom'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Tipo de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipodocumento">Tipo de Documento <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="tipodocumento" name="tipodocumento" required>
                                            <option value="">Seleccione un tipo de documento</option>
                                            <option value="DNI" <?= $usuario['tipodocumento'] == 'DNI' ? 'selected' : ''; ?>>DNI</option>
                                            <option value="PASAPORTE" <?= $usuario['tipodocumento'] == 'PASAPORTE' ? 'selected' : ''; ?>>Pasaporte</option>
                                            <option value="CI" <?= $usuario['tipodocumento'] == 'CI' ? 'selected' : ''; ?>>Cédula de Identidad</option>
                                            <option value="RUC" <?= $usuario['tipodocumento'] == 'RUC' ? 'selected' : ''; ?>>RUC</option>
                                            <option value="OTROS" <?= $usuario['tipodocumento'] == 'OTROS' ? 'selected' : ''; ?>>Otros</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Número de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numdocumento">Número de Documento <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="numdocumento" name="numdocumento"
                                                placeholder="Ingrese el número de documento" value="<?= htmlspecialchars($usuario['numdocumento']); ?>"
                                                maxlength="25" required>
                                        </div>
                                        <small class="form-text text-muted" id="docHelp">El formato dependerá del tipo de documento seleccionado</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Dirección -->
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="direccion">Dirección</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                            </div>
                                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                                placeholder="Ingrese la dirección"><?= htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información Personal -->

                    <!-- Información de Contacto -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-address-book mr-2"></i>Información de Contacto</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Teléfono -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="telefono">Teléfono</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            </div>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                placeholder="Ingrese el teléfono" value="<?= htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                                maxlength="20">
                                        </div>
                                    </div>
                                </div>

                                <!-- Correo -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="correo">Correo Electrónico <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="email" class="form-control" id="correo" name="correo"
                                                placeholder="Ingrese el correo electrónico" value="<?= htmlspecialchars($usuario['correo']); ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información de Contacto -->

                    <!-- Información de Acceso -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Información de Acceso</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Cargo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cargo">Cargo <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="cargo" name="cargo" required>
                                            <option value="">Seleccione un cargo</option>
                                            <option value="Administrador" <?= $usuario['cargo'] == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                            <option value="Recepcionista" <?= $usuario['cargo'] == 'Recepcionista' ? 'selected' : ''; ?>>Recepcionista</option>
                                            <option value="Limpieza" <?= $usuario['cargo'] == 'Limpieza' ? 'selected' : ''; ?>>Limpieza</option>
                                        </select>
                                        <small class="form-text text-muted">El cargo determina los permisos iniciales del usuario</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" <?= $usuario['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?= $usuario['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                        <small class="form-text text-muted">Si el usuario está inactivo, no podrá acceder al sistema</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="imagen">Imagen de Perfil</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                            </div>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="imagen" name="imagen"
                                                    accept="image/*">
                                                <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                    </div>
                                </div>

                                <!-- Imagen actual -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label>Imagen Actual</label><br>
                                        <div class="d-flex align-items-center">
                                            <?php if (isset($usuario['imagen']) && !empty($usuario['imagen'])): ?>
                                                <img src="<?= $URL; ?>public/uploads/usuarios/<?= htmlspecialchars($usuario['imagen']); ?>"
                                                    alt="Imagen actual" class="img-thumbnail mr-3 usuario-avatar-thumb">
                                                <small class="text-muted">Se reemplazará si selecciona una nueva imagen</small>
                                            <?php else: ?>
                                                <img src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                                    alt="Imagen por defecto" class="img-thumbnail mr-3 usuario-avatar-thumb">
                                                <small class="text-muted">No hay imagen personalizada</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista previa de imagen nueva -->
                            <div class="row mt-3" id="preview-container" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label>Vista Previa Nueva Imagen:</label><br>
                                        <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail usuario-avatar-preview">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información de Acceso -->

                    <!-- Cambiar Contraseña -->
                    <div class="card card-outline card-warning collapsed-card mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-key mr-2"></i>Cambiar Contraseña (opcional)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir la sección de cambio de contraseña">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Complete estos campos <strong>solo si desea cambiar la contraseña</strong> del usuario.
                                Si los deja en blanco, se mantendrá la contraseña actual.
                            </div>

                            <div class="row">
                                <!-- Contraseña -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="clave">Nueva Contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="clave" name="clave"
                                                placeholder="Dejar en blanco para mantener la actual">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary password-toggle" data-target="#clave" aria-label="Mostrar contraseña" aria-pressed="false">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                    </div>
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="confirmar_clave">Confirmar Nueva Contraseña</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave"
                                                placeholder="Confirme la nueva contraseña">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary password-toggle" data-target="#confirmar_clave" aria-label="Mostrar contraseña" aria-pressed="false">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="password-feedback mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Cambiar Contraseña -->

                    <!-- Asignación de Permisos -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-lock mr-2"></i>Asignación de Permisos</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Los permisos se asignan automáticamente según el cargo seleccionado. Puede personalizar los permisos si es necesario.
                            </div>

                            <!-- Botones para seleccionar/deseleccionar todos -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllPermissions">
                                    <i class="fas fa-check-square"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="deselectAllPermissions">
                                    <i class="fas fa-square"></i> Deseleccionar Todos
                                </button>
                            </div>

                            <!-- Usar el mismo sistema de permisos agrupados por cargo que en create.php -->
                            <?php
                            // Obtener permisos agrupados por cargo
                            $permisos_agrupados = $authService->obtenerPermisosAgrupados();

                            // Mostrar permisos por cargo en pestañas
                            ?>
                            <ul class="nav nav-tabs" id="permisosTab" role="tablist">
                                <?php $active = 'active'; ?>
                                <?php foreach ($permisos_agrupados as $cargo => $info): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $active ?>" id="<?= strtolower(str_replace(' ', '-', $cargo)) ?>-tab"
                                            data-toggle="tab"
                                            href="#<?= strtolower(str_replace(' ', '-', $cargo)) ?>"
                                            role="tab"
                                            aria-controls="<?= strtolower(str_replace(' ', '-', $cargo)) ?>"
                                            aria-selected="<?= $active === 'active' ? 'true' : 'false' ?>">
                                            <?= htmlspecialchars($cargo) ?>
                                        </a>
                                    </li>
                                    <?php $active = ''; ?>
                                <?php endforeach; ?>
                            </ul>

                            <div class="tab-content mt-3" id="permisosTabContent">
                                <?php $active = 'show active'; ?>
                                <?php foreach ($permisos_agrupados as $cargo => $info): ?>
                                    <div class="tab-pane fade <?= $active ?>"
                                        id="<?= strtolower(str_replace(' ', '-', $cargo)) ?>"
                                        role="tabpanel"
                                        aria-labelledby="<?= strtolower(str_replace(' ', '-', $cargo)) ?>-tab">

                                        <p class="text-muted"><?= $info['descripcion'] ?></p>

                                        <div class="row">
                                            <?php foreach ($info['permisos'] as $permiso): ?>
                                                <div class="col-md-4">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox"
                                                            class="custom-control-input permiso-checkbox"
                                                            id="permiso_<?= $permiso['idpermiso'] ?>_<?= strtolower(str_replace(' ', '_', $cargo)) ?>"
                                                            name="permisos[]"
                                                            value="<?= $permiso['idpermiso'] ?>"
                                                            data-cargo="<?= htmlspecialchars($cargo) ?>">
                                                        <label class="custom-control-label"
                                                            for="permiso_<?= $permiso['idpermiso'] ?>_<?= strtolower(str_replace(' ', '_', $cargo)) ?>">
                                                            <?= htmlspecialchars($permiso['nombre']) ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php $active = ''; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <!-- /Asignación de Permisos -->

                    <!-- Información del Sistema -->
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Información del Sistema</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Fecha de Creación:</strong>
                                    <p class="mb-0"><?= isset($usuario['fechacreacion']) ? date('d/m/Y H:i', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Última Actualización:</strong>
                                    <p class="mb-0"><?= isset($usuario['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($usuario['fechaactualizacion'])) : 'No disponible'; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save mr-2"></i> Actualizar Usuario
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/usuarios/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información del Sistema -->
                </div>
                <!-- /.col-md-8 -->

                <!-- Columna de guía (4/12) -->
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-route mr-2"></i>Guía de edición</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir esta sección">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Solo modifique los campos que necesite actualizar; el resto conserva su valor actual.</p>

                            <ul class="usuario-timeline">
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-warning"><i class="fas fa-id-card"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Datos y documento</h6>
                                        <p>Si cambia el número de documento, verifique que siga siendo único en el sistema.</p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-warning"><i class="fas fa-envelope"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Correo electrónico</h6>
                                        <p>Se usa para iniciar sesión: si lo cambia, el usuario deberá acceder con el nuevo correo.</p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-warning"><i class="fas fa-user-tag"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Cargo y estado</h6>
                                        <p>Cambiar el cargo actualiza los permisos por defecto. Prefiera "Inactivo" antes que eliminar al usuario: conserva su historial.</p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-danger"><i class="fas fa-key"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Contraseña</h6>
                                        <p>Deje ambos campos vacíos para mantener la actual, o complete los dos para establecer una nueva (mín. 6 caracteres).</p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item usuario-timeline-item-last">
                                    <span class="usuario-timeline-icon bg-success"><i class="fas fa-shield-alt"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Permisos</h6>
                                        <p>Los permisos actuales ya están marcados; use "Seleccionar/Deseleccionar Todos" para ajustes rápidos.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Tarjeta de resumen del usuario -->
                    <div class="card card-success mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Resumen del Usuario</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir esta sección">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-id-badge mr-2"></i>ID de Usuario</span>
                                    <span class="badge badge-primary"><?= $usuario['idusuario']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tag mr-2"></i>Cargo Actual</span>
                                    <span class="badge badge-info"><?= htmlspecialchars($usuario['cargo']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-toggle-on mr-2"></i>Estado Actual</span>
                                    <span id="resumenEstadoBadge" class="badge <?= $usuario['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>">
                                        <?= $usuario['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="far fa-calendar-plus mr-2"></i>Fecha de Registro</span>
                                    <span class="badge badge-secondary"><?= date('d/m/Y', strtotime($usuario['fechacreacion'])); ?></span>
                                </li>
                                <?php if (isset($usuario['fechaactualizacion']) && !empty($usuario['fechaactualizacion'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="far fa-calendar-check mr-2"></i>Última Actualización</span>
                                        <span class="badge badge-secondary"><?= date('d/m/Y', strtotime($usuario['fechaactualizacion'])); ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Tarjeta de acciones rápidas -->
                    <div class="card card-outline card-secondary mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="<?= $URL; ?>views/usuarios/show.php?id=<?= $usuario['idusuario']; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-eye mr-2 text-info"></i> Ver detalles del usuario
                                </a>
                                <a href="#" class="list-group-item list-group-item-action cambiar-estado-link"
                                    data-id="<?= $usuario['idusuario']; ?>"
                                    data-estado="<?= $usuario['estado']; ?>"
                                    data-nombre="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop']); ?>">
                                    <i class="fas <?= $usuario['estado'] == 1 ? 'fa-user-slash text-danger' : 'fa-user-check text-success'; ?> mr-2" id="cambiarEstadoLinkIcono"></i>
                                    <span id="cambiarEstadoLinkTexto"><?= $usuario['estado'] == 1 ? 'Desactivar usuario' : 'Activar usuario'; ?></span>
                                </a>
                                <a href="<?= $URL; ?>views/usuarios/index.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-list mr-2 text-primary"></i> Volver a la lista de usuarios
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Script para validaciones y vista previa -->
<!-- Script para manejo de permisos -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

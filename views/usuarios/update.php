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
                <p class="text-muted">
                    Modificando datos de: <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop']); ?></strong>
                </p>
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
        <div class="row">
            <!-- Columna del formulario (8/12) -->
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Formulario de Edición de Usuario</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/usuarios/actualizar_usuario.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="idusuario" value="<?= $usuario['idusuario']; ?>">
                        <div class="card-body">
                            <!-- Instrucciones -->
                            <div class="callout callout-warning mb-4">
                                <h5><i class="fas fa-info-circle"></i> Editando usuario ID: <?= $usuario['idusuario']; ?></h5>
                                <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los campos sin modificar mantendrán su valor actual.</p>
                            </div>

                            <!-- Sección de Información Personal -->
                            <h5 class="border-bottom border-warning pb-2 mb-3"><i class="fas fa-id-card mr-2"></i>Información Personal</h5>

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
                                    <div class="form-group">
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

                            <!-- Sección de Contacto -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-address-book mr-2"></i>Información de Contacto</h5>

                            <div class="row">
                                <!-- Teléfono -->
                                <div class="col-md-6">
                                    <div class="form-group">
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
                                    <div class="form-group">
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

                            <!-- Sección de Acceso al Sistema -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-cogs mr-2"></i>Información de Acceso</h5>

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

                            <!-- Sección de imagen -->
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
                                    <div class="form-group">
                                        <label>Imagen Actual</label><br>
                                        <div class="d-flex align-items-center">
                                            <?php if (isset($usuario['imagen']) && !empty($usuario['imagen'])): ?>
                                                <img src="<?= $URL; ?>public/uploads/usuarios/<?= htmlspecialchars($usuario['imagen']); ?>"
                                                    alt="Imagen actual" class="img-thumbnail mr-3" style="max-width: 100px; max-height: 100px;">
                                                <small class="text-muted">Se reemplazará si selecciona una nueva imagen</small>
                                            <?php else: ?>
                                                <img src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                                    alt="Imagen por defecto" class="img-thumbnail mr-3" style="max-width: 100px; max-height: 100px;">
                                                <small class="text-muted">No hay imagen personalizada</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista previa de imagen nueva -->
                            <div class="row" id="preview-container" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Vista Previa Nueva Imagen:</label><br>
                                        <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Cambio de Contraseña -->
                            <div class="card collapsed-card">
                                <div class="card-header card-outline card-warning">
                                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>Cambiar Contraseña (opcional)</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                Complete estos campos <strong>solo si desea cambiar la contraseña</strong> del usuario.
                                                Si los deja en blanco, se mantendrá la contraseña actual.
                                            </div>
                                        </div>
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
                                                        <button type="button" class="btn btn-outline-secondary" id="showPassword">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                            </div>
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirmar_clave">Confirmar Nueva Contraseña</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    </div>
                                                    <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave"
                                                        placeholder="Confirme la nueva contraseña">
                                                </div>
                                                <div class="password-feedback mt-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Permisos -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-lock mr-2"></i>Asignación de Permisos</h5>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Los permisos se asignan automáticamente según el cargo seleccionado. Puede personalizar los permisos si es necesario.
                                    </div>

                                    <!-- Botones para seleccionar/deseleccionar todos -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllPermissions">
                                                <i class="fas fa-check-square"></i> Seleccionar Todos
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="deselectAllPermissions">
                                                <i class="fas fa-square"></i> Deseleccionar Todos
                                            </button>
                                        </div>
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
                                                    <?= $cargo ?>
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
                                                                    data-cargo="<?= $cargo ?>">
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

                            <!-- Información del Sistema -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card card-outline card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">Información del Sistema</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Fecha de Creación:</strong>
                                                    <p><?= isset($usuario['fechacreacion']) ? date('d/m/Y H:i', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Última Actualización:</strong>
                                                    <p><?= isset($usuario['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($usuario['fechaactualizacion'])) : 'No disponible'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-2">
                                <div class="col-12 col-sm-auto">
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
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Nueva columna para la guía (4/12) -->
            <div class="col-md-4">
                <!-- Tarjeta de guía de edición -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Guía de Edición</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="text-info"><i class="fas fa-info-circle"></i> Cómo editar un usuario</h5>
                        <div class="callout callout-warning">
                            <p>Solo modifique los campos que necesite actualizar. Los campos sin cambios mantendrán su valor actual.</p>
                        </div>

                        <div class="accordion" id="accordionHelp">
                            <!-- Sección de Información Personal -->
                            <div class="card">
                                <div class="card-header" id="headingPersonal">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left text-info" type="button" data-toggle="collapse" data-target="#collapsePersonal" aria-expanded="true" aria-controls="collapsePersonal">
                                            <i class="fas fa-id-card mr-2"></i>Información Personal
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapsePersonal" class="collapse show" aria-labelledby="headingPersonal" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Nombre y Apellidos:</strong> Puede modificar cualquier parte del nombre del usuario.</p>
                                        <p><strong>Tipo y Número de Documento:</strong> Tenga en cuenta que si modifica estos campos, deben ser únicos en el sistema.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Verifique cuidadosamente el número de documento, ya que es un dato crítico para la identificación.</small>
                                        </div>
                                        <p><strong>Dirección:</strong> Puede actualizarla o dejarla en blanco si no es necesaria.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Contacto -->
                            <div class="card">
                                <div class="card-header" id="headingContacto">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseContacto" aria-expanded="false" aria-controls="collapseContacto">
                                            <i class="fas fa-address-book mr-2"></i>Información de Contacto
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseContacto" class="collapse" aria-labelledby="headingContacto" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Teléfono:</strong> Puede actualizar el número telefónico del usuario.</p>
                                        <p><strong>Correo Electrónico:</strong> Si lo modifica, debe ser único y no estar registrado por otro usuario.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: El correo electrónico se usa para iniciar sesión. Si lo cambia, el usuario deberá iniciar sesión con el nuevo correo.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Acceso -->
                            <div class="card">
                                <div class="card-header" id="headingAcceso">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseAcceso" aria-expanded="false" aria-controls="collapseAcceso">
                                            <i class="fas fa-key mr-2"></i>Información de Acceso
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseAcceso" class="collapse" aria-labelledby="headingAcceso" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Cargo:</strong> Puede cambiar el rol del usuario en el sistema. Esto afectará sus permisos predeterminados.</p>
                                        <p><strong>Estado:</strong> Determina si el usuario puede acceder al sistema.</p>
                                        <ul>
                                            <li><small><strong>Activo:</strong> El usuario puede iniciar sesión normalmente.</small></li>
                                            <li><small><strong>Inactivo:</strong> El usuario no podrá iniciar sesión hasta que se reactive su cuenta.</small></li>
                                        </ul>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Usar la opción "Inactivo" es mejor que eliminar usuarios, ya que mantiene su historial en el sistema.</small>
                                        </div>
                                        <p><strong>Imagen de Perfil:</strong> Si sube una nueva imagen, reemplazará la actual. Deje vacío este campo para mantener la imagen actual.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Contraseña -->
                            <div class="card">
                                <div class="card-header" id="headingContrasena">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseContrasena" aria-expanded="false" aria-controls="collapseContrasena">
                                            <i class="fas fa-lock mr-2"></i>Cambiar Contraseña
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseContrasena" class="collapse" aria-labelledby="headingContrasena" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p>Para cambiar la contraseña del usuario, haga clic en el botón "+" en la sección "Cambiar Contraseña" del formulario.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: Si deja ambos campos de contraseña vacíos, se mantendrá la contraseña actual. Si completa uno solo, el sistema mostrará un error.</small>
                                        </div>
                                        <p><strong>Requisitos de contraseña:</strong></p>
                                        <ul>
                                            <li><small>Mínimo 6 caracteres</small></li>
                                            <li><small>Se recomienda combinar letras, números y símbolos</small></li>
                                            <li><small>Las contraseñas deben coincidir exactamente</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Permisos -->
                            <div class="card">
                                <div class="card-header" id="headingPermisos">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapsePermisos" aria-expanded="false" aria-controls="collapsePermisos">
                                            <i class="fas fa-shield-alt mr-2"></i>Permisos
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapsePermisos" class="collapse" aria-labelledby="headingPermisos" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p>Los permisos actuales del usuario están marcados. Si cambia el cargo, los permisos se actualizarán automáticamente, pero puede personalizarlos según sea necesario.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Utilice los botones "Seleccionar Todos" o "Deseleccionar Todos" para facilitar la gestión de permisos múltiples.</small>
                                        </div>
                                        <p><strong>Permisos clave:</strong></p>
                                        <ul>
                                            <li><small><strong>Usuarios:</strong> Permite gestionar otros usuarios</small></li>
                                            <li><small><strong>Perfil:</strong> Permite al usuario modificar su propio perfil</small></li>
                                            <li><small><strong>Habitaciones:</strong> Acceso a la gestión de habitaciones</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de resumen del usuario -->
                <div class="card card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Resumen del Usuario</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
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
                                <?php if ($usuario['estado'] == 1): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
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

                <!-- Tarjeta de consejos para edición -->
                <div class="card card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Consejos para Edición</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>No deje campos obligatorios vacíos</strong>
                            <p class="mb-0 small">Aunque esté editando, los campos marcados con <span class="text-danger">*</span> siguen siendo obligatorios.</p>
                        </div>

                        <div class="alert alert-secondary mb-2">
                            <i class="fas fa-shield-alt mr-2"></i> <strong>Gestión de permisos</strong>
                            <p class="mb-0 small">Al cambiar el cargo, los permisos se actualizarán automáticamente. Revise si necesita ajustes adicionales.</p>
                        </div>

                        <div class="alert alert-secondary mb-2">
                            <i class="fas fa-image mr-2"></i> <strong>Sobre las imágenes</strong>
                            <p class="mb-0 small">Si no selecciona una nueva imagen, se mantendrá la actual. Las imágenes nuevas reemplazarán a las anteriores.</p>
                        </div>

                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-lock mr-2"></i> <strong>Restablecimiento de contraseña</strong>
                            <p class="mb-0 small">Si el usuario olvidó su contraseña, puede establecer una nueva temporal aquí.</p>
                        </div>
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
                            <a href="#" class="list-group-item list-group-item-action cambiar-estado-link">
                                <?php if ($usuario['estado'] == 1): ?>
                                    <i class="fas fa-user-slash mr-2 text-danger"></i> Desactivar usuario
                                <?php else: ?>
                                    <i class="fas fa-user-check mr-2 text-success"></i> Activar usuario
                                <?php endif; ?>
                            </a>
                            <a href="<?= $URL; ?>views/usuarios/index.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list mr-2 text-primary"></i> Volver a la lista de usuarios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script para validaciones y vista previa -->
<script src="<?= $URL; ?>public/js/modules/usuarios/update-usuario.js"></script>
<!-- Script para manejo de permisos -->
<script src="<?= $URL; ?>public/js/modules/usuarios/permisos.js"></script>

<script>
    // SweetAlert2 para confirmar cambio de estado
    $(document).ready(function() {
        $('.cambiar-estado-link').on('click', function(e) {
            e.preventDefault();

            const usuarioId = <?= $usuario['idusuario']; ?>;
            const estadoActual = <?= $usuario['estado']; ?>;
            const nombreUsuario = "<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidop']); ?>";

            const tituloAlerta = estadoActual == 1 ?
                `¿Desactivar a ${nombreUsuario}?` :
                `¿Activar a ${nombreUsuario}?`;

            const textoAlerta = estadoActual == 1 ?
                'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' :
                'El usuario podrá acceder nuevamente al sistema.';

            const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
            const cancelButtonText = 'Cancelar';

            Swal.fire({
                title: tituloAlerta,
                text: textoAlerta,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir a la acción de cambio de estado
                    window.location.href = `<?= $URL; ?>controllers/usuarios/desactivar_usuario.php?id=${usuarioId}&estado=${estadoActual}`;
                }
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
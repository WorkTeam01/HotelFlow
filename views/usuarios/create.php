<?php
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
$module_scripts = ['usuarios/create-usuario', 'usuarios/permisos'];
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Crear Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li class="breadcrumb-item active">Crear Usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/usuarios/crear_usuario.php" method="POST" enctype="multipart/form-data" id="formCrearUsuario">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <div class="row">
                <!-- Columna del formulario (8/12) -->
                <div class="col-md-8">
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle"></i> Información importante</h5>
                        <p class="mb-0">Los campos marcados con <span class="text-danger">*</span> son obligatorios. Asegúrese de completar todos los datos requeridos.</p>
                    </div>

                    <!-- Información Personal -->
                    <div class="card card-outline card-primary mb-3">
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
                                                placeholder="Ingrese el nombre" required>
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
                                                placeholder="Ingrese el apellido paterno" required>
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
                                                placeholder="Ingrese el apellido materno">
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
                                            <option value="DNI">DNI</option>
                                            <option value="PASAPORTE">Pasaporte</option>
                                            <option value="CI">Cédula de Identidad</option>
                                            <option value="RUC">RUC</option>
                                            <option value="OTROS">Otros</option>
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
                                                placeholder="Ingrese el número de documento" required>
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
                                                placeholder="Ingrese la dirección"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información Personal -->

                    <!-- Información de Contacto -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-address-book mr-2"></i>Información de Contacto</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Teléfono -->
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="telefono">Teléfono</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            </div>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                placeholder="Ingrese el teléfono">
                                        </div>
                                    </div>
                                </div>

                                <!-- Correo -->
                                <div class="col-md-8">
                                    <div class="form-group mb-0">
                                        <label for="correo">Correo Electrónico <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="email" class="form-control" id="correo" name="correo"
                                                placeholder="Ingrese el correo electrónico" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información de Contacto -->

                    <!-- Información de Acceso -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-key mr-2"></i>Información de Acceso</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Cargo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cargo">Cargo <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="cargo" name="cargo" required>
                                            <option value="">Seleccione un cargo</option>
                                            <option value="Administrador">Administrador</option>
                                            <option value="Recepcionista">Recepcionista</option>
                                            <option value="Limpieza">Limpieza</option>
                                        </select>
                                        <small class="form-text text-muted">El cargo determina los permisos iniciales del usuario</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Contraseña -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="clave">Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="clave" name="clave"
                                                placeholder="Ingrese la contraseña" required>
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
                                    <div class="form-group">
                                        <label for="confirmar_clave">Confirmar Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave"
                                                placeholder="Confirme la contraseña" required>
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

                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
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
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB</small>
                                    </div>
                                </div>

                                <!-- Previsualización de imagen -->
                                <div class="col-md-6">
                                    <div id="preview-container" style="display: none;">
                                        <label>Vista Previa:</label>
                                        <div class="text-center">
                                            <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail usuario-avatar-preview-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Información de Acceso -->

                    <!-- Asignación de Permisos -->
                    <div class="card card-outline card-primary">
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
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save mr-2"></i> Guardar Usuario
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
                    <!-- /Asignación de Permisos -->
                </div>
                <!-- /.col-md-8 -->

                <!-- Columna de guía (4/12) -->
                <div class="col-md-4">
                    <div class="card card-outline card-info usuario-guia-card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-route mr-2"></i>Guía rápida</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir esta sección">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Sigue estos pasos para registrar un usuario correctamente.</p>

                            <ul class="usuario-timeline">
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-primary"><i class="fas fa-id-card"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Datos personales</h6>
                                        <p>Nombre completo y documento de identidad. El apellido materno y la dirección son opcionales.</p>
                                        <span class="badge badge-light border">DNI: 8 dígitos · RUC: 11 dígitos</span>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-primary"><i class="fas fa-envelope"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Contacto</h6>
                                        <p>El correo debe ser único: se usará para iniciar sesión en el sistema.</p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-primary"><i class="fas fa-user-tag"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Cargo y permisos</h6>
                                        <p>El cargo define los permisos iniciales; puedes ajustarlos manualmente en la pestaña correspondiente.</p>
                                        <span class="badge badge-light border">Administrador · Recepcionista · Limpieza</span>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item">
                                    <span class="usuario-timeline-icon bg-warning"><i class="fas fa-lock"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Contraseña segura</h6>
                                        <p>Mínimo 6 caracteres. Combina mayúsculas, números y símbolos. Ej: <code>Aloja@2023</code></p>
                                    </div>
                                </li>
                                <li class="usuario-timeline-item usuario-timeline-item-last">
                                    <span class="usuario-timeline-icon bg-success"><i class="fas fa-image"></i></span>
                                    <div class="usuario-timeline-content">
                                        <h6>Imagen de perfil</h6>
                                        <p>Opcional. Usa una imagen cuadrada de al menos 200x200 px; si no subes ninguna, se asigna una por defecto.</p>
                                    </div>
                                </li>
                            </ul>

                            <div class="alert alert-secondary mb-0 mt-3">
                                <i class="fas fa-check-double mr-1"></i> Revisa los datos antes de guardar: el correo y el número de documento deben ser únicos.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<!-- /.content -->

<!-- Script para validaciones y vista previa -->
<!-- Script para manejo de permisos -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

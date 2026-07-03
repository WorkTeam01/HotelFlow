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
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Crear Usuario</h1>
                <p class="text-muted">Complete el formulario para registrar un nuevo usuario en el sistema</p>
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
        <div class="row">
            <!-- Columna del formulario (8/12) -->
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Formulario de Nuevo Usuario</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/usuarios/crear_usuario.php" method="POST" enctype="multipart/form-data" id="formCrearUsuario">
                        <div class="card-body">
                            <!-- Instrucciones -->
                            <div class="callout callout-info mb-4">
                                <h5><i class="fas fa-info-circle"></i> Información importante:</h5>
                                <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios. Asegúrese de completar todos los datos requeridos.</p>
                            </div>

                            <!-- Secciones con separación visual -->
                            <h5 class="border-bottom border-primary pb-2 mb-3"><i class="fas fa-id-card mr-2"></i>Información Personal</h5>

                            <!-- Fila de información básica -->
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
                                            <option value="Pasaporte">Pasaporte</option>
                                            <option value="CI">Cédula de Identidad</option>
                                            <option value="RUC">RUC</option>
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
                                    <div class="form-group">
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

                            <!-- Nueva sección de contacto -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-address-book mr-2"></i>Información de Contacto</h5>

                            <div class="row">
                                <!-- Teléfono -->
                                <div class="col-md-4">
                                    <div class="form-group">
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
                                    <div class="form-group">
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

                            <!-- Nueva sección de acceso -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-key mr-2"></i>Información de Acceso</h5>

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
                                        <label for="confirmar_clave">Confirmar Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave"
                                                placeholder="Confirme la contraseña" required>
                                        </div>
                                        <div class="password-feedback mt-1"></div>
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
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB</small>
                                    </div>
                                </div>

                                <!-- Previsualización de imagen -->
                                <div class="col-md-6">
                                    <div id="preview-container" style="display: none;">
                                        <label>Vista Previa:</label>
                                        <div class="text-center">
                                            <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de permisos con mejor estilo -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-lock mr-2"></i>Asignación de Permisos</h5>

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
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-2">
                                <div class="col-12 col-sm-auto">
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
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Nueva columna para la guía (4/12) -->
            <div class="col-md-4">
                <!-- Tarjeta de guía principal -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Guía de Usuario</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="text-info"><i class="fas fa-info-circle"></i> Cómo completar este formulario</h5>
                        <div class="callout callout-info">
                            <p>Complete todos los campos marcados con <span class="text-danger">*</span> ya que son obligatorios para registrar un nuevo usuario.</p>
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
                                        <p><strong>Nombre y Apellidos:</strong> Ingrese el nombre completo del usuario. El apellido materno es opcional.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Utilice la primera letra mayúscula y el resto en minúscula.</small>
                                        </div>
                                        <p><strong>Tipo y Número de Documento:</strong> Seleccione el tipo de documento e ingrese el número sin guiones ni espacios.</p>
                                        <ul class="pl-3">
                                            <li><small>DNI: 8 dígitos</small></li>
                                            <li><small>CI: Formato según país</small></li>
                                            <li><small>RUC: 11 dígitos</small></li>
                                            <li><small>Pasaporte: Alfanumérico</small></li>
                                        </ul>
                                        <p><strong>Dirección:</strong> Aunque es opcional, se recomienda ingresarla para tener información de contacto completa.</p>
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
                                        <p><strong>Teléfono:</strong> Preferiblemente celular, sin el código de país ni caracteres especiales.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Ejemplo: 70123456</small>
                                        </div>
                                        <p><strong>Correo Electrónico:</strong> Debe ser un correo válido y único en el sistema. Se usará para iniciar sesión.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Asegúrese de que el correo sea correcto, ya que será la vía principal de comunicación y acceso al sistema.</small>
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
                                        <p><strong>Cargo:</strong> Determina los permisos iniciales del usuario en el sistema.</p>
                                        <ul>
                                            <li><strong>Administrador:</strong> Acceso total al sistema. Puede gestionar usuarios, configuraciones y todas las operaciones.</li>
                                            <li><strong>Recepcionista:</strong> Gestión de clientes, habitaciones, reservas y servicios básicos.</li>
                                            <li><strong>Limpieza:</strong> Acceso a gestión de habitaciones y asignaciones de limpieza.</li>
                                        </ul>
                                        <p><strong>Estado:</strong> Determina si el usuario puede acceder al sistema.</p>
                                        <ul>
                                            <li><strong>Activo:</strong> Puede iniciar sesión y usar el sistema.</li>
                                            <li><strong>Inactivo:</strong> No puede iniciar sesión hasta que se active su cuenta.</li>
                                        </ul>
                                        <p><strong>Contraseña:</strong> Debe tener al menos 6 caracteres. Para mayor seguridad, combine letras, números y símbolos.</p>
                                        <div class="alert alert-danger">
                                            <small><i class="fas fa-lock mr-1"></i> Asegúrese de que ambos campos de contraseña coincidan exactamente.</small>
                                        </div>
                                        <p><strong>Imagen:</strong> Foto de perfil del usuario. No es obligatoria, se usará una imagen por defecto si no se sube ninguna.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Permisos -->
                            <div class="card">
                                <div class="card-header" id="headingPermisos">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapsePermisos" aria-expanded="false" aria-controls="collapsePermisos">
                                            <i class="fas fa-lock mr-2"></i>Permisos
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapsePermisos" class="collapse" aria-labelledby="headingPermisos" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p>Los permisos se asignan automáticamente según el cargo seleccionado, pero puede personalizarlos según las necesidades específicas del usuario.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Para usuarios con funciones mixtas, seleccione el cargo más cercano y luego ajuste los permisos manualmente.</small>
                                        </div>
                                        <p><strong>Permisos comunes:</strong></p>
                                        <ul>
                                            <li><small><strong>Usuarios:</strong> Gestión de usuarios del sistema</small></li>
                                            <li><small><strong>Perfil:</strong> Acceso a editar su propio perfil</small></li>
                                            <li><small><strong>Productos:</strong> Gestión del inventario de productos</small></li>
                                            <li><small><strong>Categorías:</strong> Gestión de categorías de productos</small></li>
                                            <li><small><strong>Habitaciones:</strong> Gestión de habitaciones</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de atajos y consejos -->
                <div class="card card-outline card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Consejos Útiles</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-key text-warning mr-2"></i> <strong>Contraseñas seguras</strong>
                                <p class="mb-0 small">Combine mayúsculas, minúsculas, números y símbolos. Ejemplo: <code>Aloja@2023</code></p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user-tag text-info mr-2"></i> <strong>Asignación de cargos</strong>
                                <p class="mb-0 small">Asigne el cargo adecuado según las responsabilidades del usuario en el alojamiento.</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-image text-success mr-2"></i> <strong>Imágenes de perfil</strong>
                                <p class="mb-0 small">Utilice imágenes cuadradas de al menos 200x200 píxeles para mejor visualización.</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check-double text-primary mr-2"></i> <strong>Verificación de datos</strong>
                                <p class="mb-0 small">Revise todos los datos antes de guardar, especialmente el correo y documento que deben ser únicos.</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Script para validaciones y vista previa -->
<script src="<?= $URL; ?>public/js/modules/usuarios/create-usuario.js"></script>
<!-- Script para manejo de permisos -->
<script src="<?= $URL; ?>public/js/modules/usuarios/permisos.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
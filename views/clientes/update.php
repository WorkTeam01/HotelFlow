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
$module_scripts = ['clientes/update-persona'];
$module_styles = ['clientes/cliente-styles'];

$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"> <i class="fas fa-user-friends"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Editar Cliente</li>
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
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-id-card"></i> Datos del Cliente</h3>
                    </div>

                    <!-- form start -->
                    <form id="formCliente" action="<?= $URL; ?>controllers/personas/actualizar_persona.php" method="POST" novalidate>
                        <input type="hidden" name="idpersona" value="<?= $persona['idpersona']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nombre">
                                            <i class="fas fa-user"></i> Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ingrese el nombre" value="<?= htmlspecialchars($persona['nombre']); ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese el nombre</div>
                                    </div>
                                </div>

                                <!-- Apellido Paterno -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="apellidopaterno">
                                            <i class="fas fa-user"></i> Apellido Paterno <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="apellidopaterno" name="apellidopaterno"
                                            placeholder="Ingrese el apellido paterno" value="<?= htmlspecialchars($persona['apellidopaterno']); ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese el apellido paterno</div>
                                    </div>
                                </div>

                                <!-- Apellido Materno -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="apellidomaterno">
                                            <i class="fas fa-user"></i> Apellido Materno
                                        </label>
                                        <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno"
                                            placeholder="Ingrese el apellido materno" value="<?= htmlspecialchars($persona['apellidomaterno'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Tipo de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipodocumento">
                                            <i class="fas fa-id-card"></i> Tipo de Documento <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="tipodocumento" name="tipodocumento" required>
                                            <option value="">Seleccione un tipo de documento</option>
                                            <option value="DNI" <?= $persona['tipodocumento'] == 'DNI' ? 'selected' : ''; ?>>DNI</option>
                                            <option value="Pasaporte" <?= $persona['tipodocumento'] == 'Pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                                            <option value="CI" <?= $persona['tipodocumento'] == 'CI' ? 'selected' : ''; ?>>Cédula de Identidad</option>
                                            <option value="RUC" <?= $persona['tipodocumento'] == 'RUC' ? 'selected' : ''; ?>>RUC</option>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione un tipo de documento</div>
                                    </div>
                                </div>

                                <!-- Número de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numdocumento">
                                            <i class="fas fa-hashtag"></i> Número de Documento <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="numdocumento" name="numdocumento"
                                            placeholder="Ingrese el número de documento" value="<?= htmlspecialchars($persona['numdocumento']); ?>"
                                            maxlength="25" required>
                                        <div class="invalid-feedback">Por favor ingrese el número de documento</div>
                                        <small id="formatoDocumento" class="form-text text-muted"></small>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><i class="fas fa-phone-alt"></i> Información de Contacto</h5>

                            <div class="row">
                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($persona['email'] ?? ''); ?>">
                                        <div class="invalid-feedback">Por favor ingrese un email válido</div>
                                    </div>
                                </div>

                                <!-- Teléfono -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telefono">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            placeholder="Ej: 71234567" value="<?= htmlspecialchars($persona['telefono'] ?? ''); ?>"
                                            maxlength="20">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Dirección -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="direccion">
                                            <i class="fas fa-map-marker-alt"></i> Dirección
                                        </label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                            placeholder="Ingrese la dirección completa"><?= htmlspecialchars($persona['direccion'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><i class="fas fa-info-circle"></i> Información Adicional</h5>

                            <div class="row">
                                <!-- Fecha de Nacimiento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechanacimiento">
                                            <i class="fas fa-birthday-cake"></i> Fecha de Nacimiento
                                        </label>
                                        <input type="date" class="form-control" id="fechanacimiento" name="fechanacimiento"
                                            value="<?= $persona['fechanacimiento'] ? htmlspecialchars($persona['fechanacimiento']) : ''; ?>">
                                        <div class="invalid-feedback">La fecha no puede ser futura</div>
                                    </div>
                                </div>

                                <!-- Género -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="genero">
                                            <i class="fas fa-venus-mars"></i> Género
                                        </label>
                                        <select class="form-control select2" id="genero" name="genero">
                                            <option value="">Seleccione una opción</option>
                                            <option value="Masculino" <?= $persona['genero'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?= $persona['genero'] == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                            <option value="Otros" <?= $persona['genero'] == 'Otros' ? 'selected' : ''; ?>>Otros</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">
                                            <i class="fas fa-toggle-on"></i> Estado
                                        </label>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="estadoSwitch" <?= $persona['estado'] == 1 ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="estadoSwitch">
                                                <span id="estadoLabel"><?= $persona['estado'] == 1 ? 'Activo' : 'Inactivo'; ?></span>
                                            </label>
                                            <input type="hidden" name="estado" id="estado" value="<?= $persona['estado']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save"></i> Actualizar Cliente
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/clientes" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Columna de información adicional -->
            <div class="col-md-4">
                <!-- Resumen del cliente -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-circle"></i> Resumen del Cliente</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <div class="profile-user-img img-fluid img-circle bg-warning d-flex align-items-center justify-content-center" style="width:100px; height:100px; margin: 0 auto;">
                                <i class="fas fa-user-tie fa-3x text-white"></i>
                            </div>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellidopaterno']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($persona['tipodocumento'] . ': ' . $persona['numdocumento']); ?>
                        </p>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Está editando la información de este cliente. Los cambios serán efectivos una vez que presione "Actualizar Cliente".
                        </div>
                    </div>
                </div>

                <!-- Información del sistema -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clock"></i> Información del Sistema</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <tr>
                                <td><i class="fas fa-calendar-plus"></i> Fecha de Creación</td>
                                <td><span class="badge badge-info"><?= date('d/m/Y H:i', strtotime($persona['fechacreacion'])); ?></span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-edit"></i> Última Actualización</td>
                                <td>
                                    <?php if (isset($persona['fechaactualizacion']) && !empty($persona['fechaactualizacion'])): ?>
                                        <span class="badge badge-warning"><?= date('d/m/Y H:i', strtotime($persona['fechaactualizacion'])); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Sin actualizar</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Guía de ayuda -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><i class="fas fa-check text-success"></i> Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        <p><i class="fas fa-check text-success"></i> El email debe tener un formato válido.</p>
                        <p><i class="fas fa-check text-success"></i> La fecha de nacimiento no puede ser futura.</p>
                        <p><i class="fas fa-check text-success"></i> Verifique el tipo y número de documento.</p>
                    </div>
                </div>
            </div>
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
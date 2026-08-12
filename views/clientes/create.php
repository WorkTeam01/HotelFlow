<?php
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

// Definir scripts y estilos específicos para este módulo
$module_scripts = ['clientes/create-persona'];
$module_styles = ['clientes/cliente-styles'];

$skip_chartjs = true;
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Registrar Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"> <i class="fas fa-user-friends"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Registrar Cliente</li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-id-card"></i> Datos del Cliente</h3>
                    </div>

                    <!-- form start -->
                    <form id="formCliente" action="<?= $URL; ?>controllers/personas/crear_persona.php" method="POST" novalidate>
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
                                            placeholder="Ingrese el nombre" required>
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
                                            placeholder="Ingrese el apellido paterno" required>
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
                                            placeholder="Ingrese el apellido materno">
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
                                            <option value="DNI">DNI</option>
                                            <option value="Pasaporte">Pasaporte</option>
                                            <option value="CI">Cédula de Identidad</option>
                                            <option value="RUC">RUC</option>
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
                                            placeholder="Ingrese el número de documento" required>
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
                                            placeholder="ejemplo@correo.com">
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
                                            placeholder="Ej: 71234567">
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
                                            placeholder="Ingrese la dirección completa"></textarea>
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
                                        <input type="date" class="form-control" id="fechanacimiento" name="fechanacimiento">
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
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Otros">Otros</option>
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
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Cliente
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/clientes/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Columna de guía e información -->
            <div class="col-md-4">
                <!-- Ayuda del formulario -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Guía de Registro</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info"></i> Información importante:</h5>
                            <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tipo de Documento</span>
                                <span class="info-box-text text-sm text-wrap">
                                    <ul class="pl-3">
                                        <li><strong>DNI:</strong> 8 dígitos numéricos</li>
                                        <li><strong>CI:</strong> Cédula de Identidad boliviana</li>
                                        <li><strong>Pasaporte:</strong> Código alfanumérico</li>
                                        <li><strong>RUC:</strong> 11 dígitos para empresas</li>
                                    </ul>
                                </span>
                            </div>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Recomendaciones</span>
                                <span class="info-box-text text-sm text-wrap">
                                    <ul class="pl-3">
                                        <li>Verifique la correcta escritura de los nombres y apellidos.</li>
                                        <li>El email debe tener un formato válido.</li>
                                        <li>La fecha de nacimiento no puede ser una fecha futura.</li>
                                    </ul>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de clientes -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-star"></i> Beneficios de Registrar Clientes</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><i class="fas fa-check text-success"></i> Gestión eficiente de reservas</p>
                        <p><i class="fas fa-check text-success"></i> Seguimiento de historial de hospedaje</p>
                        <p><i class="fas fa-check text-success"></i> Facilita la facturación</p>
                        <p><i class="fas fa-check text-success"></i> Permite aplicar descuentos a clientes frecuentes</p>
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
<?php
// Incluir el encabezado
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? 0;

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de servicio de baño no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Verificar si el usuario tiene permiso para editar servicios de baño
$auth = new AuthorizationService();

if (!($auth->puedeAccederModulo($idusuario, 'servicios_bano'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para editar servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Agregar los estilos específicos para este módulo
$module_styles = ['servicios-bano/servicios-bano'];

// Incluir el encabezado
include_once '../layouts/header.php';

// Instanciar el controlador y obtener los datos del servicio
$controller = new ServicioBanoController();
$datos = $controller->editar($id);

// Verificar si el servicio existe
if (!$datos) {
    $_SESSION['mensaje'] = 'Servicio de baño no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

$servicio = $datos['servicio'];
$banos_disponibles = $datos['banos_disponibles'];
$clientes = $datos['clientes'];

// Determinar clases y estilos según el estado
$card_class = 'card-primary';
$estado_texto = 'Activo';
$estado_clase = 'badge-success';
$estado_icono = 'fa-check-circle';

if ($servicio['estado'] == 0) {
    $card_class = 'card-danger';
    $estado_texto = 'Inactivo';
    $estado_clase = 'badge-danger';
    $estado_icono = 'fa-times-circle';
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Servicio de Baño</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano"><i class="fas fa-bath"></i> Servicios de Baño</a></li>
                    <li class="breadcrumb-item active">Editar Servicio</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card <?= $card_class; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> Formulario de Edición de Servicio de Baño</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/servicios-bano/actualizar_servicio.php" method="POST" id="formEditarServicio">
                        <input type="hidden" name="idservicio" value="<?= $servicio['idservicio']; ?>">
                        <div class="card-body">
                            <!-- Estado actual del servicio -->
                            <div class="mb-3 text-center">
                                <span class="badge badge-estado <?= $estado_clase; ?>">
                                    <i class="fas <?= $estado_icono; ?>"></i> Estado actual: <?= $estado_texto; ?>
                                </span>
                            </div>

                            <div class="row">
                                <!-- Baño -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idbano" class="required-field">Baño</label>
                                        <select class="form-control select2" id="idbano" name="idbano" required>
                                            <option value="">Seleccione un baño</option>
                                            <?php
                                            // Agregar el baño actual si no está en la lista de disponibles
                                            $bano_actual_en_lista = false;

                                            foreach ($banos_disponibles as $bano):
                                                if ($bano['idbano'] == $servicio['idbano']) {
                                                    $bano_actual_en_lista = true;
                                                }
                                            ?>
                                                <option value="<?= $bano['idbano']; ?>"
                                                    data-precio="<?= $bano['precio']; ?>"
                                                    <?= $bano['idbano'] == $servicio['idbano'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($bano['nombre']); ?>
                                                    <?php if (!empty($bano['ubicacion'])): ?>
                                                        - <?= htmlspecialchars($bano['ubicacion']); ?>
                                                    <?php endif; ?>
                                                    (<?= number_format($bano['precio'], 0); ?>)
                                                </option>
                                            <?php endforeach; ?>

                                            <?php if (!$bano_actual_en_lista && $servicio['idbano']): ?>
                                                <option value="<?= $servicio['idbano']; ?>" selected>
                                                    <?= htmlspecialchars($servicio['nombre_bano']); ?> -
                                                    <?= number_format($servicio['precio_bano'], 0); ?>
                                                </option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tipo de Cliente -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_cliente" class="required-field">Tipo de Cliente</label>
                                        <select class="form-control select2" id="tipo_cliente" name="tipo_cliente" required>
                                            <option value="Publico" <?= $servicio['tipo_cliente'] == 'Publico' ? 'selected' : ''; ?>>Público</option>
                                            <option value="Huesped" <?= $servicio['tipo_cliente'] == 'Huesped' ? 'selected' : ''; ?>>Huésped</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Cliente (opcional) -->
                                <div class="col-md-12" id="div_cliente" style="<?= $servicio['tipo_cliente'] != 'Huesped' ? 'display: none;' : ''; ?>">
                                    <div class="form-group">
                                        <label for="idcliente">Cliente</label>
                                        <select class="form-control select2" id="idcliente" name="idcliente">
                                            <option value="">Seleccione un cliente (opcional)</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <option value="<?= $cliente['idpersona']; ?>" <?= $cliente['idpersona'] == $servicio['idcliente'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($cliente['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Opcional: Seleccione si el cliente está registrado</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Método de Pago -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="metodopago" class="required-field">Método de Pago</label>
                                        <select class="form-control select2" id="metodopago" name="metodopago" required>
                                            <option value="Efectivo" <?= $servicio['metodopago'] == 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                            <option value="QR" <?= $servicio['metodopago'] == 'QR' ? 'selected' : ''; ?>>QR</option>
                                            <option value="OTROS" <?= $servicio['metodopago'] == 'OTROS' ? 'selected' : ''; ?>>Otros</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="total" class="required-field">Total</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="total" name="total"
                                                placeholder="0.00" step="0.01" min="0" value="<?= $servicio['total']; ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pago Recibido -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pagorecibido" class="required-field">Pago Recibido</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="pagorecibido" name="pagorecibido"
                                                placeholder="0.00" step="0.01" min="0" value="<?= $servicio['pagorecibido']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Cambio (calculado automáticamente) -->
                                <div class="col-md-6" id="cambio_display">
                                    <div class="form-group">
                                        <label for="cambio_display">Cambio</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="text" class="form-control" id="cambio_display" readonly
                                                value="<?= number_format($servicio['cambio'], 2); ?>">
                                        </div>
                                        <small class="form-text text-muted">Calculado automáticamente</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado del Servicio</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" <?= $servicio['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?= $servicio['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional (solo lectura) -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-calendar-alt"></i> Fecha de Servicio:</strong>
                                                    <p class="text-muted"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-user"></i> Usuario que Registró:</strong>
                                                    <p class="text-muted"><?= htmlspecialchars($servicio['nombre_usuario'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-tag"></i> Estado Actual:</strong>
                                                    <p>
                                                        <span class="badge <?= $estado_clase; ?>">
                                                            <i class="fas <?= $estado_icono; ?>"></i> <?= $estado_texto; ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
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
                                        <i class="fas fa-save"></i> Actualizar Servicio
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <div class="col-md-4">
                <!-- Resumen del servicio -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Resumen del Servicio</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-bath fa-3x text-info"></i>
                            <h3 class="profile-username text-center mt-2">Servicio #<?= $servicio['idservicio']; ?></h3>
                        </div>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-toilet text-info"></i> Baño</b>
                                <span class="float-right"><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt text-info"></i> Fecha</b>
                                <span class="float-right"><?= date('d/m/Y H:i', strtotime($servicio['fecha'])); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-dollar-sign text-info"></i> Total</b>
                                <span class="float-right"><?= number_format($servicio['total'], 0); ?></span>
                            </li>
                        </ul>

                        <a href="<?= $URL; ?>views/servicios-bano/show.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-info btn-block">
                            <i class="fas fa-eye"></i> Ver Detalle Completo
                        </a>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5>Instrucciones:</h5>
                            <ul>
                                <li>Modifique los campos necesarios</li>
                                <li>El cambio se calcula automáticamente</li>
                                <li>El pago recibido debe ser igual o mayor al total</li>
                                <li>El precio se ajustará automáticamente al cambiar el baño</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<script src="<?= $URL; ?>public/js/modules/servicios-bano/update-servicios-bano.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
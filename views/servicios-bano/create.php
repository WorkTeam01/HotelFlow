<?php
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

$idusuario = $_SESSION['usuario_id'] ?? ''; // Obtener el ID del usuario desde la sesión

// Verificar si el usuario tiene permiso para crear servicios de baño
$auth = new AuthorizationService();

if (!($auth->puedeAccederModulo($idusuario, 'servicios_bano'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para crear servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Agregar los estilos específicos para este módulo
$module_styles = ['servicios-bano/servicios-bano'];

// Incluir el encabezado
$skip_chartjs = true;
include_once '../layouts/header.php';

// Instanciar el controlador
$controller = new ServicioBanoController();

// Obtener datos para el formulario
$data = $controller->crear(); // Este método nos devolverá los baños disponibles y clientes

// Extraer datos
$banos_disponibles = $data['banos_disponibles'];
$clientes = $data['clientes'];

// Verificar si hay baños disponibles
if (empty($banos_disponibles)) {
    $_SESSION['mensaje'] = 'No hay baños disponibles para asignar a un servicio.';
    $_SESSION['icono'] = 'warning';
}

// Verificar disponibilidad del servicio antes de mostrar el formulario
$disponibilidad = $controller->validarDisponibilidadServicio();

if (!$disponibilidad['disponible']) {
    $_SESSION['mensaje'] = 'No se puede crear servicios de baño: ' . $disponibilidad['message'];
    $_SESSION['icono'] = 'error';
?>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Servicio No Disponible</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= $URL; ?>">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano">Servicios de Baños</a></li>
                        <li class="breadcrumb-item active">No Disponible</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Servicio No Disponible</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <h5><i class="icon fas fa-ban"></i> No se pueden crear servicios de baño</h5>
                                <?= htmlspecialchars($disponibilidad['message']); ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Posibles soluciones:</h6>
                                    <ul>
                                        <li>Verificar que el producto "Rollo de papel" esté registrado</li>
                                        <li>Asegurar que haya stock suficiente</li>
                                        <li>Verificar que el producto esté activo</li>
                                        <li>Contactar al administrador del sistema</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Estado del Servicio</span>
                                            <span class="info-box-number">No Disponible</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?= $URL; ?>views/servicios-bano" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Servicios
                            </a>
                            <a href="<?= $URL; ?>views/productos" class="btn btn-primary">
                                <i class="fas fa-boxes"></i> Gestionar Productos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
    include_once '../layouts/mensajes.php';
    include_once '../layouts/footer.php';
    exit; // Detener la ejecución del resto del archivo
}

// Si llegamos aquí, el servicio está disponible, mostrar alerta si el stock está bajo
if (isset($disponibilidad['stock_actual']) && isset($disponibilidad['stock_minimo'])) {
    $stockActual = $disponibilidad['stock_actual'];
    $stockMinimo = $disponibilidad['stock_minimo'];

    if ($stockActual <= $stockMinimo && $stockMinimo > 0) {
        $stockBajoActual = $stockActual;
    }
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Servicio de Baño</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/servicios-bano"><i class="fas fa-bath"></i> Servicios de Baños</a></li>
                    <li class="breadcrumb-item active">Nuevo Servicio</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Alerta de información -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-info"></i> Información Importante</h5>
                    Complete todos los campos marcados con <span class="text-danger">*</span> para registrar un servicio de baño.
                    El cambio se calculará automáticamente.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary sticky-top">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Registro de Servicio</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/servicios-bano/crear_servicio.php" method="POST" id="formServicioBano">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Baño -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idbano" class="required-field">Baño</label>
                                        <select class="form-control select2" id="idbano" name="idbano" required>
                                            <option value="">Seleccione un baño</option>
                                            <?php foreach ($banos_disponibles as $bano) : ?>
                                                <option value="<?= $bano['idbano']; ?>" data-precio="<?= $bano['precio']; ?>">
                                                    <?= htmlspecialchars($bano['nombre'] . ' - ' . $bano['ubicacion']); ?>
                                                    (<?= number_format($bano['precio'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tipo de Cliente -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_cliente" class="required-field">Tipo de Cliente</label>
                                        <select class="form-control select2" id="tipo_cliente" name="tipo_cliente" required>
                                            <option value="Publico">Público</option>
                                            <option value="Huesped">Huésped</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Cliente (solo visible si es huésped) -->
                                <div class="col-md-12" id="div_cliente" style="display: none;">
                                    <div class="form-group">
                                        <label for="idcliente">Cliente</label>
                                        <select class="form-control select2" id="idcliente" name="idcliente">
                                            <option value="">Seleccione un cliente</option>
                                            <?php foreach ($clientes as $cliente) : ?>
                                                <option value="<?= $cliente['idpersona']; ?>"><?= htmlspecialchars($cliente['nombre_completo']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Método de Pago -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="metodopago" class="required-field">Método de Pago</label>
                                        <select class="form-control select2" id="metodopago" name="metodopago" required>
                                            <option value="Efectivo" selected>Efectivo</option>
                                            <option value="QR">QR</option>
                                            <option value="OTROS">Otros</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="total" class="required-field">Total a Pagar</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="total" name="total"
                                                min="0" step="0.01" value="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Pago Recibido -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pagorecibido" class="required-field">Pago Recibido</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="pagorecibido" name="pagorecibido"
                                                min="0" step="0.01" value="0" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cambio (calculado automáticamente) -->
                                <div class="col-md-6" id="div_cambio">
                                    <div class="form-group">
                                        <label for="cambio">Cambio</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="text" class="form-control" id="cambio" readonly value="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Fecha y Hora (oculto, se llena automáticamente) -->
                                <input type="hidden" name="fecha" value="<?= date('Y-m-d H:i:s'); ?>">
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Servicio
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
                <!-- Tarjeta de ayuda -->
                <div class="card">
                    <div class="card-header card-outline card-info">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda y Tips</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Recordatorios:</h5>
                            <ul>
                                <li>El <strong>precio</strong> se carga automáticamente al seleccionar un baño</li>
                                <li>El <strong>pago recibido</strong> debe ser igual o mayor al total</li>
                                <li>El <strong>cambio</strong> se calcula automáticamente</li>
                            </ul>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Importante:</h5>
                            <p>Cada servicio consume un rollo de papel higiénico del inventario.</p>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-question-circle"></i> ¿Necesitas ayuda?</h6>
                                <p class="text-muted">Si tienes dudas sobre cómo usar este formulario, contacta al administrador del sistema.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ./card -->

                <?php if (isset($disponibilidad['stock_actual'])): ?>
                    <!-- Tarjeta de estado del inventario -->
                    <div class="card <?= ($stockActual <= $stockMinimo) ? 'card-warning' : 'card-success'; ?>">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Estado del Inventario</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-box <?= ($stockActual <= $stockMinimo) ? 'bg-warning' : 'bg-success'; ?>">
                                <span class="info-box-icon"><i class="fas fa-toilet-paper"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Stock de Papel Higiénico</span>
                                    <span class="info-box-number"><?= $stockActual; ?> unidades</span>
                                    <div class="progress">
                                        <?php
                                        $porcentaje = min(100, ($stockActual / max(1, $stockMinimo * 2)) * 100);
                                        ?>
                                        <div class="progress-bar" style="width: <?= $porcentaje; ?>%"></div>
                                    </div>
                                    <span class="progress-description">
                                        Mínimo recomendado: <?= $stockMinimo; ?> unidades
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php if (isset($stockBajoActual)) : ?>
<script>
    window.stockBajoActual = <?= (int)$stockBajoActual; ?>;
</script>
<?php endif; ?>
<script src="<?= $URL; ?>public/js/modules/servicios-bano/create-servicios-bano.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
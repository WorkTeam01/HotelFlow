<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Definir recursos específicos para esta vista
$module_styles = ['recepciones/update-recepcion'];
$module_scripts = ['recepciones/update-recepcion'];

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!($authService->puedeAccederModulo($idusuario, 'recepcion'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de recepción no válido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

$id = (int) $_GET['id'];

// Instanciar el controlador
$controller = new RecepcionController();

// Obtener datos para la vista
$datos = $controller->editar($id);

if (!$datos) {
    $_SESSION['mensaje'] = 'Recepción no encontrada.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

$recepcion = $datos['recepcion'];
$clientes = $datos['clientes'];
$tarifas = $datos['tarifas'];
$habitaciones_disponibles = $datos['habitaciones_disponibles'];

// Verificar si la recepción está en un estado que permite edición
if ($recepcion['estado'] === 'finalizado' || $recepcion['estado'] === 'cancelado') {
    $_SESSION['mensaje'] = 'No se puede editar una recepción finalizada o cancelada.';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . 'views/recepcion/show.php?id=' . $id);
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

// Determinar clase y etiqueta según el estado
$clase_estado = '';
$etiqueta_estado = '';
switch ($recepcion['estado']) {
    case 'reservado':
        $clase_estado = 'info';
        $etiqueta_estado = 'Reservado';
        break;
    case 'en_curso':
        $clase_estado = 'warning';
        $etiqueta_estado = 'En curso (Check-in)';
        break;
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <i class="fas fa-edit text-warning mr-2"></i>
                    Editar Recepción
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion"><i class="fas fa-bed"></i> Recepción</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">Detalle</a></li>
                    <li class="breadcrumb-item active">Editar Recepción</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Tarjeta de información de recepción -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-info-circle fa-3x text-warning"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Editando Recepción #<?= $recepcion['idrecepcion']; ?></h5>
                                <p class="mb-0">
                                    Habitación: <strong><?= htmlspecialchars($recepcion['numero_habitacion']); ?></strong> |
                                    Cliente: <strong><?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?></strong> |
                                    Estado: <span class="badge badge-<?= $clase_estado; ?>"><?= $etiqueta_estado; ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="formEditarRecepcion" action="<?= $URL; ?>controllers/recepcion/actualizar_recepcion.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <!-- ID de la recepción -->
            <input type="hidden" name="idrecepcion" value="<?= $recepcion['idrecepcion']; ?>">

            <div class="row">
                <!-- Panel izquierdo -->
                <div class="col-md-8">
                    <!-- Datos principales -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>Datos Principales
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Cliente -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcliente">
                                            <i class="fas fa-user mr-1 text-primary"></i>
                                            Cliente <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="idcliente" name="idcliente" required>
                                            <option value="">Seleccione un cliente</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <option value="<?= $cliente['idpersona']; ?>"
                                                    data-nombre="<?= htmlspecialchars($cliente['nombre_completo']); ?>"
                                                    data-tipodoc="<?= htmlspecialchars($cliente['tipodocumento']); ?>"
                                                    data-numdoc="<?= htmlspecialchars($cliente['numdocumento']); ?>"
                                                    <?= ($recepcion['idcliente'] == $cliente['idpersona']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($cliente['nombre_completo'] . ' - ' .
                                                        $cliente['tipodocumento'] . ': ' .
                                                        $cliente['numdocumento']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione un cliente</div>
                                        <div id="cliente-info" class="form-text text-muted mt-2" style="display: none;">
                                            <span id="cliente-documento"></span> | <span id="cliente-nombre"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Habitación -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idhabitacion">
                                            <i class="fas fa-bed mr-1 text-primary"></i>
                                            Habitación <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="idhabitacion" name="idhabitacion" required>
                                            <?php foreach ($habitaciones_disponibles as $habitacion): ?>
                                                <option value="<?= $habitacion['id_habitacion']; ?>"
                                                    data-numero="<?= htmlspecialchars($habitacion['numero']); ?>"
                                                    data-tipo="<?= htmlspecialchars($habitacion['tipo_nombre']); ?>"
                                                    data-piso="<?= htmlspecialchars($habitacion['piso_nombre']); ?>"
                                                    <?= ($recepcion['idhabitacion'] == $habitacion['id_habitacion']) ? 'selected' : ''; ?>
                                                    <?= (isset($habitacion['es_actual']) && $habitacion['es_actual']) ? 'data-actual="true"' : ''; ?>>
                                                    <?= htmlspecialchars($habitacion['numero'] . ' - ' . $habitacion['tipo_nombre'] . ' - Piso ' . $habitacion['piso_nombre']); ?>
                                                    <?= (isset($habitacion['es_actual']) && $habitacion['es_actual']) ? ' (Actual)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione una habitación</div>
                                        <small class="form-text text-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Cambiar la habitación liberará la habitación actual y ocupará la nueva.
                                        </small>
                                        <div id="habitacion-info" class="form-text text-muted mt-2" style="display: none;">
                                            <span id="habitacion-numero"></span> | <span id="habitacion-tipo"></span> | <span id="habitacion-piso"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Tarifa -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idtarifa">
                                            <i class="fas fa-tag mr-1 text-primary"></i>
                                            Tarifa <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control select2" id="idtarifa" name="idtarifa" required>
                                            <option value="">Seleccione una tarifa</option>
                                            <?php foreach ($tarifas as $tarifa): ?>
                                                <option value="<?= $tarifa['idtarifa']; ?>"
                                                    <?= ($recepcion['idtarifa'] == $tarifa['idtarifa']) ? 'selected' : ''; ?>
                                                    data-precio="<?= $tarifa['precio']; ?>"
                                                    data-tipo="<?= $tarifa['id_tipo']; ?>"
                                                    data-tipo-nombre="<?= htmlspecialchars($tarifa['tipo_habitacion_nombre']); ?>"
                                                    data-estancia="<?= $tarifa['tipo_estancia']; ?>"
                                                    data-duracion="<?= $tarifa['duracion']; ?>">
                                                    <?= htmlspecialchars($tarifa['tipo_habitacion_nombre'] . ' - ' .
                                                        ($tarifa['tipo_estancia'] == 'horas' ? $tarifa['duracion'] . ' hora(s)' : $tarifa['duracion'] . ' día(s)') .
                                                        ' - Bs ' . number_format($tarifa['precio'], 2)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione una tarifa</div>
                                        <div id="tarifa-info" class="form-text text-muted mt-2" style="display: none;">
                                            <span id="tarifa-tipo"></span> | <span id="tarifa-duracion"></span> | <span id="tarifa-precio"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">
                                            <i class="fas fa-toggle-on mr-1 text-primary"></i>
                                            Estado <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="estado" name="estado" required>
                                            <option value="reservado" <?= ($recepcion['estado'] == 'reservado') ? 'selected' : ''; ?>>Reservado</option>
                                            <option value="en_curso" <?= ($recepcion['estado'] == 'en_curso') ? 'selected' : ''; ?>>Check-in (En curso)</option>
                                        </select>
                                        <div class="invalid-feedback">Por favor seleccione un estado</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Fecha de entrada -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechaentrada">
                                            <i class="fas fa-calendar-check mr-1 text-primary"></i>
                                            Fecha y Hora de Entrada <span class="text-danger">*</span>
                                        </label>
                                        <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                                            value="<?= date('Y-m-d\TH:i', strtotime($recepcion['fechaentrada'])); ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese la fecha y hora de entrada</div>
                                    </div>
                                </div>

                                <!-- Fecha de salida prevista -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechasalida_prevista">
                                            <i class="fas fa-calendar-times mr-1 text-primary"></i>
                                            Fecha de Salida Prevista <span class="text-danger">*</span>
                                        </label>
                                        <input type="datetime-local" class="form-control" id="fechasalida_prevista" name="fechasalida_prevista"
                                            value="<?= date('Y-m-d\TH:i', strtotime($recepcion['fechasalida_prevista'])); ?>" required>
                                        <div class="invalid-feedback">Por favor ingrese la fecha de salida prevista</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Observaciones -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">
                                            <i class="fas fa-comment mr-1 text-primary"></i>
                                            Observaciones
                                        </label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= htmlspecialchars($recepcion['observaciones'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información financiera -->
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-dollar-sign mr-2"></i>Información Financiera
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Monto total -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="montototal">
                                            <i class="fas fa-money-bill-wave mr-1 text-success"></i>
                                            Monto Total <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="montototal" name="montototal"
                                                step="0.01" min="0" value="<?= $recepcion['montototal']; ?>" required>
                                            <div class="invalid-feedback">Por favor ingrese el monto total</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monto pagado -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="montopagado">
                                            <i class="fas fa-hand-holding-usd mr-1 text-success"></i>
                                            Monto Pagado
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="montopagado" name="montopagado"
                                                step="0.01" min="0" value="<?= $recepcion['montopagado']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Método de pago -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="metodopago">
                                            <i class="fas fa-credit-card mr-1 text-success"></i>
                                            Método de Pago
                                        </label>
                                        <select class="form-control" id="metodopago" name="metodopago">
                                            <option value="">Sin método de pago</option>
                                            <option value="Efectivo" <?= ($recepcion['metodopago'] == 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                                            <option value="QR" <?= ($recepcion['metodopago'] == 'QR') ? 'selected' : ''; ?>>QR</option>
                                            <option value="Otros" <?= ($recepcion['metodopago'] == 'Otros') ? 'selected' : ''; ?>>Otros</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de cambio (visible solo cuando se selecciona Efectivo) -->
                            <div id="seccion-cambio" class="row mt-3" <?= ($recepcion['metodopago'] != 'Efectivo') ? 'style="display: none;"' : ''; ?>>
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-exchange-alt mr-2 text-warning"></i>
                                                Detalle del pago en efectivo
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="monto_recibido">Monto Recibido</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">Bs</span>
                                                            </div>
                                                            <input type="number" class="form-control" id="monto_recibido" name="monto_recibido"
                                                                step="0.01" min="0" value="<?= $recepcion['montopagado'] + ($recepcion['cambio'] ?? 0); ?>">
                                                        </div>
                                                        <small class="text-muted">Dinero entregado por el cliente</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="cambio">Cambio</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">Bs</span>
                                                            </div>
                                                            <input type="number" class="form-control" id="cambio" name="cambio"
                                                                step="0.01" min="0" value="<?= $recepcion['cambio'] ?? '0.00'; ?>" readonly>
                                                        </div>
                                                        <small class="text-muted">Dinero devuelto al cliente</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen financiero -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle mr-2"></i>Resumen financiero</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Total:</strong> <span id="resumen-total">Bs <?= number_format($recepcion['montototal'], 2); ?></span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Pagado:</strong> <span id="resumen-pagado">Bs <?= number_format($recepcion['montopagado'], 2); ?></span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Saldo:</strong> <span id="resumen-saldo" class="<?= ($recepcion['montototal'] - $recepcion['montopagado'] > 0) ? 'text-danger' : 'text-success'; ?>">
                                                    Bs <?= number_format(max(0, $recepcion['montototal'] - $recepcion['montopagado']), 2); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Barra de progreso de pago -->
                                        <div class="progress mt-3" style="height: 20px;">
                                            <?php
                                            $porcentajePagado = ($recepcion['montototal'] > 0) ? ($recepcion['montopagado'] / $recepcion['montototal']) * 100 : 0;
                                            $porcentajePagado = min(100, $porcentajePagado); // No exceder 100%
                                            ?>
                                            <div class="progress-bar <?= $porcentajePagado >= 100 ? 'bg-success' : 'bg-warning'; ?>"
                                                role="progressbar"
                                                style="width: <?= $porcentajePagado; ?>%"
                                                aria-valuenow="<?= $porcentajePagado; ?>"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                <?= number_format($porcentajePagado, 1); ?>% pagado
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho -->
                <div class="col-md-4">
                    <!-- Resumen -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clipboard-list mr-2"></i>Resumen de Recepción
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0">
                                    <i class="fas fa-user text-primary mr-2"></i> <strong>Cliente:</strong>
                                    <div id="resumen-cliente" class="text-muted mt-1"><?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?></div>
                                </li>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-bed text-primary mr-2"></i> <strong>Habitación:</strong>
                                    <div id="resumen-habitacion" class="text-muted mt-1"><?= htmlspecialchars($recepcion['numero_habitacion']); ?></div>
                                </li>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-tag text-primary mr-2"></i> <strong>Tarifa:</strong>
                                    <div id="resumen-tarifa" class="text-muted mt-1"><?= htmlspecialchars($recepcion['tipo_tarifa']); ?></div>
                                </li>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-calendar-alt text-primary mr-2"></i> <strong>Fechas:</strong>
                                    <div class="text-muted mt-1">
                                        <span id="resumen-entrada"><?= date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])); ?></span>
                                        <span class="mx-2">→</span>
                                        <span id="resumen-salida"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?></span>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-toggle-on text-primary mr-2"></i> <strong>Estado:</strong>
                                    <div id="resumen-estado" class="mt-1">
                                        <span class="badge badge-<?= $clase_estado; ?> px-3 py-2"><?= $etiqueta_estado; ?></span>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-money-bill-wave text-success mr-2"></i> <strong>Monto:</strong>
                                    <div id="resumen-monto" class="text-muted mt-1">Bs <?= number_format($recepcion['montototal'], 2); ?></div>
                                </li>
                            </ul>

                            <div id="resumen-observaciones-container" class="mt-3" <?= empty($recepcion['observaciones']) ? 'style="display: none;"' : ''; ?>>
                                <div class="callout callout-info p-2">
                                    <h6 class="text-primary"><i class="fas fa-comment mr-2"></i>Observaciones:</h6>
                                    <p id="resumen-observaciones" class="mb-0 text-muted small"><?= htmlspecialchars($recepcion['observaciones'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save mr-1"></i> Actualizar
                                </button>
                                <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Información de ayuda -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-question-circle mr-2"></i>Información de Ayuda
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle text-info mr-2"></i>Cambio de habitación</h6>
                            <p class="text-muted small">
                                Si cambia la habitación, el sistema liberará la habitación actual y ocupará la nueva
                                automáticamente cuando guarde los cambios.
                            </p>

                            <h6 class="mt-3"><i class="fas fa-info-circle text-info mr-2"></i>Cambio de tarifa</h6>
                            <p class="text-muted small">
                                Al cambiar la tarifa, el monto total se actualizará automáticamente con el precio
                                de la tarifa seleccionada. Si necesita un precio diferente, puede modificarlo manualmente.
                            </p>

                            <h6 class="mt-3"><i class="fas fa-info-circle text-info mr-2"></i>Pagos</h6>
                            <p class="text-muted small">
                                Si el monto pagado es menor al monto total, el sistema registrará el saldo pendiente.
                                El cliente deberá pagar este saldo antes del check-out.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Seleccione una opción'
        });

        // Mostrar info adicional del cliente seleccionado
        function mostrarInfoCliente() {
            var selectedOption = $('#idcliente option:selected');
            if (selectedOption.val()) {
                var nombre = selectedOption.data('nombre');
                var tipoDoc = selectedOption.data('tipodoc');
                var numDoc = selectedOption.data('numdoc');

                $('#cliente-documento').text(tipoDoc + ': ' + numDoc);
                $('#cliente-nombre').text(nombre);
                $('#cliente-info').show();

                // Actualizar resumen
                $('#resumen-cliente').text(nombre);
            } else {
                $('#cliente-info').hide();
            }
        }

        // Mostrar info adicional de la habitación seleccionada
        function mostrarInfoHabitacion() {
            var selectedOption = $('#idhabitacion option:selected');
            if (selectedOption.val()) {
                var numero = selectedOption.data('numero');
                var tipo = selectedOption.data('tipo');
                var piso = selectedOption.data('piso');

                $('#habitacion-numero').text('N° ' + numero);
                $('#habitacion-tipo').text(tipo);
                $('#habitacion-piso').text('Piso ' + piso);
                $('#habitacion-info').show();

                // Actualizar resumen
                $('#resumen-habitacion').text(numero + ' - ' + tipo);
            } else {
                $('#habitacion-info').hide();
            }
        }

        // Mostrar info adicional de la tarifa seleccionada
        function mostrarInfoTarifa() {
            var selectedOption = $('#idtarifa option:selected');
            if (selectedOption.val()) {
                var precio = parseFloat(selectedOption.data('precio')) || 0;
                var tipoNombre = selectedOption.data('tipo-nombre');
                var tipoEstancia = selectedOption.data('estancia');
                var duracion = parseInt(selectedOption.data('duracion')) || 0;

                var duracionTexto = tipoEstancia === 'horas' ?
                    duracion + ' hora(s)' :
                    duracion + ' día(s)';

                $('#tarifa-tipo').text(tipoNombre);
                $('#tarifa-duracion').text(duracionTexto);
                $('#tarifa-precio').text('Bs ' + precio.toFixed(2));
                $('#tarifa-info').show();

                // Actualizar monto total
                $('#montototal').val(precio.toFixed(2));

                // Actualizar resumen
                $('#resumen-tarifa').text(tipoNombre + ' - ' + duracionTexto);
                $('#resumen-monto').text('Bs ' + precio.toFixed(2));

                // Actualizar fecha de salida prevista basada en la tarifa
                actualizarFechaSalida();
            } else {
                $('#tarifa-info').hide();
            }
        }

        // Actualizar fecha de salida basada en la tarifa
        function actualizarFechaSalida() {
            var selectedOption = $('#idtarifa option:selected');
            if (selectedOption.val()) {
                var tipoEstancia = selectedOption.data('estancia');
                var duracion = parseInt(selectedOption.data('duracion')) || 1;
                var fechaEntrada = new Date($('#fechaentrada').val());

                if (!isNaN(fechaEntrada.getTime())) {
                    var fechaSalida = new Date(fechaEntrada);

                    if (tipoEstancia === 'horas') {
                        fechaSalida.setHours(fechaSalida.getHours() + duracion);
                    } else { // días
                        fechaSalida.setDate(fechaSalida.getDate() + duracion);
                    }

                    // Formatear la fecha para el input datetime-local
                    var year = fechaSalida.getFullYear();
                    var month = String(fechaSalida.getMonth() + 1).padStart(2, '0');
                    var day = String(fechaSalida.getDate()).padStart(2, '0');
                    var hours = String(fechaSalida.getHours()).padStart(2, '0');
                    var minutes = String(fechaSalida.getMinutes()).padStart(2, '0');

                    var fechaFormateada = `${year}-${month}-${day}T${hours}:${minutes}`;
                    $('#fechasalida_prevista').val(fechaFormateada);
                }
            }
        }

        // Calcular cambio para pagos en efectivo - versión corregida
        function calcularCambio() {
            var montoPagado = parseFloat($('#montopagado').val()) || 0;
            var montoRecibido = parseFloat($('#monto_recibido').val()) || 0;

            // El cambio es la diferencia entre lo recibido y lo pagado (no puede ser negativo)
            var cambio = Math.max(0, montoRecibido - montoPagado);

            // Actualizar el campo de cambio
            $('#cambio').val(cambio.toFixed(2));

            console.log('Cálculo de cambio:', {
                montoPagado: montoPagado,
                montoRecibido: montoRecibido,
                cambio: cambio
            });
        }

        // Actualizar resumen financiero
        function actualizarResumenFinanciero() {
            var montoTotal = parseFloat($('#montototal').val()) || 0;
            var montoPagado = parseFloat($('#montopagado').val()) || 0;
            var saldo = Math.max(0, montoTotal - montoPagado);

            $('#resumen-total').text('Bs ' + montoTotal.toFixed(2));
            $('#resumen-pagado').text('Bs ' + montoPagado.toFixed(2));
            $('#resumen-saldo').text('Bs ' + saldo.toFixed(2));

            // Cambiar clase según si hay saldo pendiente
            if (saldo > 0) {
                $('#resumen-saldo').removeClass('text-success').addClass('text-danger');
            } else {
                $('#resumen-saldo').removeClass('text-danger').addClass('text-success');
            }

            // Actualizar barra de progreso
            var porcentaje = montoTotal > 0 ? (montoPagado / montoTotal) * 100 : 0;
            porcentaje = Math.min(100, porcentaje);

            $('.progress-bar').css('width', porcentaje + '%');
            $('.progress-bar').text(porcentaje.toFixed(1) + '% pagado');

            if (porcentaje >= 100) {
                $('.progress-bar').removeClass('bg-warning').addClass('bg-success');
            } else {
                $('.progress-bar').removeClass('bg-success').addClass('bg-warning');
            }
        }

        // Actualizar las fechas en el resumen
        function actualizarFechasResumen() {
            var fechaEntrada = $('#fechaentrada').val();
            var fechaSalida = $('#fechasalida_prevista').val();

            if (fechaEntrada) {
                var fecha = new Date(fechaEntrada);
                var dia = fecha.getDate().toString().padStart(2, '0');
                var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
                var anio = fecha.getFullYear();
                var hora = fecha.getHours().toString().padStart(2, '0');
                var minutos = fecha.getMinutes().toString().padStart(2, '0');

                $('#resumen-entrada').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
            }

            if (fechaSalida) {
                var fecha = new Date(fechaSalida);
                var dia = fecha.getDate().toString().padStart(2, '0');
                var mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
                var anio = fecha.getFullYear();
                var hora = fecha.getHours().toString().padStart(2, '0');
                var minutos = fecha.getMinutes().toString().padStart(2, '0');

                $('#resumen-salida').text(`${dia}/${mes}/${anio} ${hora}:${minutos}`);
            }
        }

        // Actualizar resumen de estado
        function actualizarResumenEstado() {
            var estado = $('#estado').val();
            var claseEstado = '';
            var textoEstado = '';

            switch (estado) {
                case 'reservado':
                    claseEstado = 'badge-info';
                    textoEstado = 'Reservado';
                    break;
                case 'en_curso':
                    claseEstado = 'badge-warning';
                    textoEstado = 'En curso (Check-in)';
                    break;
            }

            $('#resumen-estado').html(`<span class="badge ${claseEstado} px-3 py-2">${textoEstado}</span>`);
        }

        // Actualizar resumen de observaciones
        function actualizarResumenObservaciones() {
            var observaciones = $('#observaciones').val();

            if (observaciones && observaciones.trim() !== '') {
                $('#resumen-observaciones').text(observaciones);
                $('#resumen-observaciones-container').show();
            } else {
                $('#resumen-observaciones-container').hide();
            }
        }

        // Manejadores de eventos

        // Cliente
        $('#idcliente').on('change', function() {
            mostrarInfoCliente();
        });

        // Habitación
        $('#idhabitacion').on('change', function() {
            mostrarInfoHabitacion();
        });

        // Tarifa
        $('#idtarifa').on('change', function() {
            mostrarInfoTarifa();
            actualizarResumenFinanciero();
        });

        // Fechas
        $('#fechaentrada, #fechasalida_prevista').on('change', function() {
            actualizarFechasResumen();
        });

        // Estado
        $('#estado').on('change', function() {
            actualizarResumenEstado();
        });

        // Observaciones
        $('#observaciones').on('input', function() {
            actualizarResumenObservaciones();
        });

        // Montos
        $('#montototal, #montopagado').on('input', function() {
            actualizarResumenFinanciero();

            // Si cambia el monto pagado, actualizar el monto recibido en efectivo
            if ($(this).attr('id') === 'montopagado' && $('#metodopago').val() === 'Efectivo') {
                var montoPagado = parseFloat($(this).val()) || 0;
                $('#monto_recibido').val(montoPagado.toFixed(2));
                calcularCambio();
            }
        });

        // Actualizar el cambio cuando cambia el monto pagado
        $('#montopagado').on('input', function() {
            // Actualizar el resumen financiero
            actualizarResumenFinanciero();

            // Si es efectivo, recalcular el cambio pero NO modificar el monto recibido
            // para mantener independientes ambos valores
            if ($('#metodopago').val() === 'Efectivo') {
                calcularCambio();
            }
        });


        // Método de pago
        $('#metodopago').on('change', function() {
            if ($(this).val() === 'Efectivo') {
                $('#seccion-cambio').slideDown();

                // Inicializar el monto recibido con el monto que ha pagado el cliente
                // sin modificar el monto pagado
                var montoPagado = parseFloat($('#montopagado').val()) || 0;
                $('#monto_recibido').val(montoPagado.toFixed(2));

                // Calcular el cambio inicial
                calcularCambio();
            } else {
                $('#seccion-cambio').slideUp();
            }
        });

        // Actualizar el cambio cuando cambia el monto recibido
        $('#monto_recibido').on('input', function() {
            if ($('#metodopago').val() === 'Efectivo') {
                calcularCambio();
            }
        });

        // Calcular cambio para pagos en efectivo - versión corregida
        function calcularCambio() {
            var montoPagado = parseFloat($('#montopagado').val()) || 0;
            var montoRecibido = parseFloat($('#monto_recibido').val()) || 0;

            // El cambio es la diferencia entre lo recibido y lo pagado (no puede ser negativo)
            var cambio = Math.max(0, montoRecibido - montoPagado);

            // Actualizar el campo de cambio
            $('#cambio').val(cambio.toFixed(2));

            console.log('Cálculo de cambio:', {
                montoPagado: montoPagado,
                montoRecibido: montoRecibido,
                cambio: cambio
            });
        }

        // Método de pago
        $('#metodopago').on('change', function() {
            if ($(this).val() === 'Efectivo') {
                $('#seccion-cambio').slideDown();

                // Inicializar el monto recibido con el monto que ha pagado el cliente
                // sin modificar el monto pagado
                var montoPagado = parseFloat($('#montopagado').val()) || 0;
                $('#monto_recibido').val(montoPagado.toFixed(2));

                // Calcular el cambio inicial
                calcularCambio();
            } else {
                $('#seccion-cambio').slideUp();
            }
        });

        // Actualizar el cambio cuando cambia el monto pagado
        $('#montopagado').on('input', function() {
            // Actualizar el resumen financiero
            actualizarResumenFinanciero();

            // Si es efectivo, recalcular el cambio pero NO modificar el monto recibido
            // para mantener independientes ambos valores
            if ($('#metodopago').val() === 'Efectivo') {
                calcularCambio();
            }
        });

        // Actualizar el cambio cuando cambia el monto recibido
        $('#monto_recibido').on('input', function() {
            if ($('#metodopago').val() === 'Efectivo') {
                calcularCambio();
            }
        });

        // Validación del formulario antes de enviar
        $('#formEditarRecepcion').on('submit', function(e) {
            e.preventDefault();

            // Validar que todos los campos requeridos estén completos
            if (!this.checkValidity()) {
                e.stopPropagation();
                $(this).addClass('was-validated');

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Por favor complete todos los campos requeridos correctamente.'
                });

                return false;
            }

            // Validaciones adicionales
            var montoTotal = parseFloat($('#montototal').val());
            var montoPagado = parseFloat($('#montopagado').val());

            if (isNaN(montoTotal) || montoTotal <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El monto total debe ser mayor que cero.'
                });
                return false;
            }

            if (isNaN(montoPagado) || montoPagado < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El monto pagado no puede ser negativo.'
                });
                return false;
            }

            // Si se seleccionó pago en efectivo, validar el monto recibido
            if ($('#metodopago').val() === 'Efectivo') {
                var montoRecibido = parseFloat($('#monto_recibido').val());
                if (isNaN(montoRecibido) || montoRecibido < montoPagado) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El monto recibido debe ser igual o mayor al monto pagado.'
                    });
                    return false;
                }

                // Asegurar que el cambio sea correcto antes de enviar
                var cambio = montoRecibido - montoPagado;
                $('#cambio').val(cambio.toFixed(2));

                console.log('Formulario listo para enviar:', {
                    montoTotal: montoTotal,
                    montoPagado: montoPagado,
                    montoRecibido: montoRecibido,
                    cambio: cambio
                });
            }

            // Confirmar antes de enviar
            Swal.fire({
                title: '¿Confirmar actualización?',
                html: `
            <div class="text-left">
                <p><strong>Cliente:</strong> ${$('#resumen-cliente').text()}</p>
                <p><strong>Habitación:</strong> ${$('#resumen-habitacion').text()}</p>
                <p><strong>Monto total:</strong> Bs ${montoTotal.toFixed(2)}</p>
                <p><strong>Monto pagado:</strong> Bs ${montoPagado.toFixed(2)}</p>
                ${$('#metodopago').val() === 'Efectivo' ? 
                  `<p><strong>Dinero recibido:</strong> Bs ${parseFloat($('#monto_recibido').val()).toFixed(2)}</p>
                   <p><strong>Cambio:</strong> Bs ${parseFloat($('#cambio').val()).toFixed(2)}</p>` : ''}
                <p><strong>Estado:</strong> ${$('#estado option:selected').text()}</p>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviar el formulario
                    this.submit();
                }
            });
        });


        // Inicializar todo al cargar la página
        mostrarInfoCliente();
        mostrarInfoHabitacion();
        mostrarInfoTarifa();
        actualizarResumenFinanciero();
        actualizarResumenEstado();
        actualizarResumenObservaciones();

        // Efectos visuales
        $('.card').addClass('card-animation');
    });
</script>

<style>
    /* Animación de entrada para las tarjetas */
    @keyframes cardFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card-animation {
        animation: cardFadeIn 0.4s ease-out forwards;
    }

    /* Estilo para la tarjeta activa cuando se hace hover */
    .card:hover {
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.3s ease;
    }

    /* Estilos para los info-boxes */
    #cliente-info,
    #habitacion-info,
    #tarifa-info {
        padding: 8px;
        border-radius: 4px;
        background-color: #f8f9fa;
        border-left: 3px solid #007bff;
    }

    /* Mejorar visibilidad de los campos requeridos */
    .form-group label span.text-danger {
        font-weight: bold;
        margin-left: 4px;
    }

    /* Estilos para el resumen en panel lateral */
    .list-group-item {
        border-left: none;
        border-right: none;
    }

    .list-group-item:first-child {
        border-top: none;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }
</style>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
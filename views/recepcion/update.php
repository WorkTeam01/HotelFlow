<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Definir recursos específicos para esta vista
$module_styles = ['recepciones/update-recepcion'];
$module_scripts = ['recepciones/update-recepcion'];

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
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
$skip_chartjs = true;
include_once '../layouts/header.php';

// Estado canónico (etiqueta/color) desde la fuente única de verdad
$estado_ui = RecepcionController::estadoRecepcion($recepcion['estado']);
$clase_estado = $estado_ui['clase'];
$etiqueta_estado = $estado_ui['label'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
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
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>"><i class="fas fa-info-circle"></i> Detalle</a></li>
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
        <div class="row">
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
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Datos Principales">
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

                                <!-- Habitación (solo lectura: el cambio de habitación se hace desde el detalle) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-bed mr-1 text-primary"></i>
                                            Habitación
                                        </label>
                                        <input type="text" class="form-control" readonly
                                            value="<?= htmlspecialchars($recepcion['numero_habitacion'] . ' - ' . ($recepcion['tipo_tarifa'] ?? 'Estándar')); ?>">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Para cambiar de habitación usa
                                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">el detalle de la recepción</a>.
                                        </small>
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

                                <!-- Estado (solo lectura: check-in / check-out se hacen desde el detalle) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-toggle-on mr-1 text-primary"></i>
                                            Estado
                                        </label>
                                        <div>
                                            <span class="badge badge-<?= $clase_estado; ?> px-3 py-2"><?= $etiqueta_estado; ?></span>
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            El check-in y el check-out se realizan desde
                                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">el detalle de la recepción</a>.
                                        </small>
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

                    <!-- El dinero de esta reserva se gestiona en el folio, no aquí -->
                    <div class="callout callout-info">
                        <h5><i class="fas fa-file-invoice-dollar mr-2"></i>El dinero se gestiona en el folio</h5>
                        <p class="mb-2">
                            Los cargos, pagos y el saldo de esta reserva son la fuente de verdad del folio del
                            huésped. Desde este formulario solo se editan los datos de la estancia.
                        </p>
                        <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>#folio-recepcion" class="btn btn-sm btn-info">
                            <i class="fas fa-external-link-alt mr-1"></i> Ir al folio
                        </a>
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
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Resumen de Recepción">
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
                            </ul>

                            <div id="resumen-observaciones-container" class="mt-3" <?= empty($recepcion['observaciones']) ? 'style="display: none;"' : ''; ?>>
                                <div class="callout callout-info p-2">
                                    <h6 class="text-primary"><i class="fas fa-comment mr-2"></i>Observaciones:</h6>
                                    <p id="resumen-observaciones" class="mb-0 text-muted small"><?= htmlspecialchars($recepcion['observaciones'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-save mr-1"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Información de ayuda -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-question-circle mr-2"></i>Información de Ayuda
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Información de Ayuda">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle text-info mr-2"></i>Datos de estancia</h6>
                            <p class="text-muted small">
                                Aquí se editan cliente, tarifa, fechas y observaciones. Al cambiar la tarifa se
                                recalcula la fecha de salida prevista.
                            </p>

                            <h6 class="mt-3"><i class="fas fa-info-circle text-info mr-2"></i>Cambio de habitación</h6>
                            <p class="text-muted small">
                                El cambio de habitación se hace desde el detalle de la recepción (flujo auditado que
                                libera la habitación anterior y carga la diferencia de tarifa al folio si corresponde).
                            </p>

                            <h6 class="mt-3"><i class="fas fa-info-circle text-info mr-2"></i>Dinero y check-out</h6>
                            <p class="text-muted small">
                                Los cargos y pagos se registran en el folio del huésped. El check-out valida que el
                                saldo esté cubierto antes de finalizar la estancia.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
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
                <h1>Editar recepción</h1>
                <p class="text-muted mb-0">
                    Recepción #<?= (int) $recepcion['idrecepcion']; ?> &middot;
                    Hab. <?= htmlspecialchars($recepcion['numero_habitacion']); ?> &middot;
                    <?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?>
                    <span class="badge badge-<?= $clase_estado; ?> ml-1"><?= htmlspecialchars($etiqueta_estado); ?></span>
                </p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion"><i class="fas fa-bed"></i> Recepción</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">Folio #<?= (int) $id; ?></a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form id="formEditarRecepcion" action="<?= $URL; ?>controllers/recepcion/actualizar_recepcion.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
            <!-- ID de la recepción -->
            <input type="hidden" name="idrecepcion" value="<?= $recepcion['idrecepcion']; ?>">

            <div class="row">
                <!-- Panel izquierdo -->
                <div class="col-lg-8">
                    <!-- Datos principales -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>Datos de la estancia
                            </h3>
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
                                            value="<?= htmlspecialchars($recepcion['numero_habitacion'] . ' — ' . ($recepcion['tipo_nombre'] ?? 'Estándar')); ?>">
                                        <small class="form-text text-muted">
                                            Para cambiar de habitación usa
                                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">el folio</a>.
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
                                    </div>
                                </div>

                                <!-- Estado (solo lectura: check-in / check-out se hacen desde el folio) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Estado actual</label>
                                        <div>
                                            <span class="badge badge-<?= $clase_estado; ?> px-3 py-2"><?= htmlspecialchars($etiqueta_estado); ?></span>
                                        </div>
                                        <small class="form-text text-muted">
                                            El check-in y el check-out se hacen desde
                                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $id; ?>">el folio</a>.
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
                                            Fecha y hora de entrada <span class="text-danger">*</span>
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
                                            Fecha de salida prevista <span class="text-danger">*</span>
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

                </div>

                <!-- Panel derecho: acciones + nota del folio -->
                <div class="col-lg-4">
                    <div class="card card-outline card-warning rec-update-acciones">
                        <div class="card-body">
                            <button type="submit" class="btn btn-warning btn-block btn-lg">
                                <i class="fas fa-save mr-1"></i> Guardar cambios
                            </button>
                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= (int) $recepcion['idrecepcion']; ?>" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                        </div>
                        <div class="card-footer bg-white">
                            <p class="text-muted small mb-2">
                                Aquí solo se editan los datos de la estancia. Cargos, pagos y cambio de
                                habitación se gestionan en el folio.
                            </p>
                            <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= (int) $id; ?>#folio-recepcion" class="btn btn-sm btn-outline-info btn-block">
                                <i class="fas fa-external-link-alt mr-1"></i> Ir al folio
                            </a>
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
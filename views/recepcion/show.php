<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Definir recursos específicos para esta vista
$module_styles = ['recepciones/show-recepcion'];
$module_scripts = ['recepciones/show-recepcion'];

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

// Obtener la recepción por su ID
$recepcion = $controller->mostrar($id);

if (!$recepcion) {
    $_SESSION['mensaje'] = 'Recepción no encontrada.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

// Determinar clase CSS según el estado
$clase_estado = '';
$icono_estado = '';
$badge_estado = '';
switch ($recepcion['estado']) {
    case 'reservado':
        $clase_estado = 'info';
        $icono_estado = 'calendar-check';
        $badge_estado = 'badge-info';
        break;
    case 'en_curso':
        $clase_estado = 'warning';
        $icono_estado = 'bed';
        $badge_estado = 'badge-warning';
        break;
    case 'finalizado':
        $clase_estado = 'success';
        $icono_estado = 'check-circle';
        $badge_estado = 'badge-success';
        break;
    case 'cancelado':
        $clase_estado = 'danger';
        $icono_estado = 'times-circle';
        $badge_estado = 'badge-danger';
        break;
}

// Calcular información financiera coherente
$montoTotal = (float)$recepcion['montototal'];
$montoPagado = (float)$recepcion['montopagado'];
$cambio = isset($recepcion['cambio']) ? (float)$recepcion['cambio'] : 0;
$saldoPendiente = $montoTotal - $montoPagado;
$metodoPago = $recepcion['metodopago'] ?? 'No especificado';

// Calcular tiempo de estancia
$fechaEntrada = new DateTime($recepcion['fechaentrada']);
$fechaSalidaPrevista = new DateTime($recepcion['fechasalida_prevista']);
$estanciaPrevista = $fechaEntrada->diff($fechaSalidaPrevista);

$tiempoEstancia = '';
if ($estanciaPrevista->days > 0) {
    $tiempoEstancia .= $estanciaPrevista->days . ' día(s) ';
}
if ($estanciaPrevista->h > 0) {
    $tiempoEstancia .= $estanciaPrevista->h . ' hora(s) ';
}
if (empty($tiempoEstancia) && $estanciaPrevista->i > 0) {
    $tiempoEstancia .= $estanciaPrevista->i . ' minuto(s)';
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <i class="fas fa-concierge-bell text-<?= $clase_estado; ?> mr-2"></i>
                    Detalle de Recepción
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion"><i class="fas fa-bed"></i> Recepción</a></li>
                    <li class="breadcrumb-item active">Detalle de Recepción</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Header de estado y botones rápidos -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card bg-<?= $clase_estado; ?> text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="m-0">
                                    <i class="fas fa-<?= $icono_estado; ?> mr-2"></i>
                                    Recepción #<?= $recepcion['idrecepcion']; ?> -
                                    <span class="badge badge-light"><?= ucfirst(str_replace('_', ' ', $recepcion['estado'])); ?></span>
                                </h3>
                                <p class="mb-0 mt-2 lead">Habitación <?= htmlspecialchars($recepcion['numero_habitacion']); ?> - <?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?></p>
                            </div>
                            <div class="text-right">
                                <div class="btn-group">
                                    <a href="<?= $URL; ?>views/recepcion/lista-recepciones.php" class="btn btn-light">
                                        <i class="fas fa-arrow-left mr-1"></i> Volver
                                    </a>

                                    <?php if ($recepcion['estado'] !== 'finalizado' && $recepcion['estado'] !== 'cancelado'): ?>
                                        <a href="<?= $URL; ?>views/recepcion/update.php?id=<?= $recepcion['idrecepcion']; ?>"
                                            class="btn btn-light">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?= $URL; ?>views/recepcion/recibo.php?id=<?= $recepcion['idrecepcion']; ?>"
                                        class="btn btn-light" target="_blank">
                                        <i class="fas fa-print mr-1"></i> Imprimir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Información principal de la recepción -->
                <div class="card card-outline card-<?= $clase_estado; ?>">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Información de Estancia
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-<?= $clase_estado; ?>"><i class="fas fa-calendar-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Fecha de Entrada</span>
                                        <span class="info-box-number"><?= date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-<?= $clase_estado; ?>"><i class="fas fa-calendar-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Fecha de Salida Prevista</span>
                                        <span class="info-box-number"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-bed"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Habitación</span>
                                        <span class="info-box-number"><?= htmlspecialchars($recepcion['numero_habitacion']); ?> - <?= htmlspecialchars($recepcion['tipo_habitacion'] ?? 'Estándar'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Duración de Estancia</span>
                                        <span class="info-box-number"><?= $tiempoEstancia; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($recepcion['fechasalida'])): ?>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <span class="info-box-icon bg-success"><i class="fas fa-sign-out-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Fecha de Salida Real</span>
                                            <span class="info-box-number"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($recepcion['observaciones'])): ?>
                            <div class="callout callout-<?= $clase_estado; ?> mt-4">
                                <h5><i class="fas fa-comment-alt mr-2"></i>Observaciones:</h5>
                                <p><?= nl2br(htmlspecialchars($recepcion['observaciones'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información financiera MEJORADA -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Información Financiera
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body pb-0">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>Bs <?= number_format($montoTotal, 2); ?></h3>
                                        <p>Costo Total</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>Bs <?= number_format($montoPagado, 2); ?></h3>
                                        <p>Monto Pagado</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="small-box <?= $saldoPendiente > 0 ? 'bg-danger' : 'bg-success'; ?>">
                                    <div class="inner">
                                        <h3>Bs <?= number_format(abs($saldoPendiente), 2); ?></h3>
                                        <p><?= $saldoPendiente > 0 ? 'Saldo Pendiente' : 'Pagado Completo'; ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-<?= $saldoPendiente > 0 ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-money-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Método de Pago</span>
                                        <span class="info-box-number">
                                            <span class="badge badge-primary px-3 py-2"><?= htmlspecialchars($metodoPago); ?></span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-file-invoice-dollar"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tarifa Aplicada</span>
                                        <span class="info-box-number"><?= htmlspecialchars($recepcion['tipo_tarifa']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($metodoPago === 'Efectivo' && $cambio > 0): ?>
                            <div class="alert alert-warning mt-3">
                                <h6><i class="fas fa-hand-holding-usd mr-2"></i>Información del pago en efectivo:</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Dinero recibido:</strong> Bs <?= number_format($montoPagado + $cambio, 2); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Cambio entregado:</strong> Bs <?= number_format($cambio, 2); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Neto cobrado:</strong> Bs <?= number_format($montoPagado, 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Progreso de pago visual -->
                        <div class="mt-4 mb-3">
                            <p class="mb-1"><strong>Progreso del pago:</strong></p>
                            <?php
                            $porcentajePagado = ($montoTotal > 0) ? ($montoPagado / $montoTotal) * 100 : 0;
                            $porcentajePagado = min(100, $porcentajePagado); // No exceder 100%
                            ?>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar <?= $porcentajePagado >= 100 ? 'bg-success' : 'bg-warning'; ?>"
                                    role="progressbar"
                                    style="width: <?= $porcentajePagado; ?>%"
                                    aria-valuenow="<?= $porcentajePagado; ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?= number_format($porcentajePagado, 1); ?>% pagado
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Bs <?= number_format($montoPagado, 2); ?> de Bs <?= number_format($montoTotal, 2); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Información del cliente -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-2"></i>
                            Información del Cliente
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <img class="profile-user-img img-fluid img-circle"
                                src="<?= $URL; ?>public/img/user_default.jpg"
                                alt="Imagen del cliente">
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($recepcion['tipodoc_cliente'] . ': ' . $recepcion['numdoc_cliente']); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <?php if (!empty($recepcion['telefono_cliente'])): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-phone-alt mr-2 text-primary"></i>
                                    <b>Teléfono</b>
                                    <a href="tel:<?= htmlspecialchars($recepcion['telefono_cliente']); ?>" class="float-right">
                                        <?= htmlspecialchars($recepcion['telefono_cliente']); ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($recepcion['email_cliente'])): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-envelope mr-2 text-primary"></i>
                                    <b>Email</b>
                                    <a href="mailto:<?= htmlspecialchars($recepcion['email_cliente']); ?>" class="float-right">
                                        <?= htmlspecialchars($recepcion['email_cliente']); ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($recepcion['direccion_cliente'])): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                    <b>Dirección</b>
                                    <span class="float-right"><?= htmlspecialchars($recepcion['direccion_cliente']); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <a href="<?= $URL; ?>views/clientes/show.php?id=<?= $recepcion['idcliente']; ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-user mr-1"></i> Ver perfil completo
                        </a>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs mr-2"></i>
                            Acciones
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav flex-column">
                            <?php if ($recepcion['estado'] === 'reservado'): ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= $recepcion['idrecepcion']; ?>&nuevo_estado=en_curso&csrf_token=<?= generateCSRFToken(); ?>"
                                        class="nav-link text-primary"
                                        onclick="return confirm('¿Está seguro que desea realizar el check-in para esta reserva?');">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Realizar Check-in
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($recepcion['estado'] === 'en_curso'): ?>
                                <?php if ($saldoPendiente > 0): ?>
                                    <li class="nav-item">
                                        <a href="<?= $URL; ?>views/recepcion/checkout.php?id=<?= $recepcion['idrecepcion']; ?>"
                                            class="nav-link text-success">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Realizar Check-out
                                            <span class="badge badge-danger ml-1">Pago pendiente: Bs <?= number_format($saldoPendiente, 2); ?></span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-item">
                                        <a href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= $recepcion['idrecepcion']; ?>&nuevo_estado=finalizado&csrf_token=<?= generateCSRFToken(); ?>"
                                            class="nav-link text-success"
                                            onclick="return confirm('¿Está seguro que desea realizar el check-out para esta recepción?');">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Realizar Check-out
                                            <span class="badge badge-success ml-1">Pago completo</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($recepcion['estado'] !== 'finalizado' && $recepcion['estado'] !== 'cancelado'): ?>
                                <li class="nav-item">
                                    <a href="<?= $URL; ?>views/recepcion/update.php?id=<?= $recepcion['idrecepcion']; ?>"
                                        class="nav-link text-warning">
                                        <i class="fas fa-edit mr-2"></i> Editar recepción
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= $recepcion['idrecepcion']; ?>&nuevo_estado=cancelado&csrf_token=<?= generateCSRFToken(); ?>"
                                        class="nav-link text-danger"
                                        onclick="return confirm('¿Está seguro que desea cancelar esta recepción?');">
                                        <i class="fas fa-times-circle mr-2"></i> Cancelar recepción
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a href="<?= $URL; ?>views/recepcion/recibo.php?id=<?= $recepcion['idrecepcion']; ?>"
                                    class="nav-link text-info" target="_blank">
                                    <i class="fas fa-print mr-2"></i> Imprimir comprobante
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Información de registro -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i>
                            Información de Registro
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-user-edit mr-2 text-muted"></i>
                                <b>Registrado por:</b>
                                <span class="float-right"><?= htmlspecialchars($recepcion['nombre_usuario']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-calendar-plus mr-2 text-muted"></i>
                                <b>Fecha de registro:</b>
                                <span class="float-right"><?= date('d/m/Y H:i', strtotime($recepcion['fechacreacion'])); ?></span>
                            </li>
                            <?php if (!empty($recepcion['fechaactualizacion'])): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-edit mr-2 text-muted"></i>
                                    <b>Última actualización:</b>
                                    <span class="float-right"><?= date('d/m/Y H:i', strtotime($recepcion['fechaactualizacion'])); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        // Inicializar tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Animación de las tarjetas al cargar la página
        $('.card').addClass('card-animation');
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Recursos de módulo: tokens/tile compartidos + ajustes propios de esta vista
$module_styles = ['recepciones/recepcion', 'recepciones/show-recepcion'];
$module_scripts = ['recepciones/show-recepcion'];

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

if (!isset($_GET['id']) || $_GET['id'] === '') {
    $_SESSION['mensaje'] = 'ID de recepción no válido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

$id = (int) $_GET['id'];

$controller = new RecepcionController();
$recepcion = $controller->mostrar($id);

if (!$recepcion) {
    $_SESSION['mensaje'] = 'Recepción no encontrada.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/recepcion/index.php');
    exit;
}

// Folio de huésped: única fuente de verdad del dinero (ver Pago::calcularSaldo).
$folio = $controller->obtenerFolio($id);
$folio_lineas = $folio['lineas'];
$folio_saldo = $folio['saldo'];
$saldo = (float) $folio_saldo['saldo'];

// Historial de cambios de habitación
$movimientos = $controller->obtenerMovimientos($id);
$habitaciones_disponibles_cambio = $recepcion['estado'] === 'en_curso'
    ? $controller->modelo->getHabitacionesDisponibles()
    : [];

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Estado canónico (etiqueta/color/icono) — fuente única de verdad
$estado_ui = RecepcionController::estadoRecepcion(RecepcionController::estadoDerivado($recepcion));
$clase_estado = $estado_ui['clase'];

// Banderas de acción (presentación, no lógica de negocio)
$esActiva = !in_array($recepcion['estado'], ['finalizado', 'cancelado'], true);
$puedeCheckin = $recepcion['estado'] === 'reservado';
$puedeCheckout = $recepcion['estado'] === 'en_curso';
$puedeCambiarHabitacion = $recepcion['estado'] === 'en_curso';

// Duración de estancia prevista
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
if ($tiempoEstancia === '' && $estanciaPrevista->i > 0) {
    $tiempoEstancia = $estanciaPrevista->i . ' minuto(s)';
}

$nombreCompleto = trim($recepcion['nombre_cliente'] . ' ' . $recepcion['apellido_cliente']);
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1><i class="fas fa-concierge-bell text-<?= $clase_estado; ?> mr-2"></i> Folio #<?= (int) $recepcion['idrecepcion']; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/recepcion/index.php"><i class="fas fa-bed"></i> Recepción</a></li>
                    <li class="breadcrumb-item active">Folio #<?= (int) $recepcion['idrecepcion']; ?></li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Cabecera compacta + barra de acciones (en card, no flotando) -->
        <div class="card rec-encabezado">
            <div class="card-body">
                <div class="rec-header mb-2">
                    <span class="h5 mb-0">#<?= (int) $recepcion['idrecepcion']; ?></span>
                    <span class="text-muted">·</span>
                    <strong><?= htmlspecialchars($nombreCompleto); ?></strong>
                    <span class="text-muted">·</span>
                    <span><i class="fas fa-bed mr-1 text-muted"></i>Hab. <?= htmlspecialchars($recepcion['numero_habitacion']); ?></span>
                    <span class="badge <?= $estado_ui['badge']; ?>"><i class="fas fa-<?= $estado_ui['icono']; ?> mr-1"></i><?= htmlspecialchars($estado_ui['label']); ?></span>
                    <span class="rec-header__saldo text-<?= $saldo > 0.01 ? 'danger' : 'success'; ?>">
                        Saldo: Bs <?= number_format($saldo, 2); ?>
                    </span>
                </div>

                <div class="rec-actionbar">
                    <a href="<?= $URL; ?>views/recepcion/index.php#hoy" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>

                    <?php if ($esActiva): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bolt mr-1"></i> Acciones
                            </button>
                            <div class="dropdown-menu">
                                <?php if ($puedeCheckin): ?>
                                    <a class="dropdown-item text-primary font-weight-bold"
                                        href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= (int) $recepcion['idrecepcion']; ?>&nuevo_estado=en_curso&csrf_token=<?= generateCSRFToken(); ?>"
                                        onclick="return confirm('¿Confirmar el check-in de esta reserva?');">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Check-in
                                    </a>
                                <?php endif; ?>

                                <?php if ($puedeCheckout): ?>
                                    <a class="dropdown-item text-success font-weight-bold" href="#" id="btn-checkout"
                                        data-id="<?= (int) $recepcion['idrecepcion']; ?>"
                                        data-saldo="<?= number_format($saldo, 2, '.', ''); ?>"
                                        data-cliente="<?= htmlspecialchars($nombreCompleto); ?>"
                                        data-habitacion="<?= htmlspecialchars($recepcion['numero_habitacion']); ?>"
                                        data-endpoint="<?= $URL; ?>controllers/recepcion/checkout_ajax.php"
                                        data-csrf="<?= generateCSRFToken(); ?>">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Check-out<?php if ($saldo > 0.01): ?> (saldo Bs <?= number_format($saldo, 2); ?>)<?php endif; ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($puedeCambiarHabitacion): ?>
                                    <a class="dropdown-item" href="#cambio-habitacion">
                                        <i class="fas fa-exchange-alt mr-2"></i> Cambiar habitación
                                    </a>
                                <?php endif; ?>

                                <a class="dropdown-item" href="#folio-recepcion">
                                    <i class="fas fa-plus-circle mr-2"></i> Agregar cargo
                                </a>
                                <a class="dropdown-item" href="#folio-recepcion">
                                    <i class="fas fa-hand-holding-usd mr-2"></i> Registrar pago
                                </a>
                                <a class="dropdown-item" href="<?= $URL; ?>views/recepcion/update.php?id=<?= (int) $recepcion['idrecepcion']; ?>">
                                    <i class="fas fa-edit mr-2"></i> Editar estancia
                                </a>

                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger"
                                    href="<?= $URL; ?>controllers/recepcion/cambiar_estado.php?id=<?= (int) $recepcion['idrecepcion']; ?>&nuevo_estado=cancelado&csrf_token=<?= generateCSRFToken(); ?>"
                                    onclick="return confirm('¿Confirmar la cancelación de esta recepción?');">
                                    <i class="fas fa-times-circle mr-2"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-print mr-1"></i> Imprimir
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="<?= $URL; ?>views/recepcion/recibo.php?id=<?= (int) $recepcion['idrecepcion']; ?>" target="_blank" rel="noopener">
                                <i class="fas fa-receipt mr-2"></i> Recibo
                            </a>
                            <a class="dropdown-item" href="<?= $URL; ?>views/recepcion/tarjeta-registro.php?id=<?= (int) $recepcion['idrecepcion']; ?>" target="_blank" rel="noopener">
                                <i class="fas fa-id-card mr-2"></i> Tarjeta de registro
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card card-outline card-<?= $clase_estado; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Estancia</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Estancia">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Entrada</dt>
                            <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])); ?></dd>

                            <dt class="col-sm-4">Salida prevista</dt>
                            <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?></dd>

                            <?php if (!empty($recepcion['fechasalida'])): ?>
                                <dt class="col-sm-4">Salida real</dt>
                                <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($recepcion['fechasalida'])); ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-4">Duración prevista</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($tiempoEstancia ?: '—'); ?></dd>

                            <dt class="col-sm-4">Habitación</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($recepcion['numero_habitacion']); ?> — <?= htmlspecialchars($recepcion['tipo_habitacion'] ?? 'Estándar'); ?></dd>

                            <dt class="col-sm-4">Tarifa</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($recepcion['tipo_tarifa'] ?? '—'); ?></dd>
                        </dl>

                        <?php if (!empty($recepcion['observaciones'])): ?>
                            <div class="callout callout-<?= $clase_estado; ?> mt-3 mb-0">
                                <h6 class="mb-1"><i class="fas fa-comment-alt mr-2"></i>Observaciones</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($recepcion['observaciones'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php include __DIR__ . '/partials/folio.php'; ?>

                <div id="cambio-habitacion">
                    <?php include __DIR__ . '/partials/cambio-habitacion.php'; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i> Huésped</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Huésped">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1"><?= htmlspecialchars($nombreCompleto); ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars(($recepcion['tipodoc_cliente'] ?? 'Doc') . ': ' . ($recepcion['numdoc_cliente'] ?? '—')); ?></p>

                        <ul class="list-group list-group-flush">
                            <?php if (!empty($recepcion['telefono_cliente'])): ?>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-phone-alt mr-2 text-primary"></i><b>Teléfono</b>
                                    <a href="tel:<?= htmlspecialchars($recepcion['telefono_cliente']); ?>" class="float-right"><?= htmlspecialchars($recepcion['telefono_cliente']); ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($recepcion['email_cliente'])): ?>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-envelope mr-2 text-primary"></i><b>Email</b>
                                    <a href="mailto:<?= htmlspecialchars($recepcion['email_cliente']); ?>" class="float-right"><?= htmlspecialchars($recepcion['email_cliente']); ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($recepcion['direccion_cliente'])): ?>
                                <li class="list-group-item px-0">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i><b>Dirección</b>
                                    <span class="float-right"><?= htmlspecialchars($recepcion['direccion_cliente']); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <a href="<?= $URL; ?>views/clientes/show.php?id=<?= (int) $recepcion['idcliente']; ?>" class="btn btn-outline-primary btn-block mt-3">
                            <i class="fas fa-user mr-1"></i> Ver perfil completo
                        </a>
                    </div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i> Registro</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar Registro">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-user-edit mr-2 text-muted"></i><b>Registrado por:</b>
                                <span class="float-right"><?= htmlspecialchars($recepcion['nombre_usuario'] ?? '—'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-calendar-plus mr-2 text-muted"></i><b>Fecha de registro:</b>
                                <span class="float-right"><?= date('d/m/Y H:i', strtotime($recepcion['fechacreacion'])); ?></span>
                            </li>
                            <?php if (!empty($recepcion['fechaactualizacion'])): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-edit mr-2 text-muted"></i><b>Última actualización:</b>
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

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
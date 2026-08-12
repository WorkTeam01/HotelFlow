<?php
require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'equipajes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Agregar los estilos específicos para este módulo
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];
$module_scripts = ['almacenamiento-equipaje/show-equipaje'];

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de equipaje no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del equipaje
$controller = new AlmacenamientoEquipajeController();
$equipaje = $controller->mostrar($id);

// Verificar si el equipaje existe
if (!$equipaje) {
    $_SESSION['mensaje'] = 'Equipaje no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Determinar clase CSS y texto para el estado
$clase_estado = '';
$texto_estado = '';
$icono_estado = '';

switch ($equipaje['estado']) {
    case 'almacenado':
        $clase_estado = 'badge-warning';
        $texto_estado = 'Almacenado';
        $icono_estado = 'fa-clock';
        break;
    case 'retirado':
        $clase_estado = 'badge-success';
        $texto_estado = 'Retirado';
        $icono_estado = 'fa-check-circle';
        break;
    case 'perdido':
        $clase_estado = 'badge-danger';
        $texto_estado = 'Perdido';
        $icono_estado = 'fa-exclamation-triangle';
        break;
    case 'dañado':
        $clase_estado = 'badge-dark';
        $texto_estado = 'Dañado';
        $icono_estado = 'fa-times-circle';
        break;
    default:
        $clase_estado = 'badge-secondary';
        $texto_estado = 'Desconocido';
        $icono_estado = 'fa-question-circle';
        break;
}

// Calcular tiempo almacenado
$fecha_entrada = new DateTime($equipaje['fechaentrada']);
$fecha_actual = new DateTime();
$fecha_salida = !empty($equipaje['fechasalida']) ? new DateTime($equipaje['fechasalida']) : null;

// Usar la fecha de salida si existe, de lo contrario usar la fecha actual
$fecha_fin = $fecha_salida ? $fecha_salida : $fecha_actual;
$intervalo = $fecha_entrada->diff($fecha_fin);

// Formatear tiempo de almacenamiento
$tiempo_almacenado = '';
if ($intervalo->days > 0) {
    $tiempo_almacenado .= $intervalo->days . ' día(s) ';
}
if ($intervalo->h > 0) {
    $tiempo_almacenado .= $intervalo->h . ' hora(s) ';
}
if ($intervalo->i > 0) {
    $tiempo_almacenado .= $intervalo->i . ' minuto(s)';
}
if (empty($tiempo_almacenado)) {
    $tiempo_almacenado = 'Menos de un minuto';
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Detalle de Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Tarjeta Principal -->
            <div class="col-md-8">
                <div class="card card-info card-outline equipaje-card estado-<?= $equipaje['estado']; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Equipaje</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center mb-4">
                                    <div class="ticket-code">
                                        <i class="fas fa-ticket-alt text-primary"></i>
                                        <?= htmlspecialchars($equipaje['codigo_ticket']); ?>
                                    </div>
                                    <p class="text-muted mt-2">Código de identificación único</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center mb-4">
                                    <h4>
                                        <span class="badge badge-estado badge-<?= $equipaje['estado']; ?>">
                                            <i class="fas <?= $icono_estado; ?>"></i>
                                            <?= $texto_estado; ?>
                                        </span>
                                    </h4>
                                    <p class="text-muted mt-2">Estado actual del equipaje</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-light info-box-equipaje">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-suitcase"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tipo de Equipaje</span>
                                        <span class="info-box-number">
                                            <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'No especificado'); ?>
                                        </span>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= htmlspecialchars($equipaje['cantidad_piezas']); ?> pieza(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light info-box-equipaje">
                                    <span class="info-box-icon bg-success"><i class="fas fa-money-bill"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Monto Pagado</span>
                                        <span class="info-box-number">
                                            Bs. <?= number_format($equipaje['monto'], 2); ?>
                                        </span>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Tarifa para <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'equipaje'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-light info-box-equipaje">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-calendar-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Fecha de Entrada</span>
                                        <span class="info-box-number">
                                            <?= date('d/m/Y H:i:s', strtotime($equipaje['fechaentrada'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light info-box-equipaje">
                                    <span class="info-box-icon <?= !empty($equipaje['fechasalida']) ? 'bg-success' : 'bg-warning'; ?>">
                                        <i class="fas <?= !empty($equipaje['fechasalida']) ? 'fa-calendar-minus' : 'fa-hourglass-half'; ?>"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">
                                            <?= !empty($equipaje['fechasalida']) ? 'Fecha de Salida' : 'Tiempo Almacenado'; ?>
                                        </span>
                                        <span class="info-box-number">
                                            <?php if (!empty($equipaje['fechasalida'])): ?>
                                                <?= date('d/m/Y H:i:s', strtotime($equipaje['fechasalida'])); ?>
                                            <?php else: ?>
                                                <?= $tiempo_almacenado; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-light mt-3">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-align-left"></i> Descripción del Equipaje</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($equipaje['descripcion'])): ?>
                                    <p><?= nl2br(htmlspecialchars($equipaje['descripcion'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">No se proporcionó descripción.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card card-secondary mt-3">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-shield"></i> Información de Registro</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-user"></i> Usuario que Registró:</strong></p>
                                        <p class="text-muted ml-4">
                                            <?= htmlspecialchars($equipaje['nombre_usuario'] ?? 'Usuario no disponible'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-calendar-alt"></i> Fecha de Registro:</strong></p>
                                        <p class="text-muted ml-4">
                                            <?= isset($equipaje['fechacreacion']) ? date('d/m/Y H:i:s', strtotime($equipaje['fechacreacion'])) : date('d/m/Y H:i:s', strtotime($equipaje['fechaentrada'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta del Cliente y Acciones -->
            <div class="col-md-4">
                <!-- Tarjeta del Cliente -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-circle"></i> Información del Cliente</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                            <h4><?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'Cliente no disponible'); ?></h4>
                        </div>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card text-primary"></i> Tipo de Documento</b>
                                <span class="float-right"><?= htmlspecialchars($equipaje['tipodoc_cliente'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-passport text-primary"></i> Número de Documento</b>
                                <span class="float-right"><?= htmlspecialchars($equipaje['numdoc_cliente'] ?? 'N/A'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone text-primary"></i> Teléfono</b>
                                <span class="float-right">
                                    <?php if (!empty($equipaje['telefono_cliente'])): ?>
                                        <a href="tel:<?= htmlspecialchars($equipaje['telefono_cliente']); ?>">
                                            <?= htmlspecialchars($equipaje['telefono_cliente']); ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Acciones de Estado -->
                <?php if ($equipaje['estado'] !== 'retirado'): ?>
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tasks"></i> Cambiar Estado</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical btn-block">
                                <?php if ($equipaje['estado'] !== 'retirado'): ?>
                                    <button type="button" class="btn btn-success mb-2 btn-retirar cambiar-estado"
                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=retirado&csrf_token=<?= generateCSRFToken(); ?>"
                                        data-estado="retirado"
                                        data-icono="success">
                                        <i class="fas fa-check-circle"></i> Marcar como Retirado
                                    </button>
                                <?php endif; ?>
                                <?php if ($equipaje['estado'] !== 'perdido'): ?>
                                    <button type="button" class="btn btn-danger mb-2 btn-marcar-perdido cambiar-estado"
                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=perdido&csrf_token=<?= generateCSRFToken(); ?>"
                                        data-estado="perdido"
                                        data-icono="error">
                                        <i class="fas fa-exclamation-triangle"></i> Marcar como Perdido
                                    </button>
                                <?php endif; ?>
                                <?php if ($equipaje['estado'] !== 'dañado'): ?>
                                    <button type="button" class="btn btn-dark mb-2 cambiar-estado"
                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=dañado&csrf_token=<?= generateCSRFToken(); ?>"
                                        data-estado="dañado"
                                        data-icono="warning">
                                        <i class="fas fa-times-circle"></i> Marcar como Dañado
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Botones de navegación y acciones -->
                <div class="card">
                    <div class="card-header card-outline card-info">
                        <h3 class="card-title"><i class="fas fa-tasks"></i> Acciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body g-1">
                        <a href="<?= $URL; ?>views/almacenamiento-equipaje/" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>

                        <?php if ($equipaje['estado'] !== 'retirado'): ?>
                            <a href="<?= $URL; ?>views/almacenamiento-equipaje/update.php?id=<?= $equipaje['idalmacen']; ?>"
                                class="btn btn-warning btn-block">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>

                        <!-- Botón de impresión -->
                        <a href="<?= $URL; ?>views/almacenamiento-equipaje/recibo.php?id=<?= $equipaje['idalmacen']; ?>"
                            class="btn btn-info btn-block" target="_blank">
                            <i class="fas fa-print"></i> Imprimir Ticket
                        </a>

                        <!-- Historial de cambios (si procede) -->
                        <?php if (isset($equipaje['fechaactualizacion']) || isset($equipaje['fechasalida'])): ?>
                            <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#modalHistorial">
                                <i class="fas fa-history"></i> Ver Historial
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para historial de cambios -->
<div class="modal fade" id="modalHistorial" tabindex="-1" role="dialog" aria-labelledby="modalHistorialLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalHistorialLabel"><i class="fas fa-history"></i> Historial de Cambios</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Timeline AdminLTE nativo -->
                <div class="timeline">
                    <!-- Punto de entrada inicial -->
                    <div class="time-label">
                        <span class="bg-primary">
                            <?= date('d/m/Y', strtotime($equipaje['fechaentrada'])); ?>
                        </span>
                    </div>

                    <!-- Registro inicial -->
                    <div>
                        <i class="fas fa-calendar-plus bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($equipaje['fechaentrada'])); ?></span>
                            <h3 class="timeline-header"><strong>Registro inicial</strong></h3>
                            <div class="timeline-body">
                                <p>Equipaje registrado en el sistema con estado
                                    <span class="badge badge-warning">Almacenado</span>
                                </p>
                                <div class="callout callout-info">
                                    <small>
                                        <ul class="list-unstyled">
                                            <li><strong>Cliente:</strong> <?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'N/A'); ?></li>
                                            <li><strong>Tipo:</strong> <?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'N/A'); ?></li>
                                            <li><strong>Código:</strong> <?= htmlspecialchars($equipaje['codigo_ticket']); ?></li>
                                        </ul>
                                    </small>
                                </div>
                            </div>
                            <div class="timeline-footer">
                                <span class="text-muted">Registrado por: <?= htmlspecialchars($equipaje['nombre_usuario'] ?? 'Sistema'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($equipaje['fechaactualizacion']) && $equipaje['fechaactualizacion'] != $equipaje['fechaentrada']): ?>
                        <!-- Punto de actualización -->
                        <?php if (date('d/m/Y', strtotime($equipaje['fechaactualizacion'])) != date('d/m/Y', strtotime($equipaje['fechaentrada']))): ?>
                            <div class="time-label">
                                <span class="bg-warning">
                                    <?= date('d/m/Y', strtotime($equipaje['fechaactualizacion'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Actualización -->
                        <div>
                            <i class="fas fa-edit bg-yellow"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($equipaje['fechaactualizacion'])); ?></span>
                                <h3 class="timeline-header"><strong>Actualización</strong></h3>
                                <div class="timeline-body">
                                    <p>Se actualizó la información del equipaje.</p>
                                    <?php if (isset($equipaje['cambios_registro']) && !empty($equipaje['cambios_registro'])): ?>
                                        <div class="callout callout-warning">
                                            <small>
                                                <ul>
                                                    <?php foreach ($equipaje['cambios_registro'] as $campo => $valor): ?>
                                                        <li><strong><?= htmlspecialchars($campo); ?>:</strong> <?= htmlspecialchars($valor); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($equipaje['fechasalida'])): ?>
                        <!-- Punto de cambio de estado -->
                        <?php
                        $fecha_salida_dia = date('d/m/Y', strtotime($equipaje['fechasalida']));
                        $fecha_actualizacion_dia = isset($equipaje['fechaactualizacion']) ? date('d/m/Y', strtotime($equipaje['fechaactualizacion'])) : '';
                        $fecha_entrada_dia = date('d/m/Y', strtotime($equipaje['fechaentrada']));

                        if ($fecha_salida_dia != $fecha_actualizacion_dia && $fecha_salida_dia != $fecha_entrada_dia):
                        ?>
                            <div class="time-label">
                                <span class="bg-success">
                                    <?= date('d/m/Y', strtotime($equipaje['fechasalida'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Cambio de estado -->
                        <div>
                            <?php
                            $estado_icon = 'fa-check-circle';
                            $estado_bg = 'bg-success';

                            if ($equipaje['estado'] == 'perdido') {
                                $estado_icon = 'fa-exclamation-triangle';
                                $estado_bg = 'bg-danger';
                            } else if ($equipaje['estado'] == 'dañado') {
                                $estado_icon = 'fa-times-circle';
                                $estado_bg = 'bg-dark';
                            }
                            ?>
                            <i class="fas <?= $estado_icon; ?> <?= $estado_bg; ?>"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($equipaje['fechasalida'])); ?></span>
                                <h3 class="timeline-header"><strong>Cambio de estado</strong></h3>
                                <div class="timeline-body">
                                    <p>Estado cambiado a
                                        <span class="badge badge-<?= $equipaje['estado'] == 'retirado' ? 'success' : ($equipaje['estado'] == 'perdido' ? 'danger' : 'dark'); ?>">
                                            <?= ucfirst($texto_estado); ?>
                                        </span>
                                    </p>

                                    <?php if ($equipaje['estado'] == 'retirado'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-info-circle"></i> El equipaje ha sido entregado al cliente.
                                        </div>
                                    <?php elseif ($equipaje['estado'] == 'perdido'): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle"></i> El equipaje ha sido marcado como perdido.
                                        </div>
                                    <?php elseif ($equipaje['estado'] == 'dañado'): ?>
                                        <div class="alert alert-dark">
                                            <i class="fas fa-exclamation-circle"></i> El equipaje ha sido marcado como dañado.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-footer">
                                    <span>Tiempo total almacenado: <strong><?= $tiempo_almacenado; ?></strong></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Final del timeline -->
                    <div>
                        <i class="fas fa-clock bg-gray"></i>
                    </div>
                </div>
                <!-- ./timeline -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

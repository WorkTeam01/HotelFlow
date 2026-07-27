<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Verificar si el usuario está autenticado
requireLogin();

$idusuario = $_SESSION['usuario_id'] ?? 0;

// Verificar permisos
$auth = new AuthorizationService();
if (!($auth->puedeAccederModulo($idusuario, 'recepcion'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a recepciones.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Instanciar controlador
$controller = new RecepcionController();

// Obtener datos - usando el método index existente que devuelve array completo
$datos = $controller->index();
$recepciones = $datos['recepciones'] ?? [];
$estadisticas = $datos['estadisticas'] ?? [];
$habitaciones_disponibles = $datos['habitaciones_disponibles'] ?? [];
$recepciones_en_curso = $datos['recepciones_en_curso'] ?? [];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Lista de Recepciones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Lista de Recepciones</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Habitaciones Disponibles</span>
                        <span class="info-box-number"><?= count($habitaciones_disponibles); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-bed"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Habitaciones Ocupadas</span>
                        <span class="info-box-number"><?= count($recepciones_en_curso); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">En Mantenimiento</span>
                        <span class="info-box-number"><?= $estadisticas['mantenimiento']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movimientos de hoy -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-chart-pie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ocupación</span>
                        <?php
                        $total_habitaciones = $estadisticas['total_habitaciones'];
                        $porcentaje_ocupacion = $total_habitaciones > 0 ? (count($recepciones_en_curso) / $total_habitaciones) * 100 : 0;
                        ?>
                        <span class="info-box-number"><?= number_format($porcentaje_ocupacion, 0); ?>%</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ingresos Hoy</span>
                        <?php
                        // Asumiendo que tienes este dato en las estadísticas
                        $ingresos_hoy = $estadisticas['ingresos_hoy'] ?? 0;
                        ?>
                        <span class="info-box-number">$<?= number_format($ingresos_hoy, 2); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-sign-in-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Check-ins Hoy</span>
                        <span class="info-box-number"><?= $estadisticas['checkins_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-sign-out-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Check-outs Previstos</span>
                        <span class="info-box-number"><?= $estadisticas['checkouts_hoy']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción rápida -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Rápidas</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= $URL; ?>views/recepcion/create.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus-circle"></i><br>
                                    Nueva Reserva
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= $URL; ?>views/recepcion" class="btn btn-success btn-block">
                                    <i class="fas fa-tachometer-alt"></i><br>
                                    Recepciones
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= $URL; ?>views/habitaciones" class="btn btn-info btn-block">
                                    <i class="fas fa-door-open"></i><br>
                                    Ver Habitaciones
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de recepciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Recepciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaRecepciones" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Habitación</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Total</th>
                                        <th>Pagado</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($recepciones as $recepcion):
                                        $estado = $recepcion['estado'];
                                        $clase_estado = '';
                                        $texto_estado = '';

                                        switch ($estado) {
                                            case 'reservado':
                                                $clase_estado = 'badge-warning';
                                                $texto_estado = 'Reservado';
                                                break;
                                            case 'en_curso':
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'En curso';
                                                break;
                                            case 'finalizado':
                                                $clase_estado = 'badge-info';
                                                $texto_estado = 'Finalizado';
                                                break;
                                            case 'cancelado':
                                                $clase_estado = 'badge-danger';
                                                $texto_estado = 'Cancelado';
                                                break;
                                            default:
                                                $clase_estado = 'badge-secondary';
                                                $texto_estado = ucfirst($estado);
                                        }

                                        // Calcular saldo pendiente
                                        $saldo = ($recepcion['montototal'] ?? 0) - ($recepcion['montopagado'] ?? 0);
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td>
                                                <?= htmlspecialchars(($recepcion['nombre_cliente'] ?? '') . ' ' . ($recepcion['apellido_cliente'] ?? '')); ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($recepcion['numdoc_cliente'] ?? ''); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= htmlspecialchars($recepcion['numero_habitacion'] ?? 'N/A'); ?></strong>
                                                <?php if (!empty($recepcion['piso_nombre'])): ?>
                                                    <br><small class="text-muted">Piso: <?= htmlspecialchars($recepcion['piso_nombre']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?= !empty($recepcion['fechaentrada']) ? date('d/m/Y H:i', strtotime($recepcion['fechaentrada'])) : 'N/A'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($recepcion['fechasalida'])): ?>
                                                    <?= date('d/m/Y H:i', strtotime($recepcion['fechasalida'])); ?>
                                                <?php elseif (!empty($recepcion['fechasalida_prevista'])): ?>
                                                    <span class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($recepcion['fechasalida_prevista'])); ?>
                                                        <br><small>(Previsto)</small>
                                                    </span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                $<?= number_format(floatval($recepcion['montototal'] ?? 0), 2); ?>
                                            </td>
                                            <td class="text-right">
                                                $<?= number_format(floatval($recepcion['montopagado'] ?? 0), 2); ?>
                                                <?php if ($saldo > 0): ?>
                                                    <br><small class="text-danger">Saldo: $<?= number_format($saldo, 2); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/recepcion/show.php?id=<?= $recepcion['idrecepcion']; ?>"
                                                        class="btn btn-info btn-sm" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($estado === 'reservado'): ?>
                                                        <a href="<?= $URL; ?>views/recepcion/update.php?id=<?= $recepcion['idrecepcion']; ?>"
                                                            class="btn btn-warning btn-sm" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-success btn-sm btn-checkin"
                                                            data-id="<?= $recepcion['idrecepcion']; ?>"
                                                            title="Realizar Check-in">
                                                            <i class="fas fa-sign-in-alt"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($estado === 'en_curso'): ?>
                                                        <button type="button" class="btn btn-warning btn-sm btn-checkout"
                                                            data-id="<?= $recepcion['idrecepcion']; ?>"
                                                            title="Realizar Check-out">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <a href="<?= $URL; ?>views/recepcion/recibo.php?id=<?= $recepcion['idrecepcion']; ?>"
                                                        class="btn btn-secondary btn-sm" target="_blank" title="Imprimir comprobante">
                                                        <i class="fas fa-print"></i>
                                                    </a>

                                                    <?php if (in_array($estado, ['reservado', 'en_curso'])): ?>
                                                        <button type="button" class="btn btn-danger btn-sm btn-cancelar"
                                                            data-id="<?= $recepcion['idrecepcion']; ?>"
                                                            title="Cancelar reserva">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $URL; ?>public/js/modules/recepciones/lista-recepciones.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
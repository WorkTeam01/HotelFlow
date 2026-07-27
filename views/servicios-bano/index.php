<?php
require_once __DIR__ . '/../../controllers/servicios-bano/ServicioBanoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? ''; // Obtener el ID del usuario desde la sesión

// Verificar si el usuario tiene permiso para crear servicios de baño
$auth = new AuthorizationService();

if (!($auth->puedeAccederModulo($idusuario, 'servicios_bano'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para crear servicios de baños.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/servicios-bano');
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['servicios-bano/index-servicios-bano', 'servicios-bano/servicio-bano-rapido'];
include_once '../layouts/header.php';

$controller = new ServicioBanoController();
$servicios = $controller->index();
$estadisticas = $controller->getEstadisticas();

// Obtener los baños disponibles para el registro rápido
$banos = $controller->getBanosDisponibles();

// Verificar disponibilidad del servicio
$disponibilidad = $controller->validarDisponibilidadServicio();

// Obtener los baños disponibles solo si el servicio está disponible
$banos = [];
if ($disponibilidad['disponible']) {
    $banos = $controller->getBanosDisponibles();
}

// Verificar si el usuario está autenticado
requireLogin();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Servicios de Baños</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Servicios de Baños</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Tarjetas de estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $estadisticas['total']; ?></h3>
                        <p>Servicios Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shower"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $estadisticas['activos']; ?></h3>
                        <p>Activos Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $estadisticas['inactivos']; ?></h3>
                        <p>Inactivos Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($estadisticas['ingresos_totales'], 2); ?></h3>
                        <p>Ingresos Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <!-- /.row -->

        <!-- Botones de acción rápida -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Registro Rápido - Baños Disponibles</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$disponibilidad['disponible']): ?>
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Servicio No Disponible:</strong> <?= htmlspecialchars($disponibilidad['message']); ?>
                            </div>
                            <div class="text-center">
                                <p class="text-muted">No se pueden crear servicios de baño en este momento.</p>
                                <a href="<?= $URL; ?>views/productos" class="btn btn-primary">
                                    <i class="fas fa-boxes"></i> Gestionar Productos
                                </a>
                            </div>
                        <?php elseif (empty($banos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                No hay baños disponibles en este momento.
                            </div>
                        <?php else: ?>
                            <!-- Mostrar alerta de stock bajo si es necesario -->
                            <?php
                            $stockActual = $disponibilidad['stock_actual'] ?? 0;
                            $stockMinimo = $disponibilidad['stock_minimo'] ?? 0;

                            if ($stockActual <= $stockMinimo && $stockMinimo > 0): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Stock Bajo:</strong> Quedan <?= $stockActual; ?> unidades de papel higiénico
                                    (mínimo recomendado: <?= $stockMinimo; ?>).
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <?php foreach ($banos as $bano): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                        <button type="button" class="btn btn-block btn-success btn-registro-rapido"
                                            data-id="<?= $bano['idbano']; ?>"
                                            data-nombre="<?= htmlspecialchars($bano['nombre']); ?>"
                                            data-precio="<?= $bano['precio']; ?>">
                                            <i class="fas fa-bolt mr-2"></i>
                                            <?= htmlspecialchars($bano['nombre']); ?> - <?= number_format($bano['precio'], 2); ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-center mt-3">
                                <a href="<?= $URL; ?>views/servicios-bano/create.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus-circle"></i> Registro Manual
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Servicios de Baños</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaServicios" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Baño</th>
                                        <th>Cliente</th>
                                        <th>Tipo</th>
                                        <th>Método de Pago</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($servicios as $servicio) :
                                        $estado = $servicio['estado'];
                                        switch ($estado) {
                                            case 'cancelado':
                                                $clase_estado = 'badge-danger';
                                                $texto_estado = 'Cancelado';
                                                break;
                                            case 'finalizado':
                                                $clase_estado = 'badge-info';
                                                $texto_estado = 'Finalizado';
                                                break;
                                            case 'iniciado':
                                            default:
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'Iniciado';
                                                break;
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($servicio['nombre_bano'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($servicio['tipo_cliente'] == 'Publico' ? 'Cliente público' : ($servicio['nombre_cliente'] ?? 'N/A')); ?></td>
                                            <td><?= htmlspecialchars($servicio['tipo_cliente'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($servicio['metodopago'] ?? 'N/A'); ?></td>
                                            <td>Bs. <?= number_format(floatval($servicio['total'] ?? 0), 2); ?></td>
                                            <td><?= !empty($servicio['fecha']) ? date('d/m/Y H:i', strtotime($servicio['fecha'])) : 'N/A'; ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/servicios-bano/show.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/servicios-bano/update.php?id=<?= $servicio['idservicio']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($servicio['estado'] !== 'cancelado'): ?>
                                                        <button type="button" class="btn btn-danger btn-sm btn-cambiar-estado"
                                                            data-id="<?= $servicio['idservicio']; ?>"
                                                            data-estado="<?= $servicio['estado']; ?>"
                                                            data-accion="cancelado"
                                                            title="Cancelar servicio">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-success btn-sm btn-cambiar-estado"
                                                            data-id="<?= $servicio['idservicio']; ?>"
                                                            data-estado="<?= $servicio['estado']; ?>"
                                                            data-accion="iniciado"
                                                            title="Reactivar servicio">
                                                            <i class="fas fa-check"></i>
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
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
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

<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'ventas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['ventas/index-ventas'];
include_once '../layouts/header.php';

$controller = new VentaController();
$esAdmin = $authService->esAdministrador($idusuario);
$ventas = $controller->index($esAdmin ? null : $idusuario);
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Ventas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Ventas</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas Hoy</span>
                        <span class="info-box-number"><?= $estadisticas['ventas_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Hoy</span>
                        <span class="info-box-number"><?= number_format($estadisticas['total_hoy'], 2); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user-tie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cliente Top</span>
                        <span class="info-box-number">
                            <?= $estadisticas['cliente_mas_compro'] ? htmlspecialchars($estadisticas['cliente_mas_compro']['nombre_cliente']) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Producto Top</span>
                        <span class="info-box-number">
                            <?= $estadisticas['producto_mas_vendido'] ? htmlspecialchars($estadisticas['producto_mas_vendido']['producto_nombre']) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Historial de Ventas</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/ventas/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Venta
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="tablaVentas" class="table table-sm table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Usuario</th>
                                    <th>Total</th>
                                    <th>Método Pago</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($ventas as $venta) :
                                    $infoMetodo = $venta['info_metodo_pago'] ?? ['texto' => 'Efectivo', 'clase' => 'badge-success', 'tooltip' => '', 'es_mixto' => false];
                                    $metodoTooltip = $infoMetodo['tooltip'] ?? '';
                                    $codigoVenta = 'VENT-' . str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT);
                                    $esActiva = (int)$venta['estado'] === 1;
                                    $claseEstado = $esActiva ? 'badge-success' : 'badge-danger';
                                    $textoEstado = $esActiva ? 'Activa' : 'Anulada';
                                ?>
                                    <tr>
                                        <td><?= $codigoVenta; ?></td>
                                        <td><?= date('d/m/Y', strtotime($venta['fechaventa'])); ?></td>
                                        <td><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></td>
                                        <td><?= htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></td>
                                        <td class="text-right"><?= number_format($venta['totalventa'], 2); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= htmlspecialchars($infoMetodo['clase']); ?>"
                                                <?php if (!empty($metodoTooltip)) : ?>
                                                data-toggle="tooltip" data-html="true" title="<?= htmlspecialchars($metodoTooltip, ENT_QUOTES); ?>"
                                                <?php endif; ?>>
                                                <?= htmlspecialchars($infoMetodo['texto']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $claseEstado; ?>"><?= $textoEstado; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="<?= $URL; ?>views/ventas/show.php?id=<?= (int)$venta['idventa']; ?>"
                                                    class="btn btn-info btn-sm"
                                                    title="Ver detalle" data-toggle="tooltip"
                                                    aria-label="Ver detalle de <?= $codigoVenta; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= $URL; ?>views/ventas/recibo.php?id=<?= (int)$venta['idventa']; ?>"
                                                    class="btn btn-secondary btn-sm<?= $esActiva ? '' : ' disabled'; ?>"
                                                    <?= $esActiva ? '' : 'disabled tabindex="-1" aria-disabled="true"'; ?>
                                                    title="<?= $esActiva ? 'Imprimir recibo' : 'Recibo no disponible (venta anulada)'; ?>"
                                                    data-toggle="tooltip" target="_blank" rel="noopener"
                                                    aria-label="Imprimir recibo de <?= $codigoVenta; ?>">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                                <?php if ($esActiva && $esAdmin) : ?>
                                                    <button type="button" class="btn btn-danger btn-sm btn-anular-venta"
                                                        data-id="<?= (int)$venta['idventa']; ?>"
                                                        data-titulo="<?= $codigoVenta; ?>"
                                                        title="Anular venta" data-toggle="tooltip"
                                                        aria-label="Anular <?= $codigoVenta; ?>">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
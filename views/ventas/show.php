<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

if (!isset($URL)) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $URL = $config['app']['url'];
}

$idusuario = $_SESSION['usuario_id'] ?? '';
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'ventas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Verificar ID de venta
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $_SESSION['mensaje'] = 'ID de venta no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Obtener datos de la venta (ver() adjunta helpers: totales, info_metodo_pago, etc.)
$controller = new VentaController();
$venta = $controller->ver($id);

if (!$venta) {
    $_SESSION['mensaje'] = 'Venta no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// IDOR: no-admin solo puede ver sus propias ventas
if (!$authService->esAdministrador($idusuario) && $venta['idusuario'] != $idusuario) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver esta venta.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

// Helpers ya resueltos por ver()
$esActiva = (int)$venta['estado'] === 1;
$esPagoMixto = $venta['info_metodo_pago']['es_mixto'];

$skip_datatables = true;
$skip_select2 = true;
$skip_chartjs = true;
$module_styles = ['ventas/ventas'];
$module_scripts = ['ventas/anular-venta'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Venta <?= 'VENT-' . str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas/"><i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Detalle de Venta</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna izquierda: contexto de la venta (quién, cuándo, cómo se pagó) y acciones -->
            <div class="col-lg-4 mb-3">
                <div class="sidebar-sticky">
                    <!-- Tarjeta de información general -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Información General</h3>
                        </div>
                        <div class="card-body box-profile">
                            <div class="text-center mb-3">
                                <i class="fas fa-file-invoice-dollar fa-3x text-info"></i>
                                <h3 class="mt-2 mb-0 text-info font-weight-bold">
                                    <?= number_format($venta['totales']['total'], 2); ?> Bs.
                                </h3>
                                <p class="text-muted mb-0">Total Venta</p>
                            </div>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b><i class="fas fa-hashtag mr-2"></i>Código</b>
                                    <span class="float-right">VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-calendar-alt mr-2"></i>Fecha Venta</b>
                                    <span class="float-right"><?= date('d/m/Y H:i', strtotime($venta['fechaventa'])); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user mr-2"></i>Cliente</b>
                                    <span class="float-right"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tie mr-2"></i>Vendedor</b>
                                    <span class="float-right"><?= htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                                    <span class="float-right">
                                        <span class="badge <?= $esActiva ? 'badge-success' : 'badge-danger'; ?>" role="status">
                                            <?= $esActiva ? 'Activa' : 'Anulada'; ?>
                                        </span>
                                    </span>
                                </li>
                            </ul>

                            <div class="d-flex justify-content-between">
                                <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>

                                <?php if ($esActiva && $authService->esAdministrador($idusuario)) : ?>
                                    <button type="button" class="btn btn-danger btn-anular-venta"
                                        data-id="<?= (int)$venta['idventa']; ?>"
                                        data-titulo="<?= 'VENT-' . str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>"
                                        title="Anular venta" data-toggle="tooltip"
                                        aria-label="Anular venta <?= 'VENT-' . str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>">
                                        <i class="fas fa-ban"></i> Anular
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de métodos de pago -->
                    <div class="card <?= $esPagoMixto ? 'card-purple card-outline' : 'card-info card-outline'; ?>">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas <?= $esPagoMixto ? 'fa-money-check-alt' : 'fa-money-bill-wave'; ?>"></i>
                                <?= $esPagoMixto ? 'Pago Mixto' : 'Método de Pago'; ?>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($venta['pagos'] as $pago) :
                                [$iconoMetodo, $claseMetodo] = $controller->obtenerIconoMetodoPago($pago['metodopago']);
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge <?= $claseMetodo ?>">
                                        <i class="<?= $iconoMetodo ?>"></i> <?= ucfirst($pago['metodopago']) ?>
                                    </span>
                                    <span><?= number_format($pago['monto'], 2); ?> Bs.</span>
                                </div>
                                <?php if (strtolower($pago['metodopago']) === 'efectivo' && (float)$pago['cambio'] > 0) : ?>
                                    <div class="d-flex justify-content-between text-muted small mb-2">
                                        <span>Recibido <?= number_format($pago['pagorecibido'], 2); ?> Bs.</span>
                                        <span>Cambio <?= number_format($pago['cambio'], 2); ?> Bs.</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($esPagoMixto) : ?>
                                <div class="d-flex justify-content-between font-weight-bold border-top pt-2 mt-1 mb-0">
                                    <span>Total Pagado</span>
                                    <span><?= number_format($venta['totales']['total_pagado'], 2); ?> Bs.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: qué se vendió -->
            <div class="col-lg-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Productos Vendidos</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th class="text-right">Precio Unit.</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-right">Descuento</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $contador = 1;
                                    foreach ($venta['desglose_detalles']['lineas'] as $linea) : ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td>
                                                <?= htmlspecialchars($linea['producto_nombre']); ?>
                                                <small class="text-muted d-block">
                                                    Código: <?= htmlspecialchars($linea['producto_codigo'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td class="text-right"><?= number_format($linea['precioventa'], 2); ?> Bs.</td>
                                            <td class="text-center"><?= $linea['cantidad']; ?></td>
                                            <td class="text-right">
                                                <?php if ($linea['calculo']['descuento_total'] > 0) : ?>
                                                    <span class="text-danger">-<?= number_format($linea['calculo']['descuento_total'], 2); ?> Bs.</span>
                                                <?php else : ?>
                                                    0.00 Bs.
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right"><?= number_format($linea['calculo']['neto'], 2); ?> Bs.</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-right">Subtotal:</th>
                                        <th class="text-right"><?= number_format($venta['totales']['subtotal'], 2); ?> Bs.</th>
                                    </tr>
                                    <?php if ($venta['totales']['descuento'] > 0) : ?>
                                        <tr>
                                            <th colspan="5" class="text-right">Descuento Total:</th>
                                            <th class="text-right text-danger">-<?= number_format($venta['totales']['descuento'], 2); ?> Bs.</th>
                                        </tr>
                                    <?php endif; ?>
                                    <tr class="bg-light">
                                        <th colspan="5" class="text-right">Total:</th>
                                        <th class="text-right"><?= number_format($venta['totales']['total'], 2); ?> Bs.</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información adicional -->
                <?php if (!empty($venta['observacion']) || !$esActiva) : ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Información Adicional</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($venta['observacion'])) : ?>
                                <div class="form-group mb-0">
                                    <label>Observaciones:</label>
                                    <div class="p-2 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($venta['observacion'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$esActiva) : ?>
                                <div class="alert alert-warning <?= !empty($venta['observacion']) ? 'mt-3 mb-0' : ''; ?>">
                                    <i class="icon fas fa-info-circle"></i>
                                    Esta venta fue anulada el <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>.
                                    El stock de los productos fue restaurado.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
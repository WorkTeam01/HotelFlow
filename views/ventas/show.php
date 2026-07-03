<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Verificar si la variable $URL está definida
if (!isset($URL)) {
    $config = require_once __DIR__ . '/../../config/config.php';
    $URL = $config['app']['url'];
}

$idusuario = $_SESSION['usuario_id'] ?? '';
$authService = new AuthorizationService();

// Verificar permisos
if (!($authService->tieneAccesoCritico($idusuario, 'ventas'))) {
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

// Obtener datos de la venta
$controller = new VentaController();
$venta = $controller->ver($id);

if (!$venta) {
    $_SESSION['mensaje'] = 'Venta no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas/index.php');
    exit;
}

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Venta #<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas/"> <i class="fas fa-shopping-cart"></i> Ventas</a></li>
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
            <div class="col-md-4">
                <!-- Tarjeta de información básica -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Información General</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Código:</label>
                            <p class="text-muted">VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Fecha Venta:</label>
                            <p class="text-muted"><?= date('d/m/Y H:i', strtotime($venta['fechaventa'])); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Cliente:</label>
                            <p class="text-muted"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Vendedor:</label>
                            <p class="text-muted"><?= htmlspecialchars($venta['usuario_nombre'] ?? 'Usuario no registrado'); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Método de Pago:</label>
                            <p class="text-muted">
                                <?php
                                switch ($venta['metodopago']) {
                                    case 'Efectivo':
                                        echo '<span class="badge badge-success">Efectivo</span>';
                                        break;
                                    case 'QR':
                                        echo '<span class="badge badge-info">QR</span>';
                                        break;
                                    default:
                                        echo '<span class="badge badge-secondary">' . $venta['metodopago'] . '</span>';
                                }
                                ?>
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Estado:</label>
                            <p>
                                <?php if ($venta['estado'] == 1) : ?>
                                    <span class="badge badge-success">Activa</span>
                                <?php else : ?>
                                    <span class="badge badge-danger">Anulada</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Total Venta:</label>
                            <p class="text-primary font-weight-bold" style="font-size: 1.5rem;">
                                <?= number_format($venta['totalventa'], 2); ?> Bs.
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Pago Recibido:</label>
                            <p class="text-success"><?= number_format($venta['pagorecibido'], 2); ?> Bs.</p>
                        </div>

                        <div class="form-group">
                            <label>Cambio:</label>
                            <p class="text-info"><?= number_format($venta['cambio'], 2); ?> Bs.</p>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>

                            <?php if ($venta['estado'] == 1) : ?>
                                <button type="button" class="btn btn-danger btn-anular-venta"
                                    data-id="<?= $venta['idventa']; ?>"
                                    data-nombre="Venta #<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>">
                                    <i class="fas fa-ban"></i> Anular Venta
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Productos Vendidos</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
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
                                    <?php
                                    $contador = 1;
                                    foreach ($venta['detalles'] as $detalle) :
                                        $subtotal = ($detalle['precioventa'] * $detalle['cantidad']) - $detalle['descuento'];
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td class="text-right"><?= number_format($detalle['precioventa'], 2); ?> Bs.</td>
                                            <td class="text-center"><?= $detalle['cantidad']; ?></td>
                                            <td class="text-right"><?= number_format($detalle['descuento'], 2); ?> Bs.</td>
                                            <td class="text-right"><?= number_format($subtotal, 2); ?> Bs.</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-right">Total:</th>
                                        <th class="text-right"><?= number_format($venta['totalventa'], 2); ?> Bs.</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información adicional -->
                <div class="card card-secondary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Información Adicional</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de Creación:</label>
                                    <p class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($venta['fechacreacion'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($venta['fechaactualizacion'])) : ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Última Actualización:</label>
                                        <p class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($venta['estado'] == 0) : ?>
                            <div class="alert alert-warning">
                                <i class="icon fas fa-info-circle"></i>
                                Esta venta fue anulada el <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>.
                                El stock de los productos fue restaurado.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración del botón de anulación
        const btnAnular = document.querySelector('.btn-anular-venta');

        if (btnAnular) {
            btnAnular.addEventListener('click', function() {
                const ventaId = this.dataset.id;
                const nombreVenta = this.dataset.nombre;

                Swal.fire({
                    title: `¿Anular ${nombreVenta}?`,
                    text: "Esta acción restaurará el stock de los productos y no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `<?= $URL; ?>controllers/ventas/anular_venta.php?id=${ventaId}&accion=anular`;
                    }
                });
            });
        }
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
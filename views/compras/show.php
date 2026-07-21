<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tieneAccesoCritico($idusuario, 'compras'))) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de compra no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos de la compra
$controller = new CompraController();
$compra = $controller->ver($id);

// Verificar si la compra existe
if (!$compra) {
    $_SESSION['mensaje'] = 'Compra no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Determinar clase CSS según estado
switch ($compra['estado']) {
    case 'pendiente':
        $estado_clase = 'warning';
        break;
    case 'completada':
        $estado_clase = 'success';
        break;
    case 'cancelada':
        $estado_clase = 'danger';
        break;
    default:
        $estado_clase = 'secondary';
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Compra #<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"> <i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active">Detalle de Compra</li>
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
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <i class="fas fa-shopping-cart fa-5x text-<?= $estado_clase; ?>"></i>
                        </div>

                        <h3 class="profile-username text-center">
                            Compra #<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <span class="badge badge-<?= $estado_clase; ?>">
                                <?= ucfirst($compra['estado']); ?>
                            </span>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Fecha</b>
                                <span class="float-right">
                                    <?= date('d/m/Y', strtotime($compra['fechacompra'])); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Usuario</b>
                                <span class="float-right">
                                    <?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Total</b>
                                <span class="float-right">
                                    <?= number_format($compra['totalcompra'], 2); ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <?php if ($compra['estado'] == 'pendiente'): ?>
                                <button class="btn btn-success btn-completar" data-id="<?= $compra['idcompra']; ?>">
                                    <i class="fas fa-check"></i> Completar
                                </button>
                                <button class="btn btn-danger btn-cancelar" data-id="<?= $compra['idcompra']; ?>">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            <?php endif; ?>
                            <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de observaciones -->
                <?php if (!empty($compra['observaciones'])): ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Observaciones</h3>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($compra['observaciones']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Productos Comprados</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">P. Unitario</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compra['detalles'] as $index => $detalle): ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_codigo'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= $detalle['cantidad']; ?></td>
                                            <td class="text-right"><?= number_format($detalle['preciocompra'], 2); ?></td>
                                            <td class="text-right"><?= number_format($detalle['cantidad'] * $detalle['preciocompra'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                        <td class="text-right"><strong><?= number_format($compra['totalcompra'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar botón de completar compra
        const btnCompletar = document.querySelector('.btn-completar');
        if (btnCompletar) {
            btnCompletar.addEventListener('click', function() {
                const compraId = this.dataset.id;
                confirmarAccion(compraId, 'completar');
            });
        }

        // Manejar botón de cancelar compra
        const btnCancelar = document.querySelector('.btn-cancelar');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                const compraId = this.dataset.id;
                confirmarAccion(compraId, 'cancelar');
            });
        }

        function confirmarAccion(id, accion) {
            const titulo = accion === 'completar' ?
                '¿Marcar compra como completada?' :
                '¿Cancelar esta compra?';

            const texto = accion === 'completar' ?
                'La compra se marcará como finalizada y no podrá ser modificada.' :
                'La compra será cancelada y el stock de productos será revertido.';

            const confirmButtonText = accion === 'completar' ? 'Sí, completar' : 'Sí, cancelar';
            const confirmButtonColor = accion === 'completar' ? '#28a745' : '#dc3545';

            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= $URL; ?>controllers/compras/cambiar_estado_compra.php?id=${id}&accion=${accion}&csrf_token=<?= generateCSRFToken(); ?>`;
                }
            });
        }
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'compras')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
$module_scripts = ['compras/index-compras'];
include_once '../layouts/header.php';

$controller = new CompraController();
$esAdmin = $authService->esAdministrador($idusuario);
$compras = $esAdmin ? $controller->index() : $controller->obtenerPorUsuario($idusuario);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Compras</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Compras</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Historial de Compras</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/compras/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Compra
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaCompras" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($compras as $compra) :
                                        $estado_actual = $compra['estado'];
                                        $clase_badge = '';
                                        $texto_estado = '';

                                        switch ($estado_actual) {
                                            case 'pendiente':
                                                $clase_badge = 'badge-warning';
                                                $texto_estado = 'Pendiente';
                                                break;
                                            case 'completada':
                                                $clase_badge = 'badge-success';
                                                $texto_estado = 'Completada';
                                                break;
                                            case 'cancelada':
                                                $clase_badge = 'badge-danger';
                                                $texto_estado = 'Cancelada';
                                                break;
                                            default:
                                                $clase_badge = 'badge-secondary';
                                                $texto_estado = 'Desconocido';
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td>COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= date('d/m/Y', strtotime($compra['fechacompra'])); ?></td>
                                            <td><?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= number_format($compra['totalcompra'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_badge; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/compras/show.php?id=<?= $compra['idcompra']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($estado_actual == 'pendiente' && $esAdmin): ?>
                                                        <button type="button" class="btn btn-success btn-sm btn-cambiar-estado"
                                                            data-id="<?= $compra['idcompra']; ?>"
                                                            data-accion="completar"
                                                            data-titulo="COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>

                                                        <button type="button" class="btn btn-danger btn-sm btn-cambiar-estado"
                                                            data-id="<?= $compra['idcompra']; ?>"
                                                            data-accion="cancelar"
                                                            data-titulo="COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?>">
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

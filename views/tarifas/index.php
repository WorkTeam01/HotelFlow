<?php
require_once __DIR__ . '/../../controllers/tarifas/TarifaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'tarifas')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

$controller = new TarifaController();
$tarifas = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Tarifas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Tarifas</li>
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
                        <h3 class="card-title">Listado de Tarifas</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/tarifas/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Tarifa
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaTarifas" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Tipo Habitación</th>
                                        <th>Tipo Estancia</th>
                                        <th>Duración</th>
                                        <th>Precio</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($tarifas as $tarifa) :
                                        $estado_actual = $tarifa['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-ban' : 'fa-check';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Tarifa?' : '¿Activar Tarifa?';
                                        $texto_alerta = $estado_actual == 1 ? 'La tarifa no estará disponible para asignación.' : 'La tarifa estará disponible para asignación.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($tarifa['tipo_habitacion']); ?></td>
                                            <td><?= ucfirst(htmlspecialchars($tarifa['tipo_estancia'])); ?></td>
                                            <td class="text-center">
                                                <?= $tarifa['duracion'] . ' ' . ($tarifa['tipo_estancia'] == 'horas' ? 'horas' : 'días') ?>
                                            </td>
                                            <td class="text-right"><?= number_format($tarifa['precio'], 2); ?></td>
                                            <td><?= htmlspecialchars($tarifa['descripcion'] ?? 'N/A'); ?></td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/tarifas/show.php?id=<?= $tarifa['idtarifa']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/tarifas/update.php?id=<?= $tarifa['idtarifa']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $tarifa['idtarifa']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($tarifa['tipo_habitacion'] . ' (' . $tarifa['duracion'] . ' ' . $tarifa['tipo_estancia'] . ')'); ?>">
                                                        <i class="fas <?= $icono_boton_estado; ?>"></i>
                                                    </button>
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

<script src="<?= $URL; ?>public/js/modules/tarifas/index-tarifas.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
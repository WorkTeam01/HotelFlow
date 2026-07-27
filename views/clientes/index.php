<?php
require_once __DIR__ . '/../../controllers/personas/PersonaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar permisos para el módulo de personas
if (!($authService->puedeAccederModulo($idusuario, 'clientes'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

$skip_select2 = true;
$skip_chartjs = true;
include_once '../layouts/header.php';

$controller = new PersonaController();
$personas = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Clientes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
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
                        <h3 class="card-title">Listado de Clientes</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/clientes/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Cliente
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaPersonas" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Nombre Completo</th>
                                        <th>Tipo Documento</th>
                                        <th>Número Documento</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($personas as $persona) :
                                        $estado_actual = $persona['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-user-slash' : 'fa-user-check';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Cliente?' : '¿Activar Cliente?';
                                        $texto_alerta = $estado_actual == 1 ? 'El cliente no podrá realizar reservas.' : 'El cliente podrá realizar reservas nuevamente.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellidopaterno'] . ' ' . ($persona['apellidomaterno'] ?? '')); ?></td>
                                            <td><?= htmlspecialchars($persona['tipodocumento']); ?></td>
                                            <td><?= htmlspecialchars($persona['numdocumento']); ?></td>
                                            <td><?= htmlspecialchars($persona['email'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($persona['telefono'] ?? 'N/A'); ?></td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/clientes/show.php?id=<?= $persona['idpersona']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/clientes/update.php?id=<?= $persona['idpersona']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $persona['idpersona']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($persona['nombre']); ?>">
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

<script src="<?= $URL; ?>public/js/modules/clientes/index-personas.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
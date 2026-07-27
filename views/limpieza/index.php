<?php
// Verificar permisos antes de incluir el encabezado
require_once __DIR__ . '/../../controllers/limpieza/AsignacionLimpiezaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo ANTES de incluir el header
if (!($auth->puedeAccederModulo($idusuario, 'limpieza'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
include_once '../layouts/header.php';

// En el controlador que carga la vista index.php
$controller = new AsignacionLimpiezaController();
$asignaciones = $controller->index();
$habitaciones = $controller->getHabitaciones();
$usuarios = $controller->getUsuariosLimpieza();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Asignaciones de Limpieza</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Asignaciones</li>
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
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $estadisticas['total']; ?></h3>
                        <p>Asignaciones Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-broom"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $estadisticas['pendientes']; ?></h3>
                        <p>Pendientes Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $estadisticas['enprogreso']; ?></h3>
                        <p>En Progreso Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $estadisticas['completadas']; ?></h3>
                        <p>Completadas Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= $estadisticas['verificadas']; ?></h3>
                        <p>Verificadas Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $estadisticas['hoy']; ?></h3>
                        <p>Programadas Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de asignaciones -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Asignaciones de Limpieza</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" id="btnNuevaAsignacion">
                                <i class="fas fa-plus"></i> Nueva Asignación
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaAsignaciones" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 20%">Habitación</th>
                                        <th style="width: 20%">Responsable</th>
                                        <th style="width: 15%">Fecha</th>
                                        <th style="width: 10%">Hora</th>
                                        <th style="width: 15%">Estado</th>
                                        <th style="width: 15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($asignaciones as $asignacion) :
                                        $estado = $asignacion['estado'];
                                        $clase_estado = '';
                                        $texto_estado = '';

                                        switch ($estado) {
                                            case 'pendiente':
                                                $clase_estado = 'badge-warning';
                                                $texto_estado = 'Pendiente';
                                                break;
                                            case 'enprogreso':
                                                $clase_estado = 'badge-primary';
                                                $texto_estado = 'En Progreso';
                                                break;
                                            case 'completada':
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'Completada';
                                                break;
                                            case 'verificada':
                                                $clase_estado = 'badge-secondary';
                                                $texto_estado = 'Verificada';
                                                break;
                                            default:
                                                $clase_estado = 'badge-light';
                                                $texto_estado = $estado;
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($asignacion['numero_habitacion'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($asignacion['nombre_usuario'] ?? 'N/A'); ?></td>
                                            <td><?= date('d/m/Y', strtotime($asignacion['fecha'])); ?></td>
                                            <td><?= date('H:i', strtotime($asignacion['hora'])); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <?php if ($asignacion['estado'] !== 'verificada'): ?>
                                                        <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                            data-id="<?= $asignacion['idasignacion']; ?>"
                                                            data-usuario="<?= $asignacion['idusuario']; ?>"
                                                            data-habitacion="<?= $asignacion['idhabitacion']; ?>"
                                                            data-fecha="<?= $asignacion['fecha']; ?>"
                                                            data-hora="<?= $asignacion['hora']; ?>"
                                                            data-estado="<?= $asignacion['estado']; ?>"
                                                            data-observaciones="<?= htmlspecialchars($asignacion['observaciones'] ?? ''); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-warning btn-sm" disabled title="Las asignaciones verificadas no se pueden editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($estado == 'pendiente'): ?>
                                                        <button type="button" class="btn btn-primary btn-sm cambiar-estado"
                                                            data-id="<?= $asignacion['idasignacion']; ?>"
                                                            data-estado="enprogreso"
                                                            data-toggle="tooltip"
                                                            title="Marcar en progreso">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($estado == 'enprogreso'): ?>
                                                        <button type="button" class="btn btn-success btn-sm cambiar-estado"
                                                            data-id="<?= $asignacion['idasignacion']; ?>"
                                                            data-estado="completada"
                                                            data-toggle="tooltip"
                                                            title="Marcar como completada">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($estado == 'completada'): ?>
                                                        <button type="button" class="btn btn-secondary btn-sm cambiar-estado"
                                                            data-id="<?= $asignacion['idasignacion']; ?>"
                                                            data-estado="verificada"
                                                            data-toggle="tooltip"
                                                            title="Verificar limpieza">
                                                            <i class="fas fa-clipboard-check"></i>
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

<!-- Modal para Asignación de Limpieza -->
<div class="modal fade" id="modalAsignacion" tabindex="-1" role="dialog" aria-labelledby="modalAsignacionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalAsignacionHeader">
                <h5 class="modal-title" id="modalAsignacionLabel">Gestión de Asignación de Limpieza</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAsignacion" method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                <div class="modal-body">
                    <input type="hidden" id="asignacionAction" name="action" value="create">
                    <input type="hidden" id="idasignacion" name="idasignacion" value="">

                    <div class="form-group">
                        <label for="idhabitacion">Habitación <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="idhabitacion" name="idhabitacion" required>
                            <option value="">Seleccione una habitación</option>
                            <?php foreach ($habitaciones as $habitacion): ?>
                                <option value="<?= $habitacion['id_habitacion']; ?>">
                                    <?= htmlspecialchars($habitacion['numero'] . ' - ' . $habitacion['tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="idusuario">Responsable <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="idusuario" name="idusuario" required>
                            <option value="">Seleccione un responsable</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['idusuario']; ?>">
                                    <?= htmlspecialchars($usuario['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha" name="fecha" required>
                    </div>

                    <div class="form-group">
                        <label for="hora">Hora <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="hora" name="hora" required>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="estado" name="estado" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="enprogreso">En Progreso</option>
                            <option value="completada">Completada</option>
                            <option value="verificada">Verificada</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarAsignacion">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= $URL; ?>public/js/modules/limpieza/index-limpieza.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
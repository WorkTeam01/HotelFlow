<?php
require_once __DIR__ . '/../../controllers/tipohabitaciones/TipoHabitacionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar permisos para el módulo de tipos de habitación
if (!$authService->tieneAccesoCritico($idusuario, 'tipos_habitacion')) {
    $_SESSION['mensaje'] = 'No tiene permisos de administrador.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

include_once '../layouts/header.php';

$controller = new TipoHabitacionController();
$tiposHabitacion = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Tipos de Habitación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Tipos de Habitación</li>
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
                        <h3 class="card-title">Listado de Tipos de Habitación</h3>
                        <div class="card-tools">
                            <a href="create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Tipo
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaTiposHabitacion" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Capacidad Máxima</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($tiposHabitacion as $tipo) :
                                        $estado_actual = $tipo['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-toggle-off' : 'fa-toggle-on';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Tipo?' : '¿Activar Tipo?';
                                        $texto_alerta = $estado_actual == 1 ? 'Este tipo de habitación no estará disponible para asignación.' : 'El tipo de habitación estará disponible nuevamente.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($tipo['nombre']); ?></td>
                                            <td><?= !empty($tipo['descripcion']) ? htmlspecialchars($tipo['descripcion']) : 'N/A'; ?></td>
                                            <td class="text-center"><?= $tipo['capacidad_maxima']; ?></td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/tipohabitacion/show.php?id=<?= $tipo['id_tipo']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/tipohabitacion/update.php?id=<?= $tipo['id_tipo']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $tipo['id_tipo']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($tipo['nombre']); ?>">
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

<script src="<?= $URL; ?>public/js/modules/tipohabitacion/index_tipohabitaciones.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

        botonesCambiarEstado.forEach(boton => {
            boton.addEventListener('click', function() {
                const tipoId = this.dataset.id;
                const estadoActual = this.dataset.estado;
                const nombreTipo = this.dataset.nombre;

                const tituloAlerta = estadoActual == 1 ? `¿Desactivar ${nombreTipo}?` : `¿Activar ${nombreTipo}?`;
                const textoAlerta = estadoActual == 1 ? 'Este tipo de habitación no estará disponible para asignación.' : 'El tipo de habitación estará disponible nuevamente.';
                const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                const cancelButtonText = 'Cancelar';

                Swal.fire({
                    title: tituloAlerta,
                    text: textoAlerta,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: cancelButtonText
                }).then((result) => {
                    if (result.isConfirmed) {
                        const baseUrl = '<?= $URL; ?>';
                        window.location.href = `${baseUrl}controllers/tipohabitaciones/desactivar_tipo_habitacion.php?id=${tipoId}&estado=${estadoActual}`;
                    }
                });
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
<?php
require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!($authService->puedeAccederModulo($idusuario, 'equipajes'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Agregar los estilos específicos para este módulo
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];

// Incluir el encabezado
$skip_chartjs = true;
include_once '../layouts/header.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de equipaje no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del equipaje
$controller = new AlmacenamientoEquipajeController();
$datos = $controller->editar($id);

// Verificar si se obtuvieron los datos correctamente
if (!$datos) {
    $_SESSION['mensaje'] = 'Equipaje no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$equipaje = $datos['equipaje'];
$clientes = $datos['clientes'];
$precios_equipaje = $datos['precios_equipaje'];

// No permitir editar equipajes retirados
if ($equipaje['estado'] === 'retirado') {
    $_SESSION['mensaje'] = 'No se puede editar un equipaje que ya ha sido retirado';
    $_SESSION['icono'] = 'warning';
    header('Location: show.php?id=' . $id);
    exit;
}

// Determinar clase CSS y texto para el estado
$clase_estado = '';
$texto_estado = '';
$icono_estado = '';

switch ($equipaje['estado']) {
    case 'almacenado':
        $clase_estado = 'badge-warning';
        $texto_estado = 'Almacenado';
        $icono_estado = 'fa-clock';
        break;
    case 'perdido':
        $clase_estado = 'badge-danger';
        $texto_estado = 'Perdido';
        $icono_estado = 'fa-exclamation-triangle';
        break;
    case 'dañado':
        $clase_estado = 'badge-dark';
        $texto_estado = 'Dañado';
        $icono_estado = 'fa-times-circle';
        break;
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Editar Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-warning equipaje-card estado-<?= $equipaje['estado']; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> Formulario de Edición de Equipaje</h3>
                    </div>
                    <form id="formActualizarEquipaje" action="<?= $URL; ?>controllers/almacenamiento-equipaje/actualizar_equipaje.php" method="POST">
                        <input type="hidden" name="idalmacen" value="<?= $equipaje['idalmacen']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <!-- Estado actual del equipaje -->
                            <div class="mb-3 text-center">
                                <span class="badge badge-estado badge-<?= $equipaje['estado']; ?>">
                                    <i class="fas <?= $icono_estado; ?>"></i> Estado actual: <?= $texto_estado; ?>
                                </span>
                            </div>

                            <div class="row">
                                <!-- Cliente -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcliente" class="required-field">Cliente</label>
                                        <select class="form-control select2" id="idcliente" name="idcliente" required>
                                            <option value="">Seleccione un cliente</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <option value="<?= $cliente['idpersona']; ?>"
                                                    <?= $cliente['idpersona'] == $equipaje['idcliente'] ? 'selected' : ''; ?>
                                                    data-tipodoc="<?= htmlspecialchars($cliente['tipodocumento']); ?>"
                                                    data-numdoc="<?= htmlspecialchars($cliente['numdocumento']); ?>"
                                                    data-telefono="<?= htmlspecialchars($cliente['telefono']); ?>">
                                                    <?= htmlspecialchars($cliente['nombre_completo']); ?> -
                                                    <?= htmlspecialchars($cliente['tipodocumento']); ?>:
                                                    <?= htmlspecialchars($cliente['numdocumento']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tipo/Precio de Equipaje -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idpequipaje" class="required-field">Tipo de Equipaje</label>
                                        <select class="form-control select2" id="idpequipaje" name="idpequipaje" required>
                                            <option value="">Seleccione un tipo de equipaje</option>
                                            <?php foreach ($precios_equipaje as $precio): ?>
                                                <option value="<?= $precio['idprecioe']; ?>"
                                                    <?= $precio['idprecioe'] == $equipaje['idpequipaje'] ? 'selected' : ''; ?>
                                                    data-precio="<?= $precio['precio']; ?>">
                                                    <?= htmlspecialchars($precio['tamano']); ?> -
                                                    (Bs. <?= number_format($precio['precio'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Cantidad de Piezas -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cantidad_piezas">Cantidad de Piezas</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="cantidad_piezas" name="cantidad_piezas"
                                                value="<?= $equipaje['cantidad_piezas']; ?>" min="1" max="50">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Código de Ticket -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="codigo_ticket">Código de Ticket</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="codigo_ticket" name="codigo_ticket"
                                                value="<?= htmlspecialchars($equipaje['codigo_ticket']); ?>" readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-ticket-alt"></i></span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">El código de ticket no se puede modificar</small>
                                    </div>
                                </div>

                                <!-- Fecha de Entrada -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechaentrada">Fecha y Hora de Entrada</label>
                                        <div class="input-group">
                                            <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                                                value="<?= date('Y-m-d\TH:i', strtotime($equipaje['fechaentrada'])); ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Monto -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="monto" class="required-field">Monto</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs.</span>
                                            </div>
                                            <input type="number" class="form-control" id="monto" name="monto"
                                                value="<?= $equipaje['monto']; ?>" step="0.01" min="0" required readonly>
                                        </div>
                                        <small class="form-text text-muted">Calculado automáticamente</small>
                                    </div>
                                </div>
                                <!-- Descripción -->
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="descripcion">Descripción del Equipaje</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            placeholder="Describa el equipaje (color, tipo, características especiales, etc.)"><?= htmlspecialchars($equipaje['descripcion'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado actual -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="almacenado" <?= $equipaje['estado'] == 'almacenado' ? 'selected' : ''; ?>>Almacenado</option>
                                            <option value="perdido" <?= $equipaje['estado'] == 'perdido' ? 'selected' : ''; ?>>Perdido</option>
                                            <option value="dañado" <?= $equipaje['estado'] == 'dañado' ? 'selected' : ''; ?>>Dañado</option>
                                        </select>
                                        <small class="form-text text-muted">No se puede cambiar a "Retirado" desde este formulario</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Para marcar como "Retirado", use la opción correspondiente en la página de detalles.
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Cliente Seleccionado -->
                            <div class="card card-secondary" id="info-cliente">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-user-circle"></i> Información del Cliente</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-info"><i class="fas fa-id-card"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Tipo de Documento</span>
                                                    <span class="info-box-number" id="cliente-tipodoc"><?= htmlspecialchars($equipaje['tipodoc_cliente'] ?? '-'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-primary"><i class="fas fa-passport"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Número de Documento</span>
                                                    <span class="info-box-number" id="cliente-numdoc"><?= htmlspecialchars($equipaje['numdoc_cliente'] ?? '-'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Teléfono</span>
                                                    <span class="info-box-number" id="cliente-telefono"><?= htmlspecialchars($equipaje['telefono_cliente'] ?? '-'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen de cambios -->
                            <div class="card card-success mt-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Resumen de Cambios</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="callout callout-info">
                                                <h5><i class="fas fa-user"></i> Cliente:</h5>
                                                <p id="resumen-cliente"><?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'No seleccionado'); ?></p>
                                            </div>
                                            <div class="callout callout-warning">
                                                <h5><i class="fas fa-luggage-cart"></i> Tipo de Equipaje:</h5>
                                                <p id="resumen-tipo"><?= htmlspecialchars($equipaje['tamano_equipaje'] ?? 'No seleccionado'); ?></p>
                                            </div>
                                            <div class="callout callout-secondary">
                                                <h5><i class="fas fa-box"></i> Cantidad de Piezas:</h5>
                                                <p id="resumen-cantidad"><?= $equipaje['cantidad_piezas']; ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="callout callout-success">
                                                <h5><i class="fas fa-calendar-alt"></i> Fecha de Entrada:</h5>
                                                <p id="resumen-fecha"><?= date('d/m/Y H:i', strtotime($equipaje['fechaentrada'])); ?></p>
                                            </div>
                                            <div class="callout callout-danger">
                                                <h5><i class="fas fa-money-bill"></i> Monto Total:</h5>
                                                <p id="resumen-monto">Bs. <?= number_format($equipaje['monto'], 2); ?></p>
                                            </div>
                                            <div class="callout callout-dark">
                                                <h5><i class="fas fa-tag"></i> Estado:</h5>
                                                <p>
                                                    <span id="resumen-estado" class="badge badge-<?= $equipaje['estado'] == 'almacenado' ? 'warning' : ($equipaje['estado'] == 'perdido' ? 'danger' : 'dark'); ?>">
                                                        <?= ucfirst($equipaje['estado']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="callout callout-default">
                                                <h5><i class="fas fa-align-left"></i> Descripción:</h5>
                                                <p id="resumen-descripcion"><?= htmlspecialchars($equipaje['descripcion'] ?? '-'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del sistema -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-calendar-plus"></i> Fecha de Registro:</strong>
                                                    <p class="text-muted"><?= date('d/m/Y H:i', strtotime($equipaje['fechacreacion'] ?? $equipaje['fechaentrada'])); ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-user"></i> Usuario que Registró:</strong>
                                                    <p class="text-muted"><?= htmlspecialchars($equipaje['nombre_usuario'] ?? 'No disponible'); ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong><i class="fas fa-edit"></i> Última Actualización:</strong>
                                                    <p class="text-muted"><?= isset($equipaje['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($equipaje['fechaactualizacion'])) : 'No ha sido actualizado'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save"></i> Actualizar Equipaje
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/show.php?id=<?= $equipaje['idalmacen']; ?>" class="btn btn-info w-100">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </a>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <div class="col-md-4">
                <!-- Tarjeta de resumen actual -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Información Actual</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="ticket-code">
                                <i class="fas fa-ticket-alt text-primary"></i>
                                <?= htmlspecialchars($equipaje['codigo_ticket']); ?>
                            </div>
                            <p class="text-muted mt-2">Código de identificación único</p>
                        </div>

                        <div class="info-box bg-light">
                            <span class="info-box-icon <?= $equipaje['estado'] == 'almacenado' ? 'bg-warning' : ($equipaje['estado'] == 'perdido' ? 'bg-danger' : 'bg-dark'); ?>">
                                <i class="fas <?= $icono_estado; ?>"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Estado actual</span>
                                <span class="info-box-number"><?= $texto_estado; ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $equipaje['estado'] == 'almacenado' ? 'warning' : ($equipaje['estado'] == 'perdido' ? 'danger' : 'dark'); ?>" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Desde <?= date('d/m/Y H:i', strtotime($equipaje['fechacreacion'] ?? $equipaje['fechaentrada'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card bg-light mt-3">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="fas fa-calendar-day"></i> Tiempo almacenado</h5>

                                <?php
                                // Calcular tiempo almacenado
                                $fecha_entrada = new DateTime($equipaje['fechaentrada']);
                                $fecha_actual = new DateTime();
                                $intervalo = $fecha_entrada->diff($fecha_actual);

                                // Formatear tiempo de almacenamiento
                                $tiempo_almacenado = '';
                                if ($intervalo->days > 0) {
                                    $tiempo_almacenado .= $intervalo->days . ' día(s) ';
                                }
                                if ($intervalo->h > 0) {
                                    $tiempo_almacenado .= $intervalo->h . ' hora(s) ';
                                }
                                if ($intervalo->i > 0) {
                                    $tiempo_almacenado .= $intervalo->i . ' minuto(s)';
                                }
                                if (empty($tiempo_almacenado)) {
                                    $tiempo_almacenado = 'Menos de un minuto';
                                }
                                ?>

                                <p class="lead"><?= $tiempo_almacenado; ?></p>

                                <div class="progress">
                                    <?php
                                    // Mostrar barra de progreso de acuerdo al tiempo almacenado
                                    $horas_totales = $intervalo->days * 24 + $intervalo->h;
                                    $porcentaje = min(100, ($horas_totales / 24) * 100); // Consideramos 24 horas como 100%
                                    $color_barra = $porcentaje < 50 ? 'success' : ($porcentaje < 75 ? 'warning' : 'danger');
                                    ?>
                                    <div class="progress-bar bg-<?= $color_barra; ?>" role="progressbar" style="width: <?= $porcentaje; ?>%" aria-valuenow="<?= $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5>Instrucciones:</h5>
                            <ul>
                                <li>Modifique los campos necesarios</li>
                                <li>El monto se calcula automáticamente según el tipo de equipaje y la cantidad</li>
                                <li>No se puede cambiar el código de ticket</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Para marcar el equipaje como "Retirado", debe hacerlo desde la página de detalles.
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Los cambios quedarán registrados en el historial del equipaje.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<script src="<?= $URL; ?>public/js/modules/almacenamiento-equipaje/update-equipaje.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
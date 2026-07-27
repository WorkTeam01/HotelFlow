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

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
$module_scripts = ['almacenamiento-equipaje/index-equipajes'];
include_once '../layouts/header.php';

$controller = new AlmacenamientoEquipajeController();

// Procesar filtros si existen
$filtros = $controller->procesarFiltros();
$equipajes = $controller->index($filtros);
$estadisticas = $controller->getEstadisticas();

// Obtener clientes para el filtro
$datos_formulario = $controller->crear();
$clientes = $datos_formulario['clientes'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Almacenamiento de Equipaje</li>
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
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-suitcase"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Equipajes hoy</span>
                        <span class="info-box-number"><?= $estadisticas['total_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-warehouse"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Almacenados hoy</span>
                        <span class="info-box-number"><?= $estadisticas['almacenados_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Retirados hoy</span>
                        <span class="info-box-number"><?= $estadisticas['retirados_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ingresos hoy</span>
                        <span class="info-box-number">Bs. <?= number_format($estadisticas['ingresos_hoy'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-secondary collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Filtros de búsqueda</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="">Todos los estados</option>
                                            <option value="almacenado" <?= isset($filtros['estado']) && $filtros['estado'] == 'almacenado' ? 'selected' : ''; ?>>Almacenado</option>
                                            <option value="retirado" <?= isset($filtros['estado']) && $filtros['estado'] == 'retirado' ? 'selected' : ''; ?>>Retirado</option>
                                            <option value="perdido" <?= isset($filtros['estado']) && $filtros['estado'] == 'perdido' ? 'selected' : ''; ?>>Perdido</option>
                                            <option value="dañado" <?= isset($filtros['estado']) && $filtros['estado'] == 'dañado' ? 'selected' : ''; ?>>Dañado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="idcliente">Cliente</label>
                                        <select class="form-control select2" id="idcliente" name="idcliente">
                                            <option value="">Todos los clientes</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <option value="<?= $cliente['idpersona']; ?>"
                                                    <?= isset($filtros['idcliente']) && $filtros['idcliente'] == $cliente['idpersona'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($cliente['nombre_completo']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="fecha_inicio">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                            value="<?= isset($filtros['fecha_inicio']) ? $filtros['fecha_inicio'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="fecha_fin">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                            value="<?= isset($filtros['fecha_fin']) ? $filtros['fecha_fin'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpiar filtros
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de equipajes -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Equipajes registrados</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/almacenamiento-equipaje/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaEquipajes" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">Código Ticket</th>
                                        <th class="text-center">Cliente</th>
                                        <th class="text-center">Piezas</th>
                                        <th class="text-center">Fecha Entrada</th>
                                        <th class="text-center">Monto</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($equipajes as $equipaje) :
                                        $estado_actual = $equipaje['estado'];
                                        $clase_estado = '';
                                        $texto_estado = '';

                                        switch ($estado_actual) {
                                            case 'almacenado':
                                                $clase_estado = 'badge-warning';
                                                $texto_estado = 'Almacenado';
                                                break;
                                            case 'retirado':
                                                $clase_estado = 'badge-success';
                                                $texto_estado = 'Retirado';
                                                break;
                                            case 'perdido':
                                                $clase_estado = 'badge-danger';
                                                $texto_estado = 'Perdido';
                                                break;
                                            case 'dañado':
                                                $clase_estado = 'badge-dark';
                                                $texto_estado = 'Dañado';
                                                break;
                                            default:
                                                $clase_estado = 'badge-secondary';
                                                $texto_estado = 'Desconocido';
                                                break;
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-info"><?= htmlspecialchars($equipaje['codigo_ticket']); ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($equipaje['nombre_cliente'] ?? 'Cliente no disponible'); ?></td>
                                            <td class="text-center"><?= $equipaje['cantidad_piezas']; ?></td>
                                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($equipaje['fechaentrada'])); ?></td>
                                            <td class="text-right">Bs. <?= number_format($equipaje['monto'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <!-- Botón Ver detalles - Siempre visible -->
                                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/show.php?id=<?= $equipaje['idalmacen']; ?>"
                                                        class="btn btn-info btn-sm" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <!-- NUEVO: Botón Imprimir recibo -->
                                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/recibo.php?id=<?= $equipaje['idalmacen']; ?>"
                                                        class="btn btn-primary btn-sm" title="Imprimir recibo" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>

                                                    <?php if ($estado_actual !== 'retirado'): ?>
                                                        <!-- Botón Editar - Solo visible si no está retirado -->
                                                        <a href="<?= $URL; ?>views/almacenamiento-equipaje/update.php?id=<?= $equipaje['idalmacen']; ?>"
                                                            class="btn btn-warning btn-sm" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <!-- Dropdown para cambiar estado - Solo visible si no está retirado -->
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-success btn-sm dropdown-toggle"
                                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                                title="Cambiar estado">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <?php if ($estado_actual !== 'retirado'): ?>
                                                                    <a class="dropdown-item text-success cambiar-estado"
                                                                        href="#"
                                                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=retirado&csrf_token=<?= generateCSRFToken(); ?>"
                                                                        data-accion="Retirado"
                                                                        data-icono="success">
                                                                        <i class="fas fa-check-circle mr-2"></i> Marcar como Retirado
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if ($estado_actual !== 'perdido'): ?>
                                                                    <a class="dropdown-item text-danger cambiar-estado"
                                                                        href="#"
                                                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=perdido&csrf_token=<?= generateCSRFToken(); ?>"
                                                                        data-accion="Perdido"
                                                                        data-icono="error">
                                                                        <i class="fas fa-exclamation-triangle mr-2"></i> Marcar como Perdido
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if ($estado_actual !== 'dañado'): ?>
                                                                    <a class="dropdown-item text-warning cambiar-estado"
                                                                        href="#"
                                                                        data-url="<?= $URL; ?>controllers/almacenamiento-equipaje/cambiar_estado.php?id=<?= $equipaje['idalmacen']; ?>&nuevo_estado=dañado&csrf_token=<?= generateCSRFToken(); ?>"
                                                                        data-accion="Dañado"
                                                                        data-icono="warning">
                                                                        <i class="fas fa-times-circle mr-2"></i> Marcar como Dañado
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

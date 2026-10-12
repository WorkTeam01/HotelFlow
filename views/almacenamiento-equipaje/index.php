<?php
require_once __DIR__ . '/../../controllers/almacenamiento-equipaje/AlmacenamientoEquipajeController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos de acceso al módulo
if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'equipajes')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado después de verificar permisos
$skip_chartjs = true;
$module_styles = ['almacenamiento-equipaje/almacenamiento-equipaje'];
$module_scripts = ['almacenamiento-equipaje/cambiar-estado-equipajes', 'almacenamiento-equipaje/index-equipajes'];
include_once '../layouts/header.php';

$controller = new AlmacenamientoEquipajeController();

// Procesar filtros si existen
$filtros = $controller->procesarFiltros();
$equipajes = $controller->index($filtros);
$estadisticas = $controller->getEstadisticas();

$clientes = $controller->clientesParaFiltro();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home" aria-hidden="true"></i> Inicio</a></li>
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
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-warehouse" aria-hidden="true"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">En almacén</span>
                        <span class="info-box-number"><?= $estadisticas['en_almacen']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Vencidos</span>
                        <span class="info-box-number"><?= $estadisticas['vencidos']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle" aria-hidden="true"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Retirados hoy</span>
                        <span class="info-box-number"><?= $estadisticas['retirados_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-dollar-sign" aria-hidden="true"></i></span>
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
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Mostrar u ocultar filtros">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="">Todos los estados</option>
                                            <?php foreach (AlmacenamientoEquipajeController::estadosFiltro() as $valor => $ui_filtro): ?>
                                                <option value="<?= $valor; ?>" <?= isset($filtros['estado']) && $filtros['estado'] == $valor ? 'selected' : ''; ?>><?= $ui_filtro['label']; ?></option>
                                            <?php endforeach; ?>
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
                                            value="<?= htmlspecialchars($filtros['fecha_inicio'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="fecha_fin">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                            value="<?= htmlspecialchars($filtros['fecha_fin'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="d-none d-md-block" aria-hidden="true">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search" aria-hidden="true"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/" class="btn btn-secondary">
                                        <i class="fas fa-times" aria-hidden="true"></i> Limpiar filtros
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
                                <i class="fas fa-plus" aria-hidden="true"></i> Nuevo Registro
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer tabla de equipajes">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive equipaje-tabla">
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
                                        $ui = $equipaje['estado_ui'];
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
                                                <span class="badge <?= $ui['badge']; ?>"><?= $ui['label']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $ticket = htmlspecialchars($equipaje['codigo_ticket']);
                                                $acciones_estado = AlmacenamientoEquipajeController::accionesEstado($equipaje['estado']);
                                                ?>
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/almacenamiento-equipaje/show.php?id=<?= $equipaje['idalmacen']; ?>"
                                                        class="btn btn-touch btn-info btn-sm" title="Ver detalles" aria-label="Ver detalles del ticket <?= $ticket; ?>">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-touch btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split"
                                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                        aria-label="Más acciones del ticket <?= $ticket; ?>">
                                                        <span class="sr-only">Más acciones</span>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <?php if (AlmacenamientoEquipajeController::esEditable($equipaje['estado'])): ?>
                                                            <a class="dropdown-item" href="<?= $URL; ?>views/almacenamiento-equipaje/update.php?id=<?= $equipaje['idalmacen']; ?>">
                                                                <i class="fas fa-edit mr-2 text-warning" aria-hidden="true"></i> Editar
                                                            </a>
                                                        <?php endif; ?>
                                                        <a class="dropdown-item" href="<?= $URL; ?>views/almacenamiento-equipaje/recibo.php?id=<?= $equipaje['idalmacen']; ?>" target="_blank" rel="noopener">
                                                            <i class="fas fa-file-pdf mr-2 text-primary" aria-hidden="true"></i> Imprimir recibo
                                                        </a>
                                                        <?php if ($acciones_estado): ?>
                                                            <div class="dropdown-divider"></div>
                                                            <?php foreach ($acciones_estado as $destino => $accion): ?>
                                                                <button type="button" class="dropdown-item cambiar-estado"
                                                                    data-id="<?= $equipaje['idalmacen']; ?>" data-estado="<?= $destino; ?>">
                                                                    <i class="fas fa-<?= $accion['icono']; ?> mr-2 text-<?= $accion['clase']; ?>" aria-hidden="true"></i><?= $accion['accion_label']; ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
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
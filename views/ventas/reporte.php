<?php
$skip_select2 = true;
$skip_chartjs = true;
$skip_datatables = true;
include_once __DIR__ . '/../layouts/header.php';

$total_periodo = 0;
foreach ($ventas as $venta) {
    if ($venta['estado'] == 1) {
        $total_periodo += $venta['totalventa'];
    }
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Reporte de Ventas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas/"> <i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Reporte</li>
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
                        <h3 class="card-title">Filtrar por Rango de Fechas</h3>
                    </div>
                    <div class="card-body">
                        <form method="get" action="<?= $URL; ?>controllers/ventas/reporte_ventas.php" class="form-inline">
                            <div class="form-group mr-2">
                                <label for="fecha_inicio" class="mr-2">Desde</label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio); ?>">
                            </div>
                            <div class="form-group mr-2">
                                <label for="fecha_fin" class="mr-2">Hasta</label>
                                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-outline card-success">
                        <h3 class="card-title">Resultados (<?= count($ventas); ?> ventas · Total activo: <?= number_format($total_periodo, 2); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Usuario</th>
                                        <th>Total</th>
                                        <th>Método Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($ventas as $venta) : ?>
                                        <?php
                                        $estado = $venta['estado'];
                                        $clase_estado = $estado ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado ? 'Activa' : 'Anulada';
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td>VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= date('d/m/Y', strtotime($venta['fechaventa'])); ?></td>
                                            <td><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></td>
                                            <td><?= htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= number_format($venta['totalventa'], 2); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($venta['metodopago']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= $URL; ?>views/ventas/show.php?id=<?= $venta['idventa']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($ventas)) : ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No hay ventas en el rango seleccionado.</td>
                                        </tr>
                                    <?php endif; ?>
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
include_once __DIR__ . '/../layouts/mensajes.php';
include_once __DIR__ . '/../layouts/footer.php';
?>
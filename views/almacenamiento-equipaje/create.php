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

$controller = new AlmacenamientoEquipajeController();
$datos = $controller->crear();
$clientes = $datos['clientes'];
$precios_equipaje = $datos['precios_equipaje'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Almacenamiento de Equipaje</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/almacenamiento-equipaje"><i class="fas fa-suitcase"></i> Almacenamiento de Equipaje</a></li>
                    <li class="breadcrumb-item active">Registrar Equipaje</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Alerta de información -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-info"></i> Información Importante</h5>
                    Complete todos los campos marcados con <span class="text-danger">*</span> para registrar un nuevo almacenamiento de equipaje.
                    El monto se calculará automáticamente según el tipo de equipaje y la cantidad de piezas.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> Formulario de Registro de Equipaje</h3>
                    </div>
                    <form id="formRegistroEquipaje" action="<?= $URL; ?>controllers/almacenamiento-equipaje/crear_equipaje.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                        <div class="card-body">
                            <div class="row">
                                <!-- Cliente -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcliente" class="required-field">Cliente</label>
                                        <select class="form-control select2" id="idcliente" name="idcliente" required>
                                            <option value="">Seleccione un cliente</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <option value="<?= $cliente['idpersona']; ?>"
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
                                                <option value="<?= $precio['idprecioe']; ?>" data-precio="<?= $precio['precio']; ?>">
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
                                                value="1" min="1" max="50">
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
                                        <input type="text" class="form-control" id="codigo_ticket" name="codigo_ticket"
                                            readonly>
                                        <small class="form-text text-muted">Código generado automáticamente</small>
                                    </div>
                                </div>

                                <!-- Fecha de Entrada -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechaentrada">Fecha y Hora de Entrada</label>
                                        <div class="input-group">
                                            <input type="datetime-local" class="form-control" id="fechaentrada" name="fechaentrada"
                                                value="<?= date('Y-m-d\TH:i'); ?>">
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
                                                step="0.01" min="0" required readonly>
                                        </div>
                                        <small class="form-text text-muted">Calculado automáticamente</small>
                                    </div>
                                </div>
                                <!-- Descripción -->
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="descripcion">Descripción del Equipaje</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            placeholder="Describa el equipaje (color, tipo, características especiales, etc.)"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Cliente Seleccionado -->
                            <div class="card card-secondary" id="info-cliente" style="display: none;">
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
                                                    <span class="info-box-number" id="cliente-tipodoc">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-primary"><i class="fas fa-passport"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Número de Documento</span>
                                                    <span class="info-box-number" id="cliente-numdoc">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Teléfono</span>
                                                    <span class="info-box-number" id="cliente-telefono">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumen de la Operación -->
                            <div class="card card-success mt-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Resumen de la Operación</h3>
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
                                                <p id="resumen-cliente">No seleccionado</p>
                                            </div>
                                            <div class="callout callout-warning">
                                                <h5><i class="fas fa-luggage-cart"></i> Tipo de Equipaje:</h5>
                                                <p id="resumen-tipo">No seleccionado</p>
                                            </div>
                                            <div class="callout callout-secondary">
                                                <h5><i class="fas fa-box"></i> Cantidad de Piezas:</h5>
                                                <p id="resumen-cantidad">1</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="callout callout-success">
                                                <h5><i class="fas fa-calendar-alt"></i> Fecha de Entrada:</h5>
                                                <p id="resumen-fecha"><?= date('d/m/Y H:i'); ?></p>
                                            </div>
                                            <div class="callout callout-danger">
                                                <h5><i class="fas fa-money-bill"></i> Monto Total:</h5>
                                                <p id="resumen-monto">Bs. 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="callout callout-default">
                                                <h5><i class="fas fa-align-left"></i> Descripción:</h5>
                                                <p id="resumen-descripcion">-</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Equipaje
                                    </button>
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
            </div>

            <div class="col-md-4">
                <!-- Tarjeta de ayuda -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Ayuda y Tips</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Información:</h5>
                            <ul>
                                <li>Seleccione un <strong>cliente</strong> registrado en el sistema</li>
                                <li>El <strong>monto</strong> se calcula automáticamente según el tipo de equipaje y la cantidad</li>
                                <li>El <strong>código de ticket</strong> se genera automáticamente, pero puede cambiarlo</li>
                            </ul>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Recordatorio:</h5>
                            <p>Informe al cliente que debe conservar el ticket para retirar su equipaje posteriormente.</p>
                        </div>

                        <div class="alert alert-light">
                            <h5><i class="fas fa-question-circle"></i> ¿Necesitas ayuda?</h5>
                            <p class="text-muted">Si tienes dudas sobre cómo usar este formulario, contacta al administrador del sistema.</p>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de precios -->
                <div class="card card-outline card-success collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tags"></i> Tarifas Actuales</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tamaño</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($precios_equipaje as $precio): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-suitcase<?= strtolower($precio['tamano']) === 'grande' ? '-rolling' : (strtolower($precio['tamano']) === 'pequeño' ? '' : '-alt'); ?> text-primary"></i>
                                            <?= htmlspecialchars($precio['tamano']); ?>
                                        </td>
                                        <td><?= htmlspecialchars($precio['descripcion']); ?></td>
                                        <td class="text-right">Bs. <?= number_format($precio['precio'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Instrucciones de impresión -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title"><i class="fas fa-print"></i> Impresión de Ticket</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>Después de registrar el equipaje, podrá imprimir el ticket para el cliente.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> El ticket incluirá:
                            <ul>
                                <li>Código único de identificación</li>
                                <li>Datos del cliente</li>
                                <li>Descripción del equipaje</li>
                                <li>Fecha de entrada</li>
                                <li>Monto pagado</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $URL; ?>public/js/modules/almacenamiento-equipaje/create-equipaje.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
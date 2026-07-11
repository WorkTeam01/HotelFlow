<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../controllers/personas/PersonaController.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!$authService->tieneAccesoModulo($idusuario, 'nueva_venta')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

include_once '../layouts/header.php';

// Obtener datos necesarios
$productoController = new ProductoController();
$productos = $productoController->index();

$personaController = new PersonaController();
$clientes = $personaController->index();
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Registrar Nueva Venta</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Nueva Venta</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Venta</h3>
                    </div>
                    <form action="<?= $URL; ?>controllers/ventas/nueva_venta.php" method="POST" id="form-venta" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()); ?>">
                        <input type="hidden" name="idusuario" value="<?= $_SESSION['usuario_id'] ?>">
                        <input type="hidden" name="totalventa" id="totalventa-hidden" value="0">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Vendedor</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario actual') ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="idcliente">Cliente <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="buscar-cliente" placeholder="Buscar por número de documento o nombre" autocomplete="off">
                                        <input type="hidden" id="idcliente" name="idcliente" required>
                                        <div class="invalid-feedback">Seleccione un cliente</div>
                                        <div id="sugerencias-clientes" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto;"></div>
                                    </div>
                                    <div id="info-cliente-seleccionado" class="mt-2" style="display: none;">
                                        <strong>Cliente seleccionado:</strong>
                                        <span id="nombre-cliente"></span>
                                        <button type="button" class="btn btn-sm btn-link text-danger" id="quitar-cliente">
                                            <i class="fas fa-times"></i> Quitar
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fechaventa">Fecha <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fechaventa" name="fechaventa" value="<?= date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Ingrese una fecha válida</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="metodopago">Método de Pago <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="metodopago" name="metodopago" required>
                                            <option value="Efectivo">Efectivo</option>
                                            <option value="QR">QR</option>
                                            <option value="Otros">Otros</option>
                                        </select>
                                        <div class="invalid-feedback">Seleccione un método de pago</div>
                                    </div>
                                </div>

                                <div class="col-md-4" id="pago-recibido-container">
                                    <div class="form-group">
                                        <label for="pagorecibido">Pago Recibido <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="pagorecibido" name="pagorecibido" step="0.01" min="0" value="0.00" required>
                                        <div class="invalid-feedback">Ingrese el monto recibido</div>
                                    </div>
                                </div>

                                <div class="col-md-4" id="cambio-container">
                                    <div class="form-group">
                                        <label for="cambio">Cambio</label>
                                        <input type="text" class="form-control" id="cambio" readonly>
                                        <input type="hidden" name="cambio" id="cambio-hidden">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Detalle de Productos <span class="text-danger">*</span></h5>

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="buscar-producto" placeholder="Ingrese el código del producto">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="btn-buscar-producto">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="30%">Producto</th>
                                            <th width="10%">Código</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="15%">Precio Unitario</th>
                                            <th width="15%">Descuento</th>
                                            <th width="10%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="fila-base" style="display: none;">
                                            <td>
                                                <input type="hidden" class="form-control idproducto" name="productos[]">
                                                <input type="text" class="form-control nombre-producto" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control codigo-producto" readonly>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control cantidad" name="cantidades[]" min="1" value="1" required>
                                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                                                <small class="text-muted stock-disponible">Disponible: 0</small>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" class="form-control precio" name="precios[]" step="0.01" min="0.01" value="0.00" required>
                                                    <div class="invalid-feedback">Ingrese un precio válido</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" class="form-control descuento" name="descuentos[]" step="0.01" min="0" value="0.00">
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <span class="subtotal">0.00</span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Las filas de productos se agregarán dinámicamente aquí -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                                            <td class="text-right"><strong><span id="subtotal-venta">0.00</span></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Descuento Total:</strong></td>
                                            <td class="text-right"><strong><span id="descuento-total">0.00</span></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Total Venta:</strong></td>
                                            <td class="text-right"><strong><span id="total-venta">0.00</span></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Venta
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/ventas/nueva.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts para el formulario de ventas -->
<script>
    // Estas variables son necesarias para el archivo JS
    // Exportamos los datos desde PHP a variables JavaScript globales
    const productosDisponibles = <?= json_encode(array_filter($productos, function ($p) {
                                        return $p['estado'] == 1 && $p['stock'] > 0;
                                    })) ?>;

    const clientesDisponibles = <?= json_encode($clientes) ?>;

    // Variables de configuración y URLs
    const baseUrl = '<?= $URL ?>'; // URL base de la aplicación
</script>

<script src="<?= $URL; ?>public/js/modules/ventas/create-venta.js"></script>

<style>
    .text-danger {
        color: #dc3545 !important;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .invalid-feedback {
        display: none;
        color: #dc3545;
        font-size: 0.8em;
    }

    .is-invalid~.invalid-feedback,
    .is-invalid+.invalid-feedback {
        display: block;
    }

    [style*="display: none"] {
        visibility: hidden;
        position: absolute;
        pointer-events: none;
    }

    /* Estilos para el buscador de clientes */
    #sugerencias-clientes {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    #sugerencias-clientes button {
        text-align: left;
        border: none;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    #sugerencias-clientes button:hover {
        background-color: #f8f9fa;
    }

    #info-cliente-seleccionado {
        background-color: #f8f9fa;
        padding: 0.5rem;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
    }
</style>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
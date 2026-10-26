<?php
/**
 * Partial: tarjeta "Detalle de Productos" del formulario de nueva venta.
 *
 * No requiere variables del include-r. Las filas las agrega venta-detalle.js
 * clonando el <template id="template-fila-producto">.
 *
 * Solo pinta HTML.
 */
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Detalle de Productos</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-touch btn-sm btn-primary" id="btn-abrir-modal-productos" data-toggle="modal" data-target="#modal-productos">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive carrito-scroll" hidden>
            <table class="table table-bordered mb-0" id="tabla-productos">
                <thead class="bg-light">
                    <tr>
                        <th width="27%">Producto</th>
                        <th width="15%">Cantidad</th>
                        <th width="22%">Precio Unit. (Bs.)</th>
                        <th width="22%">Descuento (Bs.)</th>
                        <th width="9%">Subtotal (Bs.)</th>
                        <th width="5%"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template id="template-fila-producto">
                        <tr class="fila-producto">
                            <td data-label="Producto">
                                <input type="hidden" class="idproducto" name="productos[]">
                                <strong class="d-block nombre-producto"></strong>
                                <small class="text-muted d-block codigo-producto"></small>
                            </td>
                            <td data-label="Cantidad">
                                <input type="number" class="form-control cantidad" name="cantidades[]" min="1" step="1" value="1" required aria-label="Cantidad">
                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                                <small class="text-muted stock-disponible">Disponible: 0</small>
                            </td>
                            <td data-label="Precio Unit. (Bs.)">
                                <div class="input-group">
                                    <input type="number" class="form-control precio" name="precios[]" step="0.01" min="0.01" value="0.00" readonly required aria-label="Precio unitario">
                                    <div class="invalid-feedback">Ingrese un precio válido</div>
                                </div>
                            </td>
                            <td data-label="Descuento (Bs.)">
                                <div class="input-group">
                                    <input type="number" class="form-control descuento" name="descuentos[]" step="0.01" min="0" value="0.00" aria-label="Descuento">
                                    <div class="invalid-feedback">El descuento supera el subtotal de la línea</div>
                                </div>
                                <small class="form-text text-muted">Total por línea</small>
                            </td>
                            <td data-label="Subtotal (Bs.)" class="text-right">
                                <span class="subtotal">0.00</span>
                            </td>
                            <td data-label="" class="text-center">
                                <button type="button" class="btn btn-touch btn-danger btn-sm btn-eliminar-fila" aria-label="Eliminar fila">
                                    <i class="fas fa-trash"></i> <span class="d-md-none">Eliminar</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div id="carrito-vacio" class="text-center text-muted p-5">
            <i class="fas fa-shopping-basket fa-2x mb-2"></i>
            <p class="mb-0">Aún no agregó productos. Use "Agregar Producto" para iniciar la venta.</p>
        </div>
    </div>
</div>

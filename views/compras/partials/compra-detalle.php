<?php
/**
 * Partial: tarjeta "Detalle de productos" del formulario de nueva compra.
 *
 * No requiere variables del include-r. Las filas las agrega compra-detalle.js
 * clonando el <template id="template-fila-compra">.
 *
 * Solo pinta HTML.
 */
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">Detalle de productos</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-touch btn-sm btn-primary" id="btn-abrir-modal-productos" data-toggle="modal" data-target="#modal-productos">
                <i class="fas fa-plus" aria-hidden="true"></i> Agregar producto
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive comp-detalle-scroll" hidden>
            <table class="table table-bordered mb-0 comp-tabla-apilada" id="tabla-compra">
                <thead class="bg-light">
                    <tr>
                        <th width="36%">Producto</th>
                        <th width="16%">Cantidad</th>
                        <th width="22%">Precio unit. (Bs.)</th>
                        <th width="18%">Subtotal (Bs.)</th>
                        <th width="8%"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template id="template-fila-compra">
                        <tr class="fila-compra">
                            <td data-label="Producto">
                                <input type="hidden" class="idproducto" name="productos[]">
                                <strong class="d-block nombre-producto"></strong>
                                <small class="text-muted d-block codigo-producto"></small>
                                <small class="text-muted d-block stock-actual"></small>
                            </td>
                            <td data-label="Cantidad">
                                <input type="number" class="form-control cantidad" name="cantidades[]" min="1" step="1" value="1" required aria-label="Cantidad">
                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                            </td>
                            <td data-label="Precio unit. (Bs.)">
                                <input type="number" class="form-control precio" name="precios[]" step="0.01" min="0.01" required aria-label="Precio de compra unitario">
                                <div class="invalid-feedback">Ingrese un precio mayor que cero</div>
                            </td>
                            <td data-label="Subtotal (Bs.)" class="text-right">
                                <span class="subtotal">0.00</span>
                            </td>
                            <td data-label="" class="text-center">
                                <button type="button" class="btn btn-touch btn-danger btn-sm btn-eliminar-fila" aria-label="Quitar producto">
                                    <i class="fas fa-trash" aria-hidden="true"></i> <span class="d-md-none">Quitar</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div id="detalle-vacio" class="text-center text-muted p-5">
            <i class="fas fa-boxes fa-2x mb-2" aria-hidden="true"></i>
            <p class="mb-0">Aún no agregó productos. Use «Agregar producto» para iniciar la compra.</p>
        </div>
    </div>
</div>

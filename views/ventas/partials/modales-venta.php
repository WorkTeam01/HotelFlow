<?php
/**
 * Partial: modales de búsqueda de producto y de cliente del formulario de nueva venta.
 *
 * No requiere variables del include-r. Las listas las pinta venta-selectores.js
 * a partir de los atributos data-* del <form>.
 *
 * Solo pinta HTML.
 */
?>
<div class="modal fade" id="modal-productos" tabindex="-1" role="dialog" aria-labelledby="modal-productos-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-productos-titulo">Agregar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="modal-buscar-producto" placeholder="Buscar por nombre o código..." autocomplete="off" aria-label="Buscar producto">
                <div id="lista-productos-modal" class="list-group"></div>
                <p class="text-muted text-center mt-3" data-sin-resultados hidden>No se encontraron productos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-clientes" tabindex="-1" role="dialog" aria-labelledby="modal-clientes-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-clientes-titulo">Buscar Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="modal-buscar-cliente" placeholder="Buscar por número de documento o nombre..." autocomplete="off" aria-label="Buscar cliente">
                <div id="lista-clientes-modal" class="list-group"></div>
                <p class="text-muted text-center mt-3" data-sin-resultados hidden>No se encontraron clientes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Partial: modal de búsqueda de producto del formulario de nueva compra.
 *
 * No requiere variables del include-r. La lista la pinta compra-selector.js a partir
 * del atributo data-productos del <form>.
 *
 * Solo pinta HTML.
 */
?>
<div class="modal fade" id="modal-productos" tabindex="-1" role="dialog" aria-labelledby="modal-productos-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-productos-titulo">Agregar producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="modal-buscar-producto" placeholder="Buscar por nombre o código..." autocomplete="off" aria-label="Buscar producto" aria-describedby="ayuda-buscar-producto">
                <small id="ayuda-buscar-producto" class="form-text text-muted mb-3">Enter con un código exacto agrega el producto directo.</small>
                <div id="lista-productos-modal" class="list-group"></div>
                <p class="text-muted text-center mt-3" data-sin-resultados hidden>No se encontraron productos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

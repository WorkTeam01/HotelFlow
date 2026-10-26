<?php
/**
 * Partial: observaciones y aviso de anulación de la venta.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $venta     de VentaController::ver()
 * - $esActiva  bool: la venta no está anulada
 *
 * Solo pinta HTML.
 */
?>
<!-- Tarjeta de información adicional -->
<?php if (!empty($venta['observacion']) || !$esActiva) : ?>
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">Información Adicional</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($venta['observacion'])) : ?>
                <div class="form-group mb-0">
                    <p class="font-weight-bold mb-1">Observaciones:</p>
                    <div class="p-2 bg-light rounded">
                        <?= nl2br(htmlspecialchars($venta['observacion'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$esActiva) : ?>
                <div class="alert alert-warning <?= !empty($venta['observacion']) ? 'mt-3 mb-0' : ''; ?>">
                    <i class="icon fas fa-info-circle"></i>
                    Esta venta fue anulada el <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>.
                    El stock de los productos fue restaurado.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

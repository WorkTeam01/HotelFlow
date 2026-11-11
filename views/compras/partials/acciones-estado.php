<?php
/**
 * Partial: botones de cambio de estado de una compra (completar / cancelar).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $compra    array con 'idcompra', 'codigo' y 'estado'
 * - $acciones  estados de destino permitidos (CompraController::accionesEstado())
 * - $variante  'fila' (iconos, dentro de un btn-group) o 'detalle' (con texto)
 *
 * Solo pinta HTML; el flujo de confirmación vive en cambiar-estado-compras.js.
 */
$botones = [
    'completada' => ['clase' => 'btn-success', 'icono' => 'fa-check', 'texto' => 'Completar'],
    'cancelada'  => ['clase' => 'btn-danger', 'icono' => 'fa-times', 'texto' => 'Cancelar'],
];
$esDetalle = $variante === 'detalle';

foreach ($acciones as $destino) :
    if (!isset($botones[$destino])) {
        continue;
    }
    $boton = $botones[$destino];
    $etiqueta = $boton['texto'] . ' ' . $compra['codigo'];
?>
    <button type="button"
        class="btn btn-touch <?= $boton['clase']; ?> <?= $esDetalle ? 'btn-block' : 'btn-sm'; ?> cambiar-estado-compra"
        data-id="<?= (int) $compra['idcompra']; ?>"
        data-estado="<?= $destino; ?>"
        data-estado-actual="<?= htmlspecialchars($compra['estado']); ?>"
        data-codigo="<?= htmlspecialchars($compra['codigo']); ?>"
        aria-label="<?= htmlspecialchars($etiqueta); ?>"
        title="<?= htmlspecialchars($etiqueta); ?>" data-toggle="tooltip">
        <i class="fas <?= $boton['icono']; ?>" aria-hidden="true"></i>
        <?php if ($esDetalle) : ?><?= $boton['texto']; ?><?php endif; ?>
    </button>
<?php endforeach; ?>

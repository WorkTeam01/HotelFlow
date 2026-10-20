<?php

/**
 * Partial: botones de cambio de estado de un servicio de baño.
 * Los maneja cambiar-estado-servicios-bano.js (clase .cambiar-estado-servicio).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $servicio   fila de servicio (idservicio)
 * - $acciones   ServicioBanoController::accionesEstado($servicio): estados destino permitidos
 * - $variante   'fila' (botones compactos de icono) o 'detalle' (botones de ancho completo)
 *
 * Solo pinta HTML.
 */
$botones = [
    'finalizado' => ['clase' => 'primary', 'icono' => 'flag-checkered', 'texto' => 'Finalizar servicio'],
    'cancelado' => ['clase' => 'danger', 'icono' => 'times', 'texto' => 'Cancelar servicio'],
];
foreach ($acciones as $destino):
    if (!isset($botones[$destino])) {
        continue;
    }
    $boton = $botones[$destino];
?>
    <button type="button"
        class="btn btn-touch btn-<?= $boton['clase']; ?> <?= $variante === 'fila' ? 'btn-sm' : 'btn-block'; ?> cambiar-estado-servicio"
        data-id="<?= (int)$servicio['idservicio']; ?>"
        data-estado="<?= $destino; ?>"
        aria-label="<?= $boton['texto']; ?>"
        title="<?= $boton['texto']; ?>">
        <i class="fas fa-<?= $boton['icono']; ?>" aria-hidden="true"></i>
        <?php if ($variante === 'detalle'): ?> <?= $boton['texto']; ?><?php endif; ?>
    </button>
<?php endforeach; ?>
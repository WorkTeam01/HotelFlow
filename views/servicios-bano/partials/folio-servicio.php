<?php

/**
 * Partial: ledger de un servicio de baño (solo lectura).
 * Incluido desde la pestaña Folio de views/servicios-bano/show.php.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $folio  líneas de Pago::getByServicioBano() (tipo, concepto, montototal, metodopago,
 *           nombre_usuario, fechacreacion, id_pago, id_pago_reversado)
 *
 * Solo pinta HTML. Las líneas cargo/pago que ya tienen reverso se muestran tachadas.
 */
$reversadas = array_filter(array_column($folio, 'id_pago_reversado'));
$etiquetas = [
    'cargo' => ['badge' => 'warning', 'texto' => 'Cargo'],
    'pago' => ['badge' => 'success', 'texto' => 'Pago'],
    'reverso' => ['badge' => 'secondary', 'texto' => 'Reverso'],
];
?>
<?php if (empty($folio)): ?>
    <div class="text-center text-muted py-4 mb-0">
        <i class="fas fa-receipt fa-3x mb-3" aria-hidden="true"></i>
        <p class="mb-0">Sin movimientos de dinero (baño sin costo).</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Método</th>
                    <th>Registró</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($folio as $linea):
                    $etiqueta = $etiquetas[$linea['tipo']] ?? ['badge' => 'secondary', 'texto' => ucfirst($linea['tipo'])];
                    $tachada = in_array($linea['id_pago'], $reversadas, true) || in_array((string)$linea['id_pago'], $reversadas, true);
                ?>
                    <tr class="<?= $tachada ? 'text-muted' : ''; ?>">
                        <td><?= date('d/m/Y H:i', strtotime($linea['fechacreacion'])); ?></td>
                        <td><span class="badge badge-<?= $etiqueta['badge']; ?>"><?= $etiqueta['texto']; ?></span></td>
                        <td><?= $tachada ? '<s>' : ''; ?><?= htmlspecialchars($linea['concepto'] ?? ''); ?><?= $tachada ? '</s>' : ''; ?></td>
                        <td><?= htmlspecialchars($linea['metodopago'] === 'OTROS' ? 'Otros' : ($linea['metodopago'] ?? '')); ?></td>
                        <td><?= htmlspecialchars($linea['nombre_usuario'] ?? 'N/A'); ?></td>
                        <td class="text-right"><?= $tachada ? '<s>' : ''; ?>Bs. <?= number_format($linea['montototal'], 2); ?><?= $tachada ? '</s>' : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php

/**
 * Partial: historial del equipaje (pestaña Sistema de show.php).
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $timeline  array de AlmacenamientoEquipajeController::armarTimeline()
 *
 * Solo pinta HTML: los eventos, las fechas y las alertas ya vienen resueltos.
 */
?>
<div class="timeline">
    <?php foreach ($timeline as $evento) : ?>
        <?php if ($evento['dia'] !== null) : ?>
            <div class="time-label tl-<?= $evento['tono']; ?>">
                <span><?= $evento['dia']; ?></span>
            </div>
        <?php endif; ?>

        <div class="tl-<?= $evento['tono']; ?>">
            <i class="fas fa-<?= $evento['icono']; ?>" aria-hidden="true"></i>
            <div class="timeline-item">
                <span class="time"><i class="fas fa-clock" aria-hidden="true"></i> <?= $evento['hora']; ?></span>
                <h3 class="timeline-header"><strong><?= htmlspecialchars($evento['titulo']); ?></strong></h3>
                <div class="timeline-body">
                    <p class="<?= ($evento['detalles'] || $evento['alerta']) ? '' : 'mb-0'; ?>">
                        <?= htmlspecialchars($evento['mensaje']); ?>
                        <?php if ($evento['estado_ui']) : ?>
                            <span class="badge <?= $evento['estado_ui']['badge']; ?>"><?= $evento['estado_ui']['label']; ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if ($evento['detalles']) : ?>
                        <div class="callout callout-info">
                            <small>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($evento['detalles'] as $etiqueta => $valor) : ?>
                                        <li><strong><?= htmlspecialchars($etiqueta); ?>:</strong> <?= htmlspecialchars($valor); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </small>
                        </div>
                    <?php endif; ?>
                    <?php if ($evento['alerta']) : ?>
                        <div class="alert alert-<?= $evento['alerta']['clase']; ?> mb-0">
                            <i class="fas fa-<?= $evento['alerta']['icono']; ?>" aria-hidden="true"></i> <?= htmlspecialchars($evento['alerta']['texto']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($evento['pie'] !== '') : ?>
                    <div class="timeline-footer">
                        <span class="text-muted"><?= htmlspecialchars($evento['pie']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="tl-secondary">
        <i class="fas fa-clock" aria-hidden="true"></i>
    </div>
</div>
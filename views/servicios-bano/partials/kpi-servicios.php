<?php

/**
 * Partial: info-box de resumen del listado de servicios de baño.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $estadisticas  ServicioBanoController::getEstadisticas() (total, activos, inactivos,
 *                  en_uso, ingresos_totales, folio_hoy)
 *
 * Solo pinta HTML.
 */
?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shower" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Servicios hoy</span>
                <span class="info-box-number"><?= (int)$estadisticas['total']; ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Baños en uso</span>
                <span class="info-box-number"><?= (int)$estadisticas['en_uso']; ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-dollar-sign" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cobrado hoy</span>
                <span class="info-box-number"><?= number_format($estadisticas['ingresos_totales'], 2); ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-concierge-bell" aria-hidden="true"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cargado a folio hoy</span>
                <span class="info-box-number"><?= number_format($estadisticas['folio_hoy'], 2); ?></span>
            </div>
        </div>
    </div>
</div>
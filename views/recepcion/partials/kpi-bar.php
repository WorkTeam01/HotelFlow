<?php

/**
 * Partial: barra de KPIs del día.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $kpis  array de RecepcionController::kpis() con las claves:
 *   ocupacion_pct, adr, ingresos_dia, llegadas_pendientes, salidas_pendientes, habitaciones_sucias
 *
 * Solo pinta HTML; el cálculo lo hace el modelo (SQL).
 */
?>
<div class="row rec-kpi">
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-chart-pie"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ocupación</span>
                <span class="info-box-number"><?= number_format((float) $kpis['ocupacion_pct'], 1); ?>%</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-tag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">ADR</span>
                <span class="info-box-number">Bs <?= number_format((float) $kpis['adr'], 2); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ingresos del día</span>
                <span class="info-box-number">Bs <?= number_format((float) $kpis['ingresos_dia'], 2); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-sign-in-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Llegadas pendientes</span>
                <span class="info-box-number"><?= (int) $kpis['llegadas_pendientes']; ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-sign-out-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Salidas pendientes</span>
                <span class="info-box-number"><?= (int) $kpis['salidas_pendientes']; ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-broom"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Habitaciones sucias</span>
                <span class="info-box-number"><?= (int) $kpis['habitaciones_sucias']; ?></span>
            </div>
        </div>
    </div>
</div>
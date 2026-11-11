<?php

/**
 * Partial: datos de la compra (responsable, fecha y estado inicial) del formulario de nueva compra.
 *
 * Dependencias que debe definir el include-r ANTES del include:
 * - $datos['responsable']  nombre del usuario en sesión
 * - $datos['esAdmin']      si puede registrar la compra ya completada
 *
 * El servidor ignora cualquier idusuario y reduce el estado a 'pendiente' si el
 * usuario no es administrador; aquí solo se pinta la opción. Solo pinta HTML.
 */
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h2 class="card-title mb-0">Datos de la compra</h2>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="nombre_usuario">Responsable</label>
            <input type="text" class="form-control comp-campo-fijo" id="nombre_usuario"
                value="<?= htmlspecialchars($datos['responsable'] ?? ''); ?>" readonly>
        </div>

        <div class="form-group">
            <label for="fechacompra">Fecha de compra <span class="text-danger" aria-hidden="true">*</span></label>
            <input type="date" class="form-control" id="fechacompra" name="fechacompra"
                value="<?= date('Y-m-d'); ?>" required aria-required="true">
            <div class="invalid-feedback">Ingrese la fecha de la compra</div>
        </div>

        <div class="form-group mb-0">
            <?php if ($datos['esAdmin']) : ?>
                <label for="estado">Estado inicial</label>
                <select class="form-control select2" id="estado" name="estado" aria-describedby="ayuda-estado">
                    <option value="pendiente" selected>Pendiente</option>
                    <option value="completada">Completada</option>
                </select>
                <small id="ayuda-estado" class="form-text text-muted">«Completada» suma el stock al guardar; «Pendiente» lo suma cuando se complete.</small>
            <?php else : ?>
                <span class="d-block font-weight-bold">Estado inicial</span>
                <span class="badge badge-warning" role="status">Pendiente</span>
                <small class="form-text text-muted">Un administrador la completará al recibir la mercadería.</small>
                <input type="hidden" id="estado" name="estado" value="pendiente">
            <?php endif; ?>
        </div>
    </div>
</div>
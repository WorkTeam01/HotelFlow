<!-- Dashboard visual de habitaciones pendientes -->

<!-- Indicadores clave para Personal de Limpieza -->
<div class="row">
    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['pendientes'] ?? 0 ?></h3>
                <p>Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['completadas'] ?? 0 ?></h3>
                <p>Completadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= $stats['asignaciones']['estadisticas']['total'] ?? 0 ?></h3>
                <p>Total Asignaciones</p>
            </div>
            <div class="icon">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
    </div>
</div>


<!-- Dashboard visual de habitaciones pendientes -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-1"></i>
                    Mis Asignaciones Pendientes
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="buscarHabitacionPendiente" class="form-control float-right" placeholder="Buscar habitación">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Fecha actual -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Hoy</h4>
                        <p class="text-muted mb-0"><?= date('d F Y') ?></p>
                    </div>
                    <div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary filtro-estado-pendiente active" data-estado="todos">Todos</button>
                            <button type="button" class="btn btn-outline-warning filtro-estado-pendiente" data-estado="pendiente">Pendientes</button>
                            <button type="button" class="btn btn-outline-info filtro-estado-pendiente" data-estado="enprogreso">En progreso</button>
                        </div>
                    </div>
                </div>

                <!-- Grid de habitaciones asignadas pendientes -->
                <div class="row" id="grid-habitaciones-pendientes">
                    <?php
                    // Filtrar solo las asignaciones pendientes o en progreso
                    $asignaciones_activas = array_filter($stats['asignaciones']['pendientes'], function ($asignacion) {
                        return in_array($asignacion['estado'], ['pendiente', 'enprogreso']);
                    });

                    if (empty($asignaciones_activas)):
                    ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">
                                <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                                ¡No tienes asignaciones pendientes para hoy!
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($asignaciones_activas as $asignacion): ?>
                            <?php
                            $estado = $asignacion['estado'];
                            $clase_estado = '';
                            $texto_estado = '';
                            $icono_estado = '';

                            switch ($estado) {
                                case 'pendiente':
                                    $clase_estado = 'bg-warning';
                                    $texto_estado = 'Pendiente';
                                    $icono_estado = 'fa-clock';
                                    break;
                                case 'enprogreso':
                                    $clase_estado = 'bg-info';
                                    $texto_estado = 'En Progreso';
                                    $icono_estado = 'fa-sync-alt fa-spin';
                                    break;
                            }
                            ?>
                            <div class="col-md-3 col-sm-6 mb-4 habitacion-card-pendiente" data-estado="<?= $estado ?>" data-numero="<?= $asignacion['numero_habitacion'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="card-title">N°: <?= htmlspecialchars($asignacion['numero_habitacion']) ?></h5>
                                        <div class="badge <?= $clase_estado ?> mb-3">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            <?= htmlspecialchars($asignacion['tipo_habitacion']) ?><br>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($asignacion['fecha'])) ?><br>
                                            <i class="far fa-clock mr-1"></i> <?= date('H:i', strtotime($asignacion['hora'])) ?>
                                        </p>
                                    </div>
                                    <div class="card-footer p-0">
                                        <div class="btn-group d-flex">
                                            <?php if ($estado == 'pendiente'): ?>
                                                <button type="button" class="btn btn-info btn-sm btn-block cambiar-estado rounded-0"
                                                    data-id="<?= $asignacion['idasignacion']; ?>"
                                                    data-estado="enprogreso">
                                                    <i class="fas fa-play mr-1"></i> Iniciar
                                                </button>
                                            <?php elseif ($estado == 'enprogreso'): ?>
                                                <button type="button" class="btn btn-success btn-sm btn-block cambiar-estado rounded-0"
                                                    data-id="<?= $asignacion['idasignacion']; ?>"
                                                    data-estado="completada">
                                                    <i class="fas fa-check mr-1"></i> Completar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard visual de habitaciones completadas -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-check-circle mr-1"></i>
                    Asignaciones Completadas Recientemente
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="buscarHabitacionCompletada" class="form-control float-right" placeholder="Buscar habitación">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Grid de habitaciones completadas -->
                <div class="row" id="grid-habitaciones-completadas">
                    <?php
                    // Combinar las asignaciones completadas con las que están completadas en la lista de pendientes
                    $asignaciones_completadas_pendientes = array_filter($stats['asignaciones']['pendientes'], function ($asignacion) {
                        return in_array($asignacion['estado'], ['completada', 'verificada']);
                    });

                    $todas_completadas = array_merge($stats['asignaciones']['completadas'], $asignaciones_completadas_pendientes);

                    if (empty($todas_completadas)):
                    ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle fa-3x mb-3"></i><br>
                                No hay asignaciones completadas recientemente.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todas_completadas as $asignacion): ?>
                            <?php
                            $estado = $asignacion['estado'];
                            $clase_estado = $estado == 'completada' ? 'bg-success' : 'bg-secondary';
                            $texto_estado = $estado == 'completada' ? 'Completada' : 'Verificada';
                            $icono_estado = $estado == 'completada' ? 'fa-check-circle' : 'fa-clipboard-check';
                            ?>
                            <div class="col-md-3 col-sm-6 mb-4 habitacion-card-completada" data-numero="<?= $asignacion['numero_habitacion'] ?>">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="card-title">N°: <?= htmlspecialchars($asignacion['numero_habitacion']) ?></h5>
                                        <div class="badge <?= $clase_estado ?> mb-3">
                                            <i class="fas <?= $icono_estado ?> mr-1"></i> <?= $texto_estado ?>
                                        </div>
                                        <p class="card-text small text-muted">
                                            <?= htmlspecialchars($asignacion['tipo_habitacion']) ?><br>
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y', strtotime($asignacion['fecha'])) ?><br>
                                            <i class="far fa-check-circle mr-1"></i>
                                            <?= isset($asignacion['fechaactualizacion']) ? date('H:i', strtotime($asignacion['fechaactualizacion'])) : 'Recientemente' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para filtrado y cambio de estado -->
<script>
    $(document).ready(function() {
        // Búsqueda de habitación pendiente - corregido para números
        $("#buscarHabitacionPendiente").on("keyup", function() {
            var value = $(this).val().toString();
            $(".habitacion-card-pendiente").each(function() {
                var numero = $(this).data("numero").toString();
                $(this).toggle(numero.indexOf(value) > -1);
            });
        });

        // Búsqueda de habitación completada - corregido para números
        $("#buscarHabitacionCompletada").on("keyup", function() {
            var value = $(this).val().toString();
            $(".habitacion-card-completada").each(function() {
                var numero = $(this).data("numero").toString();
                $(this).toggle(numero.indexOf(value) > -1);
            });
        });

        // Filtrado por estado para pendientes
        $(".filtro-estado-pendiente").on("click", function() {
            // Actualizar botón activo
            $(".filtro-estado-pendiente").removeClass("active");
            $(this).addClass("active");

            // Obtener el estado seleccionado
            var estado = $(this).data("estado");

            // Filtrar las tarjetas
            if (estado === "todos") {
                $(".habitacion-card-pendiente").show();
            } else {
                $(".habitacion-card-pendiente").hide();
                $(".habitacion-card-pendiente[data-estado='" + estado + "']").show();
            }
        });

        // Manejar cambio de estado de asignaciones
        $('.cambiar-estado').on('click', function() {
            const id = $(this).data('id');
            const estado = $(this).data('estado');

            // Textos para diferentes estados
            const textos = {
                'pendiente': 'pendiente',
                'enprogreso': 'en progreso',
                'completada': 'completada',
                'verificada': 'verificada'
            };

            // Colores para diferentes estados
            const colores = {
                'pendiente': '#ffc107',
                'enprogreso': '#17a2b8',
                'completada': '#28a745',
                'verificada': '#6c757d'
            };

            Swal.fire({
                title: `¿Marcar como ${textos[estado]}?`,
                text: `La asignación se marcará como ${textos[estado]}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: colores[estado],
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Sí, marcar como ${textos[estado]}`,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carga
                    const button = $(this);
                    const originalText = button.html();
                    button.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    button.prop('disabled', true);

                    // Realizar la solicitud AJAX para cambiar el estado
                    $.ajax({
                        url: `${baseUrl}controllers/limpieza/cambiar_estado_ajax.php`,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: id,
                            estado: estado
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Recargar la página para reflejar los cambios
                                    location.reload();
                                });
                            } else {
                                button.html(originalText);
                                button.prop('disabled', false);

                                Swal.fire({
                                    title: 'Error',
                                    text: response.message,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function() {
                            button.html(originalText);
                            button.prop('disabled', false);

                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error en la comunicación con el servidor',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    });
</script>

<!-- Estilos adicionales para las tarjetas de habitación -->
<style>
    .habitacion-card-pendiente .card,
    .habitacion-card-completada .card {
        transition: all 0.3s ease;
        border: 1px solid #ddd;
    }

    .habitacion-card-pendiente .card:hover,
    .habitacion-card-completada .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .badge {
        padding: 8px 12px;
        font-size: 0.85rem;
    }

    .bg-warning {
        color: #212529;
    }

    .btn-group .btn {
        box-shadow: none !important;
    }

    .filtro-estado-pendiente.active {
        font-weight: bold;
    }
</style>
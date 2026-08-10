/**
 * Admin Dashboard JavaScript
 * 
 * Funcionalidades para el dashboard de administración
 * incluyendo gráficos, filtros y ajustes responsivos
 */

// Variable global para el gráfico de actividad
let actividadChart;
let metricaActual = 'servicios_equipajes';

const dashboardData = JSON.parse(document.getElementById('dashboard-admin-root').dataset.dashboard);

$(document).ready(function () {
    // Al final de la función
    initializeHabitacionTooltips();

    // Gráfico de Actividad Reciente
    inicializarGraficoActividad();

    // Gráfico de Estado de Baños
    inicializarGraficoEstadoBanos();

    // Renderizar mapa de habitaciones
    renderizarMapaHabitaciones();

    // Filtrado de habitaciones en el mapa
    initializarFiltroHabitaciones();
});

/**
 * Inicializa el gráfico de actividad reciente
 */
function inicializarGraficoActividad() {
    const ctx = document.getElementById('actividad-chart');

    // Verificar si el elemento existe
    if (!ctx) {
        console.error("No se encontró el elemento canvas 'actividad-chart'");
        return;
    }

    const ctxChart = ctx.getContext('2d');

    // Verificar y preparar los datos
    let datosGraficos = dashboardData.graficos || {};

    // Obtener fecha actual para generar datos por defecto coherentes
    const hoy = new Date();
    const nombresDias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    // Datos por defecto basados en los últimos 7 días (fechas reales, series en cero si no hay datos)
    const datosPorDefecto = {
        labels: [],
        fechasCompletas: [],
        serviciosBano: new Array(7).fill(0),
        equipajes: new Array(7).fill(0),
        ocupacion: new Array(7).fill(0),
        ingresos: new Array(7).fill(0)
    };

    // Generar etiquetas y fechas completas por defecto
    for (let i = 6; i >= 0; i--) {
        const fecha = new Date(hoy);
        fecha.setDate(hoy.getDate() - i);

        const diaSemana = fecha.getDay(); // 0=domingo, 6=sábado
        datosPorDefecto.labels.push(nombresDias[diaSemana]);

        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const anio = fecha.getFullYear();
        datosPorDefecto.fechasCompletas.push(`${dia}/${mes}/${anio}`);
    }

    // Usar datos por defecto si no hay datos reales
    datosGraficos.labels = datosGraficos.labels && datosGraficos.labels.length > 0 ?
        datosGraficos.labels : datosPorDefecto.labels;

    datosGraficos.fechasCompletas = datosGraficos.fechasCompletas && datosGraficos.fechasCompletas.length > 0 ?
        datosGraficos.fechasCompletas : datosPorDefecto.fechasCompletas;

    datosGraficos.serviciosBano = datosGraficos.serviciosBano && datosGraficos.serviciosBano.length > 0 ?
        datosGraficos.serviciosBano : datosPorDefecto.serviciosBano;

    datosGraficos.equipajes = datosGraficos.equipajes && datosGraficos.equipajes.length > 0 ?
        datosGraficos.equipajes : datosPorDefecto.equipajes;

    datosGraficos.ocupacion = datosGraficos.ocupacion && datosGraficos.ocupacion.length > 0 ?
        datosGraficos.ocupacion : datosPorDefecto.ocupacion;

    datosGraficos.ingresos = datosGraficos.ingresos && datosGraficos.ingresos.length > 0 ?
        datosGraficos.ingresos : datosPorDefecto.ingresos;
    // Configuración del gráfico de actividad
    const actividadConfig = {
        type: 'line',
        data: {
            labels: datosGraficos.labels,
            datasets: [{
                label: 'Servicios Baño',
                data: datosGraficos.serviciosBano,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                pointBackgroundColor: '#007bff',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Equipajes',
                data: datosGraficos.equipajes,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                pointBackgroundColor: '#ffc107',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function (tooltipItems) {
                            // Mostrar el día y la fecha completa
                            const index = tooltipItems[0].dataIndex;
                            const dia = datosGraficos.labels[index];
                            const fecha = datosGraficos.fechasCompletas ? datosGraficos.fechasCompletas[index] : '';
                            return dia + (fecha ? ' - ' + fecha : '');
                        },
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }

                            // Mostrar formato correcto según la métrica
                            if (metricaActual === 'ingresos') {
                                return label + context.parsed.y.toFixed(2) + ' Bs.';
                            } else if (metricaActual === 'ocupacion') {
                                return label + context.parsed.y + ' habitaciones';
                            } else {
                                return label + context.parsed.y + ' unidades';
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    };

    // Crear el gráfico de actividad
    try {
        actividadChart = new Chart(ctxChart, actividadConfig);
    } catch (error) {
        console.error("Error al crear el gráfico de actividad:", error);
    }

    // Configurar eventos de los botones para cambiar de métrica
    $('.metrica-btn').on('click', function () {
        // Actualizar botón activo
        $('.metrica-btn').removeClass('active').attr('aria-pressed', 'false');
        $(this).addClass('active').attr('aria-pressed', 'true');

        // Obtener métrica seleccionada
        metricaActual = $(this).data('metrica');

        // Actualizar gráfico
        actualizarGraficoActividad(metricaActual, datosGraficos);
    });
}

/**
 * Actualiza el gráfico según la métrica seleccionada
 */
function actualizarGraficoActividad(metrica, datosGraficos) {
    if (!actividadChart) {
        console.error("El gráfico no está inicializado");
        return;
    }

    // Resetear datasets
    actividadChart.data.datasets = [];

    // Configurar datasets según la métrica
    switch (metrica) {
        case 'servicios_equipajes':
            actividadChart.data.datasets = [
                {
                    label: 'Servicios Baño',
                    data: datosGraficos.serviciosBano,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    pointBackgroundColor: '#007bff',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Equipajes',
                    data: datosGraficos.equipajes,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    pointBackgroundColor: '#ffc107',
                    tension: 0.4,
                    fill: true
                }
            ];

            // Actualizar leyenda
            $('#chart-legend').html(`
                <span class="mr-2"><i class="fas fa-square text-primary"></i> Servicios Baño</span>
                <span><i class="fas fa-square text-warning"></i> Equipajes</span>
            `);
            break;

        case 'ocupacion':
            actividadChart.data.datasets = [
                {
                    label: 'Habitaciones Ocupadas',
                    data: datosGraficos.ocupacion,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    pointBackgroundColor: '#28a745',
                    tension: 0.4,
                    fill: true
                }
            ];

            // Actualizar leyenda
            $('#chart-legend').html(`
                <span><i class="fas fa-square text-success"></i> Habitaciones Ocupadas</span>
            `);
            break;

        case 'ingresos':
            actividadChart.data.datasets = [
                {
                    label: 'Ingresos',
                    data: datosGraficos.ingresos,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    pointBackgroundColor: '#dc3545',
                    tension: 0.4,
                    fill: true
                }
            ];

            // Actualizar leyenda
            $('#chart-legend').html(`
                <span><i class="fas fa-square text-danger"></i> Ingresos Diarios (Bs.)</span>
            `);
            break;
    }

    // Actualizar el gráfico
    actividadChart.update();
}

/**
 * Inicializa el gráfico de estado de baños
 */
function inicializarGraficoEstadoBanos() {
    const ctx = document.getElementById('banos-chart');

    // Verificar si el elemento existe
    if (!ctx) {
        console.error("No se encontró el elemento canvas 'banos-chart'");
        return;
    }

    const ctxChart = ctx.getContext('2d');

    // Datos de estado de baños (si no llegan del backend, se muestran en cero, nunca con valores de ejemplo)
    const banosData = (dashboardData.graficos && dashboardData.graficos.banos) || {};
    const disponibles = banosData.disponibles || 0;
    const mantenimiento = banosData.mantenimiento || 0;
    const fuera_servicio = banosData.fuera_servicio || 0;

    if (disponibles === 0 && mantenimiento === 0 && fuera_servicio === 0) {
        const mensaje = document.createElement('p');
        mensaje.className = 'text-muted text-center mb-0 sin-datos-banos';
        mensaje.textContent = 'No hay datos de baños disponibles.';
        ctx.closest('.card-body').appendChild(mensaje);
        return;
    }

    // Crear gráfico
    new Chart(ctxChart, {
        type: 'doughnut',
        data: {
            labels: ['Disponibles', 'Mantenimiento', 'Fuera de Servicio'],
            datasets: [{
                data: [disponibles, mantenimiento, fuera_servicio],
                backgroundColor: [
                    '#28a745', // Verde - Disponibles
                    '#6c757d', // Gris - Mantenimiento
                    '#343a40'  // Negro - Fuera de servicio
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Genera y renderiza el mapa de habitaciones
 */
function renderizarMapaHabitaciones() {
    // Obtener el contenedor
    const container = $('#mapa-habitaciones-container');

    // Si no hay elemento, no continuar
    if (container.length === 0) return;

    // Si no hay datos, mostrar mensaje
    if (!dashboardData.habitaciones || dashboardData.habitaciones.length === 0) {
        container.html('<div class="col-12 text-center py-3"><p class="text-muted">No hay habitaciones registradas en el sistema.</p></div>');
        return;
    }

    // Limpiar contenedor
    container.empty();

    // Iterar por pisos y mostrar habitaciones agrupadas
    Object.keys(dashboardData.habitacionesPorPiso).forEach(pisoId => {
        const pisoData = dashboardData.habitacionesPorPiso[pisoId];

        // Crear el divisor de piso
        const pisoDivider = $('<div class="piso-divider"></div>')
            .html(`<i class="fas fa-building mr-1"></i> ${pisoData.nombre}`);

        // Crear el contenedor de habitaciones para este piso
        const pisoContainer = $('<div class="d-flex flex-wrap justify-content-start"></div>');

        // Agregar habitaciones a este piso
        pisoData.habitaciones.forEach(habitacion => {
            // Determinar clase e icono según estado
            let clase, icono;

            switch (habitacion.estado) {
                case 'disponible':
                    clase = 'success';
                    icono = 'check-circle';
                    break;
                case 'ocupada':
                    clase = 'warning';
                    icono = 'user';
                    break;
                case 'limpieza':
                    clase = 'primary';
                    icono = 'broom';
                    break;
                case 'mantenimiento':
                    clase = 'danger';
                    icono = 'tools';
                    break;
                default:
                    clase = 'secondary';
                    icono = 'question';
            }

            // Crear elemento de habitación
            const habElement = $(`
                <div class="habitacion-item m-1" data-estado="${habitacion.estado}" data-piso="${habitacion.idpiso}">
                    <a href="${BASE_URL}views/habitaciones/show.php?id=${habitacion.id_habitacion}"
                       class="btn btn-${clase} position-relative" 
                       style="width: 70px; height: 70px;"
                       data-toggle="tooltip" title="${capitalizeFirstLetter(habitacion.estado)} - ${habitacion.tipo_habitacion}">
                        <i class="fas fa-${icono} position-absolute" 
                           style="top: 15px; left: 50%; transform: translateX(-50%); font-size: 1.2rem;"></i>
                        <span class="position-absolute" 
                              style="bottom: 12px; left: 50%; transform: translateX(-50%); font-size: 14px; font-weight: bold;">
                            ${habitacion.numero}
                        </span>
                    </a>
                </div>
            `);

            // Agregar al contenedor
            pisoContainer.append(habElement);
        });

        // Agregar a contenedor principal
        container.append(pisoDivider);
        container.append(pisoContainer);
    });

    // Reinicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Inicializa los eventos de filtrado de habitaciones
 */
function initializarFiltroHabitaciones() {
    $('.habitaciones-mapa .btn-group button').on('click', function () {
        // Actualizar botón activo
        $(this).siblings().removeClass('active').attr('aria-pressed', 'false');
        $(this).addClass('active').attr('aria-pressed', 'true');

        const filtro = $(this).data('filter');

        if (filtro === 'all') {
            $('.habitacion-item').show();
        } else {
            $('.habitacion-item').hide();
            $('.habitacion-item[data-estado="' + filtro + '"]').show();
        }
    });
}

/**
 * Inicializa tooltips especiales para el mapa de habitaciones
 */
function initializeHabitacionTooltips() {
    // Detectar si es un dispositivo táctil
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (isTouchDevice) {
        // En dispositivos móviles, modificar los enlaces de habitaciones
        $('.habitacion-item a').each(function () {
            const $link = $(this);
            const tooltipTitle = $link.attr('title') || $link.data('original-title');
            const originalHref = $link.attr('href');

            // Quitar el tooltip original
            $link.tooltip('dispose');
            $link.removeAttr('data-toggle');
            $link.removeAttr('title');
            $link.removeAttr('data-original-title');

            // Crear un botón de información
            const $infoBtn = $('<button type="button" class="btn btn-info btn-sm info-badge"><i class="fas fa-info"></i></button>');
            $link.append($infoBtn);

            // Estilos para el botón de información
            $infoBtn.css({
                'position': 'absolute',
                'top': '5px',
                'right': '5px',
                'z-index': '10',
                'width': '22px',
                'height': '22px',
                'padding': '0',
                'border-radius': '50%',
                'font-size': '10px'
            });

            // Crear modal de información para esta habitación
            const modalId = 'modal-habitacion-' + $link.closest('.habitacion-item').data('numero');
            const $modal = $(`
                <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Información de Habitación</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>${tooltipTitle}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                <a href="${originalHref}" class="btn btn-primary">Ver detalles</a>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Añadir modal al cuerpo del documento
            $('body').append($modal);

            // Mostrar modal al hacer clic en el botón de información
            $infoBtn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#' + modalId).modal('show');
            });
        });
    }
}

/**
 * Capitaliza la primera letra de un string
 */
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * Formatea una moneda
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Actualiza los contadores de habitaciones
 */
function actualizarContadores() {
    // Calcular conteos
    let total = 0;
    let disponibles = 0;
    let ocupadas = 0;
    let mantenimiento = 0;
    let limpieza = 0;

    $('.habitacion-item').each(function () {
        total++;
        const estado = $(this).data('estado');

        switch (estado) {
            case 'disponible':
                disponibles++;
                break;
            case 'ocupada':
                ocupadas++;
                break;
            case 'mantenimiento':
                mantenimiento++;
                break;
            case 'limpieza':
                limpieza++;
                break;
        }
    });

    // Actualizar textos
    $('#contador-total').text(total);
    $('#contador-disponibles').text(disponibles);
    $('#contador-ocupadas').text(ocupadas);
    $('#contador-mantenimiento').text(mantenimiento);
    $('#contador-limpieza').text(limpieza);
}
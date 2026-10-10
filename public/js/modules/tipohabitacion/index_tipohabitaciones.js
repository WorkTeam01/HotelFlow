$(document).ready(function () {
    // Inicializar DataTable
    initDataTable('#tablaTiposHabitacion', {
        titulo: 'Tipos de Habitación',
        archivo: 'tipos_habitacion',
        subtitulo: 'Tipos de habitación registrados en el sistema',
        descripcionExcel: 'Registro de tipos de habitación del sistema',
        entidad: 'Tipos',
        columnas: [0, 1, 2, 3, 4],
        columnasTexto: [4],
        longitudPagina: 5,
        menuLongitud: [3, 5, 10, 25, 50]
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

    botonesCambiarEstado.forEach(boton => {
        boton.addEventListener('click', function() {
            const tipoId = this.dataset.id;
            const estadoActual = this.dataset.estado;
            const nombreTipo = this.dataset.nombre;

            const tituloAlerta = estadoActual == 1 ? `¿Desactivar ${escapeHtml(nombreTipo)}?` : `¿Activar ${escapeHtml(nombreTipo)}?`;
            const textoAlerta = estadoActual == 1 ? 'Este tipo de habitación no estará disponible para asignación.' : 'El tipo de habitación estará disponible nuevamente.';
            const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
            const cancelButtonText = 'Cancelar';

            Swal.fire({
                title: tituloAlerta,
                text: textoAlerta,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
                cancelButtonColor: themeColor('secondary'),
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCarga('Procesando...', 'Actualizando el estado del tipo de habitación');
                    window.location.href = `${BASE_URL}controllers/tipohabitaciones/desactivar_tipo_habitacion.php?id=${tipoId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
                }
            });
        });
    });
});
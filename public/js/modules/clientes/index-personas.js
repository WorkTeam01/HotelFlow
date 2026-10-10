$(document).ready(function () {
    // Inicializar DataTable para la tabla de personas
    initDataTable('#tablaPersonas', {
        titulo: 'Clientes Registrados',
        archivo: 'clientes_registrados',
        subtitulo: 'Listado de clientes del sistema',
        descripcionExcel: 'Registro de clientes del sistema',
        entidad: 'clientes',
        columnas: [0, 1, 2, 3, 4, 5],
        columnasImpresion: [0, 1, 2, 3],
        longitudPagina: 5,
        menuLongitud: [3, 5, 10, 25, 50]
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Delegado: las filas de otras páginas del DataTable no existen al cargar el documento
    $(document).on('click', '.btn-cambiar-estado', function() {
        const personaId = this.dataset.id;
        const estadoActual = this.dataset.estado;
        const nombrePersona = this.dataset.nombre;

        const tituloAlerta = estadoActual == 1 ? `¿Desactivar a ${escapeHtml(nombrePersona)}?` : `¿Activar a ${escapeHtml(nombrePersona)}?`;
        const textoAlerta = estadoActual == 1 ? 'El cliente no podrá realizar reservas.' : 'El cliente podrá realizar reservas nuevamente.';
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
                mostrarCarga('Procesando...', 'Actualizando el estado del cliente');
                window.location.href = `${BASE_URL}controllers/personas/desactivar_persona.php?id=${personaId}&estado=${estadoActual}&csrf_token=${CSRF_TOKEN}`;
            }
        });
    });
});
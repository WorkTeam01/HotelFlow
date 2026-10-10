$(document).ready(function () {
    // Inicializar DataTable
    initDataTable('#tablaUsuarios', {
        titulo: 'Usuarios del Sistema',
        archivo: 'usuarios_sistema',
        subtitulo: 'Usuarios registrados',
        descripcionExcel: 'Registro de usuarios del sistema',
        entidad: 'Usuarios',
        columnas: [0, 1, 2, 3, 4, 6, 7],
        longitudPagina: 5,
        menuLongitud: [3, 5, 10, 25, 50]
    });

    // Cambiar estado (activo/inactivo) de un usuario sin recargar la página.
    // Delegado sobre la tabla: los botones sobreviven al reordenamiento/paginado de DataTables.
    $('#tablaUsuarios').on('click', '.btn-cambiar-estado', function () {
        const boton = $(this);
        const usuarioId = boton.data('id');
        const estadoActual = boton.data('estado');
        const nombreUsuario = boton.data('nombre');

        const tituloAlerta = estadoActual == 1 ? `¿Desactivar a ${escapeHtml(nombreUsuario)}?` : `¿Activar a ${escapeHtml(nombreUsuario)}?`;
        const textoAlerta = estadoActual == 1 ? 'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' : 'El usuario podrá acceder nuevamente al sistema.';
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
            if (!result.isConfirmed) return;

            boton.prop('disabled', true);

            mostrarCarga('Procesando...', 'Actualizando el estado');
            ajaxRequest(
                `${BASE_URL}controllers/usuarios/desactivar_usuario.php`,
                'GET',
                {
                    id: usuarioId,
                    estado: estadoActual,
                    csrf_token: CSRF_TOKEN
                },
                function (data) {
                    boton.prop('disabled', false);

                    if (!data.success) {
                        Swal.fire('Error', data.message, 'error');
                        return;
                    }

                    // Actualizar la fila en el DOM, sin recargar la página
                    // (conserva página/orden/filtro de la tabla)
                    const fila = boton.closest('tr');
                    const badgeEstado = fila.find('.badge');
                    const activo = data.nuevoEstado == 1;

                    badgeEstado.text(activo ? 'Activo' : 'Inactivo');
                    badgeEstado.toggleClass('badge-success', activo).toggleClass('badge-danger', !activo);

                    boton.data('estado', data.nuevoEstado);
                    boton.toggleClass('btn-danger', activo).toggleClass('btn-success', !activo);
                    boton.attr('aria-label', activo ? 'Desactivar usuario' : 'Activar usuario');
                    boton.find('i').toggleClass('fa-user-slash', activo).toggleClass('fa-user-check', !activo);

                    Swal.fire({
                        icon: 'success',
                        title: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                function () {
                    boton.prop('disabled', false);
                    Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
                }
            );
        });
    });
});
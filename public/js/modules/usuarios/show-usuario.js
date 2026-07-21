$(document).ready(function () {
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastUserDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastUserDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del usuario con SweetAlert2 (AJAX, sin navegar fuera del detalle)
    $('#btnCambiarEstado').on('click', function (e) {
        e.preventDefault();

        const boton = $(this);
        const usuarioId = boton.data('id');
        const estadoActual = parseInt(boton.attr('data-estado'), 10);
        const nombreUsuario = boton.data('nombre');

        const tituloAlerta = estadoActual == 1 ?
            `¿Desactivar a ${nombreUsuario}?` :
            `¿Activar a ${nombreUsuario}?`;

        const textoAlerta = estadoActual == 1 ?
            'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' :
            'El usuario podrá acceder nuevamente al sistema.';

        const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
        const cancelButtonText = 'Cancelar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText
        }).then((result) => {
            if (!result.isConfirmed) return;

            ajaxRequest(
                `${BASE_URL}controllers/usuarios/desactivar_usuario.php`,
                'GET',
                {
                    id: usuarioId,
                    estado: estadoActual,
                    csrf_token: CSRF_TOKEN
                },
                function (data) {
                    if (!data.success) {
                        Swal.fire('Error', data.message, 'error');
                        return;
                    }

                    const activo = data.nuevoEstado == 1;

                    // Actualizar enlace de acción
                    boton.attr('data-estado', data.nuevoEstado);
                    boton.find('i').toggleClass('fa-user-slash text-danger', activo).toggleClass('fa-user-check text-success', !activo);
                    $('#btnCambiarEstadoTexto').text(activo ? 'Desactivar usuario' : 'Activar usuario');

                    // Actualizar indicador sobre el avatar
                    $('#avatarEstadoBadge')
                        .toggleClass('badge-success', activo).toggleClass('badge-danger', !activo)
                        .find('i').toggleClass('fa-check', activo).toggleClass('fa-times', !activo);

                    // Actualizar pestaña "Sistema"
                    $('#timelineEstadoIcono').toggleClass('fa-check-circle bg-success', activo).toggleClass('fa-times-circle bg-danger', !activo);
                    $('#timelineEstadoBadge')
                        .toggleClass('badge-success', activo).toggleClass('badge-danger', !activo)
                        .text(activo ? 'Activo' : 'Inactivo');

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
                    Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
                }
            );
        });
    });
});

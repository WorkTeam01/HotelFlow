// Mejoras para update-usuario.js
$(document).ready(function () {
    // Inicializar Select2 con tema por defecto
    initializeSelect2();

    // Nota: la selección de permisos por cargo (al cambiar el <select> y al
    // cargar la página) se maneja en permisos.js, que se carga junto a este
    // script (evita handlers duplicados/conflictivos).

    // Mostrar nombre del archivo seleccionado
    $('.custom-file-input').on('change', function () {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName);

        // Mostrar vista previa de la imagen
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-image').attr('src', e.target.result);
                $('#preview-container').show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Validación de contraseñas
    $('#confirmar_clave').on('blur', function () {
        const clave = $('#clave').val();
        const confirmar = $(this).val();

        if (clave !== '' || confirmar !== '') {
            if (clave !== confirmar) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Las contraseñas no coinciden',
                    text: 'Por favor, verifique que ambas contraseñas sean iguales'
                });
            }
        }
    });

    // Validación del formulario antes de enviar
    $('form').on('submit', function (e) {
        const clave = $('#clave').val();
        const confirmar = $('#confirmar_clave').val();

        if (clave !== '' || confirmar !== '') {
            if (clave !== confirmar) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en las contraseñas',
                    text: 'Las contraseñas no coinciden'
                });
                return false;
            }

            if (clave.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseña muy corta',
                    text: 'La contraseña debe tener al menos 6 caracteres'
                });
                return false;
            }
        }
    });

    // Nota: mostrar/ocultar contraseña (.password-toggle) se maneja de forma
    // centralizada en common-utils.js.

    // Validación de número de documento según tipo
    $('#tipodocumento').on('change', function () {
        let tipo = $(this).val();
        let numDocInput = $('#numdocumento');
        let docHelp = $('#docHelp');

        switch (tipo) {
            case 'DNI':
                numDocInput.attr('maxlength', '8');
                numDocInput.attr('pattern', '[0-9]{8}');
                numDocInput.attr('placeholder', 'Ingrese 8 dígitos');
                docHelp.text('El DNI debe contener 8 dígitos numéricos');
                break;
            case 'RUC':
                numDocInput.attr('maxlength', '11');
                numDocInput.attr('pattern', '[0-9]{11}');
                numDocInput.attr('placeholder', 'Ingrese 11 dígitos');
                docHelp.text('El RUC debe contener 11 dígitos numéricos');
                break;
            case 'PASAPORTE':
                numDocInput.attr('maxlength', '12');
                numDocInput.removeAttr('pattern');
                numDocInput.attr('placeholder', 'Ingrese el número de pasaporte');
                docHelp.text('Ingrese el número de pasaporte completo');
                break;
            case 'CI':
                numDocInput.attr('maxlength', '10');
                numDocInput.removeAttr('pattern');
                numDocInput.attr('placeholder', 'Ingrese el número de CI');
                docHelp.text('Ingrese el número de Cédula de Identidad completo');
                break;
            default:
                numDocInput.removeAttr('maxlength');
                numDocInput.removeAttr('pattern');
                numDocInput.attr('placeholder', 'Ingrese el número de documento');
                docHelp.text('El formato dependerá del tipo de documento seleccionado');
        }
    });

    // Cambiar estado (activo/inactivo) desde la tarjeta de acciones rápidas,
    // sin salir de la vista de edición.
    $('.cambiar-estado-link').on('click', function (e) {
        e.preventDefault();

        const enlace = $(this);
        const usuarioId = enlace.data('id');
        const estadoActual = parseInt(enlace.attr('data-estado'), 10);
        const nombreUsuario = enlace.data('nombre');

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

                    // Actualizar enlace de acción rápida
                    enlace.attr('data-estado', data.nuevoEstado);
                    $('#cambiarEstadoLinkTexto').text(activo ? 'Desactivar usuario' : 'Activar usuario');
                    $('#cambiarEstadoLinkIcono')
                        .toggleClass('fa-user-slash text-danger', activo)
                        .toggleClass('fa-user-check text-success', !activo);

                    // Actualizar badge de la tarjeta de resumen
                    $('#resumenEstadoBadge')
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
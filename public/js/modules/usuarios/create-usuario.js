// Mejoras para create-usuario.js
$(document).ready(function () {
    // Inicializar Select2 con tema de Bootstrap 4
    initializeSelect2();

    // Actualizar etiqueta del archivo seleccionado con animación
    $('.custom-file-input').on('change', function () {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);

        // Mostrar vista previa de la imagen con animación
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-image').attr('src', e.target.result);
                $('#preview-container').fadeIn(300);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Validación de contraseñas con feedback visual mejorado
    $('#confirmar_clave').on('keyup', function () {
        let clave = $('#clave').val();
        let confirmar = $(this).val();

        if (confirmar.length > 0) {
            if (clave === confirmar && clave.length >= 6) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $('.password-feedback').html('<small class="text-success"><i class="fas fa-check-circle"></i> Las contraseñas coinciden</small>');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $('.password-feedback').html('<small class="text-danger"><i class="fas fa-times-circle"></i> Las contraseñas no coinciden o son muy cortas</small>');
            }
        } else {
            $(this).removeClass('is-valid is-invalid');
            $('.password-feedback').html('');
        }
    });

    // Validación del formulario antes de enviar con Sweet Alert
    $('#formCrearUsuario').on('submit', function (e) {
        let clave = $('#clave').val();
        let confirmar = $('#confirmar_clave').val();

        if (clave !== confirmar) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Las contraseñas no coinciden'
            });
            return false;
        }

        if (clave.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'La contraseña debe tener al menos 6 caracteres'
            });
            return false;
        }
    });

    // Mostrar/ocultar contraseña
    $('#showPassword').on('mousedown', function () {
        $('#clave').attr('type', 'text');
        $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
    }).on('mouseup mouseleave', function () {
        $('#clave').attr('type', 'password');
        $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    });

    // Botones para seleccionar/deseleccionar todos los permisos
    $('#selectAllPermissions').click(function () {
        $('.permiso-checkbox').prop('checked', true);
    });

    $('#deselectAllPermissions').click(function () {
        $('.permiso-checkbox').prop('checked', false);
    });

    // Validación de número de documento según tipo
    $('#tipodocumento').on('change', function () {
        let tipo = $(this).val();
        let numDocInput = $('#numdocumento');
        let docHelp = $('#docHelp');

        numDocInput.val(''); // Limpiar el campo al cambiar el tipo

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
            case 'Pasaporte':
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
});
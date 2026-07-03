// Mejoras para update-usuario.js
$(document).ready(function () {
    // Inicializar Select2 con tema por defecto
    initializeSelect2();

    // Función para seleccionar permisos según el cargo
    function seleccionarPermisosPorCargo(cargo) {
        // Primero desmarcamos todos los permisos
        $('.permiso-checkbox').prop('checked', false);

        // Luego marcamos los permisos correspondientes al cargo seleccionado
        $('.permiso-checkbox[data-cargo="' + cargo + '"]').prop('checked', true);

        // Activamos la pestaña correspondiente al cargo
        $('#' + cargo.toLowerCase().replace(' ', '-') + '-tab').tab('show');
    }

    // Evento al cambiar el cargo
    $('#cargo').change(function () {
        var cargoSeleccionado = $(this).val();
        if (cargoSeleccionado) {
            seleccionarPermisosPorCargo(cargoSeleccionado);
        }
    });

    // Inicializar con el cargo actual (para edición)
    var cargoInicial = $('#cargo').val();
    if (cargoInicial) {
        seleccionarPermisosPorCargo(cargoInicial);
    }

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
});
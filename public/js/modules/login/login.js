$(document).ready(function() {
    // Configuración de Toast para SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Nota: mostrar/ocultar contraseña (.password-toggle) se maneja de
    // forma centralizada en common-utils.js.

    // Add subtle animation to login box
    $('.login-box').addClass('login-animation');

    // Marca un campo como inválido y asocia el mensaje de error via aria-describedby
    function marcarInvalido($input, mensaje) {
        $input.addClass('is-invalid').attr('aria-invalid', 'true');
        $('#' + $input.attr('aria-describedby')).text(mensaje);
    }

    function limpiarInvalido($input) {
        $input.removeClass('is-invalid').removeAttr('aria-invalid');
        $('#' + $input.attr('aria-describedby')).text('');
    }

    // Form submission with validation
    $('#login-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const $identifier = $('input[name="identifier"]');
        const $password = $('input[name="clave"]');
        const identifier = $identifier.val().trim();
        const password = $password.val().trim();
        let isValid = true;

        // Validar que los campos no estén vacíos
        if (!identifier) {
            marcarInvalido($identifier, 'Ingrese su email o número de documento');
            isValid = false;
        } else {
            limpiarInvalido($identifier);
        }

        if (!password) {
            marcarInvalido($password, 'Ingrese su contraseña');
            isValid = false;
        } else {
            limpiarInvalido($password);
        }

        if (!isValid) {
            Toast.fire({
                icon: 'error',
                title: 'Por favor revise los campos marcados'
            });
            return;
        }

        // Show loading state and submit immediately
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            title: 'Iniciando sesión...',
            didOpen: () => {
                Swal.showLoading();
            }
        });

        this.submit();
    });

    // Remove invalid state on input
    $('input').on('input', function() {
        limpiarInvalido($(this));
    });
});

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

    // Form submission with validation
    $('#login-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const identifier = $('input[name="identifier"]').val().trim();
        const password = $('input[name="clave"]').val().trim();
        let isValid = true;

        // Validar que los campos no estén vacíos
        if (!identifier) {
            $('input[name="identifier"]').addClass('is-invalid');
            isValid = false;
        } else {
            $('input[name="identifier"]').removeClass('is-invalid');
        }

        if (!password) {
            $('input[name="clave"]').addClass('is-invalid');
            isValid = false;
        } else {
            $('input[name="clave"]').removeClass('is-invalid');
        }

        if (!isValid) {
            Toast.fire({
                icon: 'error',
                title: 'Por favor complete todos los campos'
            });
            return;
        }

        // Validar la longitud de la contraseña
        if (password.length < 6) {
            $('input[name="clave"]').addClass('is-invalid');
            Toast.fire({
                icon: 'error',
                title: 'La contraseña debe tener al menos 6 caracteres'
            });
            return;
        }

        // Show loading state
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

        // Submit the form after small delay to show loading
        setTimeout(() => {
            this.submit();
        }, 1000);
    });

    // Remove invalid class on input
    $('input').on('input', function() {
        $(this).removeClass('is-invalid');
    });
});

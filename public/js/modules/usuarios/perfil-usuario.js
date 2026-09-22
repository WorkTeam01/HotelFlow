document.addEventListener('DOMContentLoaded', function () {

    // === Imagen de perfil: nombre de archivo + validación + vista previa ===
    const imagenInput = document.getElementById('imagen');
    if (imagenInput) {
        imagenInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const label = e.target.nextElementSibling;

            if (!file) {
                if (label) label.textContent = 'Seleccionar archivo...';
                return;
            }

            if (label) label.textContent = file.name;

            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo muy grande',
                    text: 'El archivo no debe superar los 2MB'
                });
                e.target.value = '';
                if (label) label.textContent = 'Seleccionar archivo...';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tipo de archivo no válido',
                    text: 'Solo se permiten archivos JPG, PNG, GIF y WEBP'
                });
                e.target.value = '';
                if (label) label.textContent = 'Seleccionar archivo...';
                return;
            }

            var previewContainer = document.getElementById('preview-container');
            var previewImage = document.getElementById('preview-image');
            if (previewContainer && previewImage) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    previewImage.src = ev.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // === Cambio de contraseña vía AJAX ===
    var formCambiarPassword = document.getElementById('formCambiarPassword');
    if (formCambiarPassword) {
        formCambiarPassword.addEventListener('submit', function (e) {
            e.preventDefault();

            var claveActual = document.getElementById('clave_actual').value;
            var nuevaClave = document.getElementById('nueva_clave').value;
            var confirmarClave = document.getElementById('confirmar_nueva_clave').value;

            if (!claveActual || !nuevaClave || !confirmarClave) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Todos los campos son obligatorios' });
                return;
            }

            if (nuevaClave !== confirmarClave) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Las contraseñas no coinciden' });
                return;
            }

            if (nuevaClave.length < 6) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'La contraseña debe tener al menos 6 caracteres' });
                return;
            }

            var formData = new FormData(formCambiarPassword);
            var btn = document.getElementById('btnCambiarPassword');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Procesando...';

            fetch(BASE_URL + 'controllers/usuarios/ajax_cambiar_clave.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    btn.disabled = false;
                    btn.textContent = 'Cambiar Contraseña';

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.message,
                            allowOutsideClick: false,
                            confirmButtonText: 'Aceptar'
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                window.location.href = BASE_URL + 'controllers/auth/logout.php';
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Cambiar Contraseña';
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar la solicitud' });
                });
        });
    }
});

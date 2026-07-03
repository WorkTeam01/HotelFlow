document.addEventListener('DOMContentLoaded', function () {
    // Inicializar Select2 en el campo de selección
    initializeSelect2();

    // Validación del formulario
    const formTipoHabitacion = document.getElementById('formTipoHabitacion');

    if (formTipoHabitacion) {
        formTipoHabitacion.addEventListener('submit', function (event) {
            let isValid = true;

            // Validar nombre
            const nombreInput = document.getElementById('nombre');
            if (!nombreInput.value.trim()) {
                nombreInput.classList.add('is-invalid');
                isValid = false;
            } else {
                nombreInput.classList.remove('is-invalid');
                nombreInput.classList.add('is-valid');
            }

            // Validar capacidad máxima
            const capacidadInput = document.getElementById('capacidad_maxima');
            const capacidad = parseInt(capacidadInput.value);
            if (isNaN(capacidad) || capacidad < 1 || capacidad > 10) {
                capacidadInput.classList.add('is-invalid');
                isValid = false;
            } else {
                capacidadInput.classList.remove('is-invalid');
                capacidadInput.classList.add('is-valid');
            }

            // Prevenir envío si no es válido
            if (!isValid) {
                event.preventDefault();

                // Mostrar alerta
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Por favor revise los campos marcados en rojo'
                });
            } else {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Procesando la información',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        });

        // Validación en tiempo real
        document.getElementById('nombre').addEventListener('input', function () {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        document.getElementById('capacidad_maxima').addEventListener('input', function () {
            const capacidad = parseInt(this.value);
            if (!isNaN(capacidad) && capacidad >= 1 && capacidad <= 10) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
});
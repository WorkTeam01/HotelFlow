document.addEventListener('DOMContentLoaded', function () {
    // Manejar cambio en el estado (switch)
    const estadoSwitch = document.getElementById('estadoSwitch');
    const estadoLabel = document.getElementById('estadoLabel');
    const estadoInput = document.getElementById('estado');

    estadoSwitch.addEventListener('change', function () {
        if (this.checked) {
            estadoLabel.textContent = 'Activo';
            estadoInput.value = '1';
        } else {
            estadoLabel.textContent = 'Inactivo';
            estadoInput.value = '0';
        }
    });

    // Validación del formulario
    const formTipoHabitacion = document.getElementById('formTipoHabitacion');

    if (formTipoHabitacion) {
        // Marcar el estado inicial del formulario
        const elementosOriginales = {};

        document.querySelectorAll('#formTipoHabitacion input, #formTipoHabitacion textarea, #formTipoHabitacion select').forEach(elem => {
            if (elem.type === 'checkbox') {
                elementosOriginales[elem.id] = elem.checked;
            } else {
                elementosOriginales[elem.id] = elem.value;
            }
        });

        // Validación en envío del formulario
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
                    title: 'Actualizando...',
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

        // Confirmación al cancelar edición
        document.querySelector('a.btn-secondary').addEventListener('click', function (e) {
            // Verificar si hay cambios en el formulario
            let hayModificaciones = false;

            document.querySelectorAll('#formTipoHabitacion input, #formTipoHabitacion textarea, #formTipoHabitacion select').forEach(elem => {
                if (elem.type === 'checkbox') {
                    if (elementosOriginales[elem.id] !== elem.checked) {
                        hayModificaciones = true;
                    }
                } else if (elem.type === 'hidden' && elem.id === 'estado') {
                    // Ignorar el campo estado oculto ya que su valor puede cambiar con el switch
                } else {
                    if (elementosOriginales[elem.id] !== elem.value) {
                        hayModificaciones = true;
                    }
                }
            });

            if (hayModificaciones) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Abandonar cambios?',
                    text: 'Hay cambios sin guardar. ¿Está seguro de que desea salir sin guardar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, salir',
                    cancelButtonText: 'No, permanecer'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '<?= $URL; ?>views/tipohabitacion/index.php';
                    }
                });
            }
        });
    }
});
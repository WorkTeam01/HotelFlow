document.addEventListener('DOMContentLoaded', function () {
    // Elementos del formulario
    const tipoEstanciaSelect = document.getElementById('tipo_estancia');
    const unidadDuracionSpan = document.getElementById('unidad_duracion');
    const duracionInput = document.getElementById('duracion');
    const precioInput = document.getElementById('precio');
    const tipoHabitacionSelect = document.getElementById('id_tipo');
    const estadoSelect = document.getElementById('estado');

    // Elementos de vista previa
    const previewTitle = document.getElementById('preview-title');
    const previewDuration = document.getElementById('preview-duration');
    const previewPrice = document.getElementById('preview-price');
    const previewStatus = document.getElementById('preview-status');

    // Cambiar la unidad de duración según el tipo de estancia
    tipoEstanciaSelect.addEventListener('change', function () {
        const unidad = this.value === 'horas' ? 'horas' : 'días';
        unidadDuracionSpan.textContent = unidad;
        actualizarVistaPrevia();
    });

    // Inicializar Select2
    initializeSelect2();

    // Cambiar el estado en la vista previa
    estadoSelect.addEventListener('change', function () {
        if (this.value === '1') {
            previewStatus.className = 'text-success';
            previewStatus.innerHTML = '<i class="fas fa-check-circle"></i> Activo';
        } else {
            previewStatus.className = 'text-danger';
            previewStatus.innerHTML = '<i class="fas fa-times-circle"></i> Inactivo';
        }
    });

    // Actualizar vista previa al cambiar tipo de habitación
    tipoHabitacionSelect.addEventListener('change', function () {
        actualizarVistaPrevia();
    });

    // Actualizar vista previa al cambiar duración
    duracionInput.addEventListener('input', function () {
        actualizarVistaPrevia();
    });

    // Actualizar vista previa al cambiar precio
    precioInput.addEventListener('input', function () {
        actualizarVistaPrevia();
    });

    // Función para actualizar la vista previa
    function actualizarVistaPrevia() {
        const tipoSeleccionado = tipoHabitacionSelect.options[tipoHabitacionSelect.selectedIndex];
        const tipoTexto = tipoSeleccionado ? tipoSeleccionado.text : 'Seleccione un tipo de habitación';
        const duracion = parseInt(duracionInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        const unidad = tipoEstanciaSelect.value === 'horas' ? 'hora' : 'día';
        const unidadPlural = tipoEstanciaSelect.value === 'horas' ? 'horas' : 'días';

        previewTitle.textContent = tipoSeleccionado && tipoSeleccionado.value ? tipoTexto : 'Seleccione un tipo de habitación';
        previewDuration.textContent = duracion + ' ' + (duracion === 1 ? unidad : unidadPlural);
        previewPrice.textContent = 'Bs. ' + precio.toFixed(2);
    }

    // Marcar el estado inicial del formulario para detectar cambios
    const elementosOriginales = {};

    document.querySelectorAll('#formTarifa input, #formTarifa textarea, #formTarifa select').forEach(elem => {
        if (elem.type === 'checkbox') {
            elementosOriginales[elem.id] = elem.checked;
        } else {
            elementosOriginales[elem.id] = elem.value;
        }
    });

    // Validación del formulario
    const formTarifa = document.getElementById('formTarifa');

    if (formTarifa) {
        formTarifa.addEventListener('submit', function (event) {
            let isValid = true;

            // Validar tipo de habitación
            const tipoHabitacionInput = document.getElementById('id_tipo');
            if (!tipoHabitacionInput.value) {
                tipoHabitacionInput.classList.add('is-invalid');
                isValid = false;
            } else {
                tipoHabitacionInput.classList.remove('is-invalid');
                tipoHabitacionInput.classList.add('is-valid');
            }

            // Validar duración
            const duracionInput = document.getElementById('duracion');
            const duracion = parseInt(duracionInput.value);
            if (isNaN(duracion) || duracion <= 0) {
                duracionInput.classList.add('is-invalid');
                isValid = false;
            } else {
                duracionInput.classList.remove('is-invalid');
                duracionInput.classList.add('is-valid');
            }

            // Validar precio
            const precioInput = document.getElementById('precio');
            const precio = parseFloat(precioInput.value);
            if (isNaN(precio) || precio <= 0) {
                precioInput.classList.add('is-invalid');
                isValid = false;
            } else {
                precioInput.classList.remove('is-invalid');
                precioInput.classList.add('is-valid');
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
        document.getElementById('id_tipo').addEventListener('change', function () {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        document.getElementById('duracion').addEventListener('input', function () {
            const duracion = parseInt(this.value);
            if (!isNaN(duracion) && duracion > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        document.getElementById('precio').addEventListener('input', function () {
            const precio = parseFloat(this.value);
            if (!isNaN(precio) && precio > 0) {
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

            document.querySelectorAll('#formTarifa input, #formTarifa textarea, #formTarifa select').forEach(elem => {
                if (elem.id === 'idtarifa') {
                    // Ignorar el campo oculto de ID
                    return;
                }

                if (elementosOriginales[elem.id] !== elem.value) {
                    hayModificaciones = true;
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
                        window.location.href = `${BASE_URL}views/tarifas/index.php`;
                    }
                });
            }
        });
    }
});
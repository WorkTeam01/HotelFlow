/**
 * Script para el formulario de edición de habitaciones
 */

$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Cuando cambia el tipo de habitación, actualizar la capacidad actual
    $('#id_tipo').change(function() {
        // Mostrar confirmación solo si hay valor actual
        var capacidadActual = parseInt($('#capacidad_actual').val()) || 0;
        var capacidadNueva = parseInt($(this).find(':selected').data('capacidad')) || 0;

        if (capacidadActual > 0 && capacidadActual !== capacidadNueva) {
            Swal.fire({
                title: '¿Actualizar capacidad?',
                text: `¿Desea actualizar la capacidad de ${capacidadActual} a ${capacidadNueva} personas?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'No, mantener actual'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#capacidad_actual').val(capacidadNueva);
                }
            });
        } else {
            // Si no hay valor actual, simplemente actualizar
            $('#capacidad_actual').val(capacidadNueva);
        }
    });

    // Validación del formulario
    $('#formHabitacion').submit(function(e) {
        var numero = $('#numero').val().trim();
        var id_tipo = $('#id_tipo').val();
        var idpiso = $('#idpiso').val();
        var capacidad_actual = $('#capacidad_actual').val();
        var precio_base = $('#precio_base').val();

        if (numero === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El número de habitación es obligatorio'
            });
            return false;
        }

        if (id_tipo === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un tipo de habitación'
            });
            return false;
        }

        if (idpiso === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un piso'
            });
            return false;
        }

        if (capacidad_actual === '' || parseInt(capacidad_actual) < 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La capacidad actual debe ser un número mayor o igual a cero'
            });
            return false;
        }

        if (precio_base === '' || parseFloat(precio_base) <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El precio base debe ser un número mayor que cero'
            });
            return false;
        }

        return true;
    });
});

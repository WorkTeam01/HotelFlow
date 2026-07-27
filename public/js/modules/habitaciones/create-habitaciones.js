/**
 * Script para el formulario de creación de habitaciones
 */

$(document).ready(function() {
    // Inicializar Select2
    initializeSelect2();

    // Cuando cambia el tipo de habitación, actualizar la capacidad actual
    $('#id_tipo').change(function() {
        var capacidad = $(this).find(':selected').data('capacidad');
        if (capacidad) {
            $('#capacidad_actual').val(capacidad);
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

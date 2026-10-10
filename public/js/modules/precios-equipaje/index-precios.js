/**
 * Script para la gestión de precios de equipaje
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con los precios de equipaje (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    // Inicializar Select2
    initializeSelect2();

    // Inicializar DataTable
    initDataTable('#tablaPrecios', {
        titulo: 'Precios de Equipaje',
        archivo: 'precios_equipaje',
        subtitulo: 'Precios registrados',
        descripcionExcel: 'Registro de precios de equipaje del sistema',
        entidad: 'precios',
        columnas: [0, 1, 2, 3, 4],
        columnasTexto: [4]
    });

    // Botón para crear nuevo precio
    $('#btnNuevoPrecio').on('click', function () {
        // Limpiar formulario
        $('#formPrecio')[0].reset();

        // Configurar para creación
        $('#precioAction').val('create');
        $('#idPrecio').val('');
        $('#tamano').val('');
        $('#descripcion').val('');
        $('#precio').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalPrecioHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalPrecioLabel').text('Crear Nuevo Precio de Equipaje');
        $('#btnGuardarPrecio').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarPrecio').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalPrecio').modal('show');
    });

    // Botón para editar precio
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const tamano = $(this).data('tamano');
        const descripcion = $(this).data('descripcion') || ''; // Manejar caso de descripción null o undefined
        const precio = $(this).data('precio');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#precioAction').val('edit');
        $('#idPrecio').val(id);
        $('#tamano').val(tamano);
        $('#descripcion').val(descripcion);
        $('#precio').val(precio);
        $('#estado').val(estado);

        // Cambiar apariencia del modal
        $('#modalPrecioHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalPrecioLabel').text('Editar Precio de Equipaje');
        $('#btnGuardarPrecio').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarPrecio').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalPrecio').modal('show');
    });

    // Procesar formulario
    $('#formPrecio').on('submit', function (e) {
        e.preventDefault();

        const action = $('#precioAction').val();

        // Manejar descripción vacía para asegurar que se guarde como NULL
        if ($('#descripcion').val().trim() === '') {
            $('#descripcion').val(''); // Asegurarse de que esté vacío sin espacios
        }

        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${BASE_URL}controllers/precios-equipaje/crear_precio_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${BASE_URL}controllers/precios-equipaje/actualizar_precio_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarPrecio').prop('disabled', true).html(loadingMsg);
                mostrarCarga(loadingMsg.replace(/<[^>]+>/g, '').trim(), 'Procesando la información');
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalPrecio').modal('hide');

                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        confirmButtonColor: themeColor('danger'),
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarPrecio').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    confirmButtonColor: themeColor('danger'),
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarPrecio').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $('.cambiar-estado').on('click', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const boton = $(this);

        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const textoEstado = estadoActual == 1 ? 'desactivar' : 'activar';
        const textoEstadoCapitalizado = textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1);

        Swal.fire({
            title: `¿${textoEstadoCapitalizado} este precio?`,
            text: `El precio de equipaje será ${textoEstado}do.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${BASE_URL}controllers/precios-equipaje/cambiar_estado_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado_actual: estadoActual,
                        csrf_token: CSRF_TOKEN
                    },
                    success: function (response) {
                        if (response.success) {
                            // Recargar la página para mostrar el mensaje con mensajes.php
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                confirmButtonColor: themeColor('danger'),
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            confirmButtonColor: themeColor('danger'),
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor'
                        });
                    }
                });
            }
        });
    });

    // Limpiar modal al cerrarlo para evitar problemas en dispositivos móviles
    $('#modalPrecio').on('hidden.bs.modal', function () {
        $('#formPrecio')[0].reset();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formPrecio').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalPrecio').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#tamano').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalPrecio').data('backdrop', 'static');
    $('#modalPrecio').data('keyboard', false);
});
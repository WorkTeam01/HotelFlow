/**
 * Script para la gestión de pisos
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con los pisos (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar Select2
    initializeSelect2();
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar DataTable
    initDataTable('#tablaPisos', {
        titulo: 'Pisos',
        archivo: 'pisos',
        subtitulo: 'Pisos registrados',
        descripcionExcel: 'Registro de pisos del sistema',
        entidad: 'pisos',
        columnas: [0, 1, 2, 3, 4],
        columnasTexto: [4]
    });

    // Botón para crear nuevo piso
    $('#btnNuevoPiso').on('click', function () {
        // Limpiar formulario
        $('#formPiso')[0].reset();

        // Configurar para creación
        $('#pisoAction').val('create');
        $('#idPiso').val('');
        $('#nombre').val('');
        $('#descripcion').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalPisoHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalPisoLabel').text('Crear Nuevo Piso');
        $('#btnGuardarPiso').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarPiso').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalPiso').modal('show');
    });

    // Botón para editar piso
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion') || ''; // Manejar caso de descripción null o undefined
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#pisoAction').val('edit');
        $('#idPiso').val(id);
        $('#nombre').val(nombre);
        $('#descripcion').val(descripcion);
        $('#estado').val(estado);

        $('#estado').trigger('change');

        // Cambiar apariencia del modal
        $('#modalPisoHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalPisoLabel').text('Editar Piso');
        $('#btnGuardarPiso').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarPiso').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalPiso').modal('show');
    });

    // Procesar formulario
    $('#formPiso').on('submit', function (e) {
        e.preventDefault();

        const action = $('#pisoAction').val();

        // Manejar descripción vacía para asegurar que se guarde como NULL
        if ($('#descripcion').val().trim() === '') {
            $('#descripcion').val(''); // Asegurarse de que esté vacío sin espacios
        }

        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${BASE_URL}controllers/pisos/crear_piso_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${BASE_URL}controllers/pisos/actualizar_piso_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarPiso').prop('disabled', true).html(loadingMsg);
                mostrarCarga(loadingMsg.replace(/<[^>]+>/g, '').trim(), 'Procesando la información');
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalPiso').modal('hide');

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
                    $('#btnGuardarPiso').prop('disabled', false).html(successBtn);
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
                $('#btnGuardarPiso').prop('disabled', false).html(successBtn);
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
            title: `¿${textoEstadoCapitalizado} este piso?`,
            text: `El piso será ${textoEstado}do.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${BASE_URL}controllers/pisos/cambiar_estado_ajax.php`,
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
    $('#modalPiso').on('hidden.bs.modal', function () {
        $('#formPiso')[0].reset();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formPiso').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalPiso').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#nombre').trigger('focus');
    });
});
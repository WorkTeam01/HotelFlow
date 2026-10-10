/**
 * Script para la gestión de categorías
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con las categorías (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar DataTable
    initDataTable('#tablaCategorias', {
        titulo: 'Categorías',
        archivo: 'categorias',
        subtitulo: 'Categorías registradas',
        descripcionExcel: 'Registro de categorías del sistema',
        entidad: 'categorías',
        columnas: [0, 1, 2, 3],
        columnasTexto: [3]
    });

    // Botón para crear nueva categoría
    $('#btnNuevaCategoria').on('click', function () {
        // Limpiar formulario
        $('#formCategoria')[0].reset();

        // Configurar para creación
        $('#categoriaAction').val('create');
        $('#idCategoria').val('');
        $('#nombre').val('');
        $('#descripcion').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalCategoriaHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalCategoriaLabel').text('Crear Nueva Categoría');
        $('#btnGuardarCategoria').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarCategoria').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalCategoria').modal('show');
    });

    // Botón para editar categoría
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion') || ''; // Manejar caso de descripción null o undefined
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#categoriaAction').val('edit');
        $('#idCategoria').val(id);
        $('#nombre').val(nombre);
        $('#descripcion').val(descripcion);
        $('#estado').val(estado);

        // Cambiar apariencia del modal
        $('#modalCategoriaHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalCategoriaLabel').text('Editar Categoría');
        $('#btnGuardarCategoria').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarCategoria').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalCategoria').modal('show');
    });

    // Procesar formulario
    $('#formCategoria').on('submit', function (e) {
        e.preventDefault();

        const action = $('#categoriaAction').val();

        // Manejar descripción vacía para asegurar que se guarde como NULL
        if ($('#descripcion').val().trim() === '') {
            $('#descripcion').val(''); // Asegurarse de que esté vacío sin espacios
        }

        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${BASE_URL}controllers/categoria/crear_categoria_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${BASE_URL}controllers/categoria/actualizar_categoria_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarCategoria').prop('disabled', true).html(loadingMsg);
                mostrarCarga(loadingMsg.replace(/<[^>]+>/g, '').trim(), 'Procesando la información');
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalCategoria').modal('hide');

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
                    $('#btnGuardarCategoria').prop('disabled', false).html(successBtn);
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
                $('#btnGuardarCategoria').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $(document).on('click', '.cambiar-estado', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const boton = $(this);

        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const textoEstado = estadoActual == 1 ? 'desactivar' : 'activar';
        const textoEstadoCapitalizado = textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1);

        Swal.fire({
            title: `¿${textoEstadoCapitalizado} esta categoría?`,
            text: `La categoría será ${textoEstado}da.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? themeColor('danger') : themeColor('success'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${BASE_URL}controllers/categoria/desactivar_categoria_ajax.php`,
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
    $('#modalCategoria').on('hidden.bs.modal', function () {
        $('#formCategoria')[0].reset();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formCategoria').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalCategoria').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#nombre').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalCategoria').data('backdrop', 'static');
    $('#modalCategoria').data('keyboard', false);
});
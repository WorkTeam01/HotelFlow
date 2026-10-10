$(document).ready(function () {
    // Inicializar DataTable
    initDataTable('#tablaBanos', {
        titulo: 'Baños del Sistema',
        archivo: 'banos_sistema',
        subtitulo: 'Baños registrados',
        descripcionExcel: 'Registro de baños del sistema',
        entidad: 'Baños',
        columnas: [0, 1, 2, 3, 4],
        columnasTexto: [4]
    });

    // Inicializar Select2
    initializeSelect2();

    // Botón para crear nuevo baño
    $(document).on('click', '#btnNuevoBano', function () {
        // Configurar para creación
        $('#banoAction').val('create');
        $('#idBano').val('');
        $('#nombreBano').val('');
        $('#ubicacionBano').val('');
        $('#precioBano').val('0.00');
        $('#estadoBano').val('disponible');

        // Cambiar apariencia del modal
        $('#modalBanoHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalBanoLabel').text('Crear Nuevo Baño');
        $('#btnGuardarBano').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarBano').html('<i class="fas fa-check"></i> Guardar Baño');

        // Mostrar modal
        $('#modalBano').modal('show');
    });

    // Botón para editar baño
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const ubicacion = $(this).data('ubicacion');
        const precio = $(this).data('precio');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#banoAction').val('edit');
        $('#idBano').val(id);
        $('#nombreBano').val(nombre);
        $('#ubicacionBano').val(ubicacion);
        $('#precioBano').val(precio);
        $('#estadoBano').val(estado);

        $('#estadoBano').trigger('change');

        // Cambiar apariencia del modal
        $('#modalBanoHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalBanoLabel').text('Editar Baño');
        $('#btnGuardarBano').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarBano').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalBano').modal('show');
    });

    // Procesar formulario
    $('#formBano').on('submit', function (e) {
        e.preventDefault();

        const action = $('#banoAction').val();
        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = BASE_URL + 'controllers/banos/crear_bano_ajax.php';
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-check"></i> Guardar Baño';
        } else {
            url = BASE_URL + 'controllers/banos/actualizar_bano_ajax.php';
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarBano').prop('disabled', true).html(loadingMsg);
                mostrarCarga(loadingMsg.replace(/<[^>]+>/g, '').trim(), 'Procesando la información');
            },
            success: function (response) {
                if (response.success) {
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
                    $('#btnGuardarBano').prop('disabled', false).html(successBtn);
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    confirmButtonColor: themeColor('danger'),
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarBano').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $('.cambiar-estado').on('click', function (e) {
        e.preventDefault();

        const banoId = $(this).data('id');
        const estadoActual = $(this).data('estado');
        const nuevoEstado = $(this).data('nuevo-estado');
        const nombre = $(this).data('nombre');

        let textoEstado = '';
        let iconoAlerta = '';

        switch (nuevoEstado) {
            case 'disponible':
                textoEstado = 'marcar como disponible';
                iconoAlerta = 'info';
                break;
            case 'mantenimiento':
                textoEstado = 'poner en mantenimiento';
                iconoAlerta = 'warning';
                break;
            case 'fuera_servicio':
                textoEstado = 'marcar fuera de servicio';
                iconoAlerta = 'error';
                break;
        }

        Swal.fire({
            title: `¿Desea ${textoEstado} este baño?`,
            text: `Baño: ${nombre}`,
            icon: iconoAlerta,
            showCancelButton: true,
            confirmButtonColor: themeColor('primary'),
            cancelButtonColor: themeColor('secondary'),
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'controllers/banos/cambiar_estado_bano_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: banoId,
                        nuevo_estado: nuevoEstado,
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
                    error: function () {
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

    // Limpiar modal al cerrarlo
    $('#modalBano').on('hidden.bs.modal', function () {
        $('#formBano')[0].reset();
    });
});
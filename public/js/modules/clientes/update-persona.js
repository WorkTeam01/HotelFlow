/* update - persona - mejorado.js - Script mejorado para validación del formulario de actualización
* 
* Este archivo proporciona validación en tiempo real para el formulario
* de actualización de clientes en el sistema de gestión de alojamiento.
*/

$(document).ready(function () {
    // Inicializar el formato del documento según el tipo seleccionado
    actualizarFormatoDocumento();

    // Inicializar Select2 para campos select
    initializeSelect2();

    // Manejar cambio en el estado (switch)
    const estadoSwitch = $('#estadoSwitch');
    const estadoLabel = $('#estadoLabel');
    const estadoInput = $('#estado');

    estadoSwitch.on('change', function () {
        if (this.checked) {
            estadoLabel.text('Activo');
            estadoInput.val('1');
        } else {
            estadoLabel.text('Inactivo');
            estadoInput.val('0');
        }
    });

    // Validación de número de documento según tipo
    $('#tipodocumento').on('change', function () {
        actualizarFormatoDocumento();
    });

    // Validación en tiempo real del nombre
    $('#nombre').on('input', function () {
        validarCampoTexto($(this));
    });

    // Validación en tiempo real del apellido paterno
    $('#apellidopaterno').on('input', function () {
        validarCampoTexto($(this));
    });

    // Validación de email
    $('#email').on('blur', function () {
        validarEmail($(this));
    });

    // Validación de número de documento
    $('#numdocumento').on('input', function () {
        validarDocumento($(this));
    });

    // Validación de fecha de nacimiento
    $('#fechanacimiento').on('change', function () {
        validarFechaNacimiento($(this));
    });

    // Validación del formulario completo antes de enviar
    $('#formCliente').on('submit', function (e) {
        if (!validarFormulario()) {
            e.preventDefault();
            mostrarMensajeError('Por favor, revise los campos marcados en rojo antes de continuar.');
        } else {
            // Mostrar indicador de carga
            mostrarCargando('Actualizando la información del cliente...');
        }
    });

    // Confirmación al cancelar edición
    $('a.btn-secondary').on('click', function (e) {
        // Si hay cambios en el formulario, pedir confirmación
        if (formularioModificado()) {
            e.preventDefault();
            confirmarSalir($(this).attr('href'));
        }
    });

    // Marcar el estado inicial del formulario
    marcarEstadoInicial();
});

/**
 * Guarda el estado inicial del formulario para detectar cambios
 */
function marcarEstadoInicial() {
    // Guardar el estado inicial de todos los campos del formulario
    $('#formCliente input, #formCliente select, #formCliente textarea').each(function () {
        $(this).data('valorInicial', $(this).val());
    });

    // Estado especial para checkbox
    $('#estadoSwitch').data('valorInicial', $('#estadoSwitch').prop('checked'));
}

/**
 * Verifica si el formulario ha sido modificado
 * @returns {boolean} True si hay cambios, False si no los hay
 */
function formularioModificado() {
    let modificado = false;

    // Verificar todos los campos excepto el switch
    $('#formCliente input:not(#estadoSwitch), #formCliente select, #formCliente textarea').each(function () {
        if ($(this).val() !== $(this).data('valorInicial')) {
            modificado = true;
            return false; // Salir del bucle
        }
    });

    // Verificar el switch por separado
    if (!modificado) {
        modificado = $('#estadoSwitch').prop('checked') !== $('#estadoSwitch').data('valorInicial');
    }

    return modificado;
}

/**
 * Muestra confirmación antes de salir si hay cambios
 * @param {string} destino - URL de destino
 */
function confirmarSalir(destino) {
    Swal.fire({
        title: '¿Abandonar cambios?',
        text: 'Hay cambios sin guardar. ¿Está seguro de que desea salir sin guardar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'No, permanecer'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = destino;
        }
    });
}

/**
 * Actualiza el formato y ayuda visual para el campo de documento según el tipo seleccionado
 */
function actualizarFormatoDocumento() {
    let tipo = $('#tipodocumento').val();
    let numDocInput = $('#numdocumento');
    let formatoHelp = $('#formatoDocumento');

    // Limpiar clases de validación
    numDocInput.removeClass('is-invalid is-valid');

    switch (tipo) {
        case 'DNI':
            numDocInput.attr('maxlength', '8');
            numDocInput.attr('pattern', '[0-9]{8}');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El DNI debe contener 8 dígitos numéricos.');
            break;
        case 'RUC':
            numDocInput.attr('maxlength', '11');
            numDocInput.attr('pattern', '[0-9]{11}');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El RUC debe contener 11 dígitos numéricos.');
            break;
        case 'Pasaporte':
            numDocInput.attr('maxlength', '12');
            numDocInput.removeAttr('pattern');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El pasaporte puede contener letras y números.');
            break;
        case 'CI':
            numDocInput.attr('maxlength', '10');
            numDocInput.removeAttr('pattern');
            formatoHelp.html('<i class="fas fa-info-circle"></i> La CI puede contener letras, números y guiones.');
            break;
        default:
            numDocInput.removeAttr('maxlength');
            numDocInput.removeAttr('pattern');
            formatoHelp.html('');
    }

    // Validar el campo si ya tiene valor
    if (numDocInput.val()) {
        validarDocumento(numDocInput);
    }
}

/**
 * Valida un campo de texto genérico
 * @param {jQuery} campo - El campo a validar
 * @returns {boolean} - True si es válido, false si no lo es
 */
function validarCampoTexto(campo) {
    if (campo.prop('required') && campo.val().trim() === '') {
        campo.addClass('is-invalid').removeClass('is-valid');
        return false;
    } else if (campo.val().trim() !== '') {
        campo.addClass('is-valid').removeClass('is-invalid');
    } else {
        campo.removeClass('is-invalid is-valid');
    }
    return true;
}

/**
 * Valida el formato del email
 * @param {jQuery} campo - El campo email a validar
 * @returns {boolean} - True si es válido, false si no lo es
 */
function validarEmail(campo) {
    const email = campo.val().trim();

    // Si está vacío y no es requerido, no validar
    if (email === '' && !campo.prop('required')) {
        campo.removeClass('is-invalid is-valid');
        return true;
    }

    // Si está vacío y es requerido, marcar como inválido
    if (email === '' && campo.prop('required')) {
        campo.addClass('is-invalid').removeClass('is-valid');
        return false;
    }

    // Validar formato de email
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(email)) {
        campo.addClass('is-invalid').removeClass('is-valid');
        return false;
    } else {
        campo.addClass('is-valid').removeClass('is-invalid');
        return true;
    }
}

/**
 * Valida el formato del documento según el tipo seleccionado
 * @param {jQuery} campo - El campo de documento a validar
 * @returns {boolean} - True si es válido, false si no lo es
 */
function validarDocumento(campo) {
    const tipo = $('#tipodocumento').val();
    const valor = campo.val().trim();

    // Si no hay tipo seleccionado o está vacío, no validar
    if (!tipo || valor === '') {
        if (campo.prop('required')) {
            campo.addClass('is-invalid').removeClass('is-valid');
            return false;
        } else {
            campo.removeClass('is-invalid is-valid');
            return true;
        }
    }

    let esValido = true;

    switch (tipo) {
        case 'DNI':
            esValido = /^[0-9]{8}$/.test(valor);
            break;
        case 'RUC':
            esValido = /^[0-9]{11}$/.test(valor);
            break;
        case 'Pasaporte':
            esValido = valor.length >= 5 && valor.length <= 12;
            break;
        case 'CI':
            esValido = valor.length >= 5 && valor.length <= 10;
            break;
    }

    if (esValido) {
        campo.addClass('is-valid').removeClass('is-invalid');
    } else {
        campo.addClass('is-invalid').removeClass('is-valid');
    }

    return esValido;
}

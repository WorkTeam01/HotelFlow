/**
 * create-persona-mejorado.js - Script mejorado para validación de formularios de clientes
 * 
 * Este archivo proporciona validación en tiempo real y mejorada para el formulario
 * de registro de clientes en el sistema de gestión de alojamiento.
 */

$(document).ready(function () {
    // Inicializar el formato del documento según el tipo seleccionado
    actualizarFormatoDocumento();

    // Inicializar Select2 para los campos select
    initializeSelect2();

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
        }
    });
});

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
            numDocInput.attr('placeholder', 'Ingrese 8 dígitos');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El DNI debe contener 8 dígitos numéricos.');
            break;
        case 'RUC':
            numDocInput.attr('maxlength', '11');
            numDocInput.attr('pattern', '[0-9]{11}');
            numDocInput.attr('placeholder', 'Ingrese 11 dígitos');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El RUC debe contener 11 dígitos numéricos.');
            break;
        case 'Pasaporte':
            numDocInput.attr('maxlength', '12');
            numDocInput.removeAttr('pattern');
            numDocInput.attr('placeholder', 'Ingrese el número de pasaporte');
            formatoHelp.html('<i class="fas fa-info-circle"></i> El pasaporte puede contener letras y números.');
            break;
        case 'CI':
            numDocInput.attr('maxlength', '10');
            numDocInput.removeAttr('pattern');
            numDocInput.attr('placeholder', 'Ingrese la cédula de identidad');
            formatoHelp.html('<i class="fas fa-info-circle"></i> La CI puede contener letras, números y guiones.');
            break;
        default:
            numDocInput.removeAttr('maxlength');
            numDocInput.removeAttr('pattern');
            numDocInput.attr('placeholder', 'Ingrese el número de documento');
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

/**
 * Valida que la fecha de nacimiento no sea futura
 * @param {jQuery} campo - El campo de fecha a validar
 * @returns {boolean} - True si es válido, false si no lo es
 */
function validarFechaNacimiento(campo) {
    const valor = campo.val();

    // Si está vacío y no es requerido, no validar
    if (valor === '' && !campo.prop('required')) {
        campo.removeClass('is-invalid is-valid');
        return true;
    }

    // Si está vacío y es requerido, marcar como inválido
    if (valor === '' && campo.prop('required')) {
        campo.addClass('is-invalid').removeClass('is-valid');
        return false;
    }

    const fechaNacimiento = new Date(valor);
    const hoy = new Date();

    // Verificar si es fecha futura
    if (fechaNacimiento > hoy) {
        campo.addClass('is-invalid').removeClass('is-valid');
        return false;
    }

    // Verificar si es menor de edad (opcional)
    const edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const mesesDiferencia = hoy.getMonth() - fechaNacimiento.getMonth();

    if (mesesDiferencia < 0 || (mesesDiferencia === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }

    if (edad < 18) {
        // Aquí puedes decidir si marcar como inválido o solo mostrar una advertencia
        // campo.addClass('is-invalid').removeClass('is-valid');
        // return false;

        // O simplemente mostrar una alerta pero considerar válido
        mostrarAdvertencia('El cliente es menor de edad (' + edad + ' años). Verifique si requiere permiso especial.');
    }

    campo.addClass('is-valid').removeClass('is-invalid');
    return true;
}

/**
 * Valida todo el formulario antes de enviar
 * @returns {boolean} - True si todo es válido, false si hay errores
 */
function validarFormulario() {
    let formValido = true;

    // Validar campos obligatorios
    $('#formCliente input[required], #formCliente select[required]').each(function () {
        const $campo = $(this);

        if ($campo.is('select')) {
            if ($campo.val() === '') {
                $campo.addClass('is-invalid').removeClass('is-valid');
                formValido = false;
            } else {
                $campo.addClass('is-valid').removeClass('is-invalid');
            }
        } else if ($campo.attr('type') === 'email') {
            if (!validarEmail($campo)) formValido = false;
        } else if ($campo.attr('id') === 'numdocumento') {
            if (!validarDocumento($campo)) formValido = false;
        } else {
            if (!validarCampoTexto($campo)) formValido = false;
        }
    });

    // Validar fecha de nacimiento si está presente
    if ($('#fechanacimiento').val() !== '') {
        if (!validarFechaNacimiento($('#fechanacimiento'))) {
            formValido = false;
        }
    }

    return formValido;
}

/**
 * Muestra un mensaje de error usando SweetAlert
 * @param {string} mensaje - El mensaje a mostrar
 */
function mostrarMensajeError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error de validación',
        text: mensaje
    });
}

/**
 * Muestra un mensaje de advertencia usando SweetAlert
 * @param {string} mensaje - El mensaje a mostrar
 */
function mostrarAdvertencia(mensaje) {
    Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: mensaje
    });
}

/**
 * Muestra un mensaje de éxito usando SweetAlert
 * @param {string} mensaje - El mensaje a mostrar
 */
function mostrarExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: mensaje
    });
}
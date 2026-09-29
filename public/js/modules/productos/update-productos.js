/**
 * Formulario de edición de productos.
 * Toda la lógica compartida vive en producto-form.js.
 */
$(document).ready(function () {
    ProductoForm.init({
        form: '#formEditarProducto',
        tituloCarga: 'Actualizando producto...',
        textoCarga: 'Por favor espere mientras se guardan los cambios'
    });
});

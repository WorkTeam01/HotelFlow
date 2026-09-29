/**
 * Formulario de alta de productos.
 * Toda la lógica compartida vive en producto-form.js.
 */
$(document).ready(function () {
    ProductoForm.init({
        form: '#formCrearProducto',
        tituloCarga: 'Guardando producto...',
        textoCarga: 'Por favor espere mientras se guarda la información'
    });
});

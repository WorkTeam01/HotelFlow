document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formEditarServicio');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function () {
        mostrarCarga('Guardando...', 'Espere un momento mientras se guardan los cambios.');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Manejar botón de completar compra
    const btnCompletar = document.querySelector('.btn-completar');
    if (btnCompletar) {
        btnCompletar.addEventListener('click', function() {
            const compraId = this.dataset.id;
            confirmarAccion(compraId, 'completar');
        });
    }

    // Manejar botón de cancelar compra
    const btnCancelar = document.querySelector('.btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            const compraId = this.dataset.id;
            confirmarAccion(compraId, 'cancelar');
        });
    }

    function confirmarAccion(id, accion) {
        const titulo = accion === 'completar' ?
            '¿Marcar compra como completada?' :
            '¿Cancelar esta compra?';

        const texto = accion === 'completar' ?
            'La compra se marcará como finalizada y no podrá ser modificada.' :
            'La compra será cancelada y el stock de productos será revertido.';

        const confirmButtonText = accion === 'completar' ? 'Sí, completar' : 'Sí, cancelar';
        const confirmButtonColor = accion === 'completar' ? '#28a745' : '#dc3545';

        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${BASE_URL}controllers/compras/cambiar_estado_compra.php?id=${id}&accion=${accion}&csrf_token=${CSRF_TOKEN}`;
            }
        });
    }
});

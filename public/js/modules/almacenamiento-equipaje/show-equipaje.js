document.addEventListener('DOMContentLoaded', function () {
    // Manejar botones de cambio de estado
    const botonesEstado = document.querySelectorAll('.cambiar-estado');
    botonesEstado.forEach(function (boton) {
        boton.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const estado = this.getAttribute('data-estado');
            const icono = this.getAttribute('data-icono');

            let titulo = '';
            let texto = '';
            let confirmButtonColor = '';

            switch (estado) {
                case 'retirado':
                    titulo = '¿Marcar como Retirado?';
                    texto = 'Esta acción registrará que el equipaje ha sido entregado al cliente y no se podrá deshacer.';
                    confirmButtonColor = '#28a745';
                    break;
                case 'perdido':
                    titulo = '¿Marcar como Perdido?';
                    texto = 'Esta acción registrará que el equipaje ha sido perdido o no puede ser localizado.';
                    confirmButtonColor = '#dc3545';
                    break;
                case 'dañado':
                    titulo = '¿Marcar como Dañado?';
                    texto = 'Esta acción registrará que el equipaje ha sufrido daños.';
                    confirmButtonColor = '#343a40';
                    break;
                default:
                    titulo = '¿Cambiar estado?';
                    texto = 'Esta acción cambiará el estado del equipaje.';
                    confirmButtonColor = '#3085d6';
            }

            Swal.fire({
                title: titulo,
                text: texto,
                icon: icono,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carga
                    Swal.fire({
                        title: 'Procesando...',
                        html: 'Por favor espere mientras se actualiza el estado',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Redireccionar a la página de cambio de estado
                    window.location.href = url;
                }
            });
        });
    });

    // Persistir la pestaña activa del detalle (mismo patrón que usuarios/show)
    const tabs = document.querySelectorAll('a[data-toggle="pill"]');
    tabs.forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            try {
                sessionStorage.setItem('lastEquipajeDetailTab', e.target.id);
            } catch (err) {
                // sessionStorage no disponible: se ignora
            }
        });
    });

    try {
        const lastTab = sessionStorage.getItem('lastEquipajeDetailTab');
        if (lastTab && document.getElementById(lastTab)) {
            const tabEl = document.getElementById(lastTab);
            if (window.bootstrap && typeof window.bootstrap.Tab === 'function') {
                new bootstrap.Tab(tabEl).show();
            } else if (window.$ && typeof window.$(tabEl).tab === 'function') {
                window.$(tabEl).tab('show');
            }
        }
    } catch (err) {
        // Sin persistencia de tab: se usa la pestaña por defecto
    }
});

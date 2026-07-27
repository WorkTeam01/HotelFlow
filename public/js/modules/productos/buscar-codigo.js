document.addEventListener('DOMContentLoaded', function () {
    const formEscaner = document.getElementById('form-escaner');
    const busquedaActual = JSON.parse(formEscaner.dataset.busqueda);

    // Mantener el foco en el campo de código
    const codigoInput = document.getElementById('codigo');
    codigoInput.focus();

    // Enviar formulario automáticamente al escanear (cuando se recibe un Enter)
    codigoInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            formEscaner.submit();
        }
    });

    // Historial de búsquedas en localStorage
    let historialBusquedas = [];

    // Cargar historial existente
    if (localStorage.getItem('historialBusquedasProductos')) {
        try {
            historialBusquedas = JSON.parse(localStorage.getItem('historialBusquedasProductos'));
        } catch (e) {
            console.error('Error al cargar historial:', e);
            historialBusquedas = [];
        }
    }

    // Añadir búsqueda actual al historial (dato inyectado por la vista según el POST recibido)
    if (busquedaActual !== null) {
        const nuevaBusqueda = {
            codigo: busquedaActual.codigo,
            encontrado: busquedaActual.encontrado,
            timestamp: new Date().toISOString(),
            nombre: busquedaActual.nombre
        };

        // Comprobar si ya existe en el historial
        const existeIndex = historialBusquedas.findIndex(item => item.codigo === nuevaBusqueda.codigo);
        if (existeIndex !== -1) {
            // Actualizar entrada existente
            historialBusquedas.splice(existeIndex, 1);
        }

        // Añadir al inicio
        historialBusquedas.unshift(nuevaBusqueda);

        // Limitar a 10 entradas
        if (historialBusquedas.length > 10) {
            historialBusquedas = historialBusquedas.slice(0, 10);
        }

        // Guardar en localStorage
        localStorage.setItem('historialBusquedasProductos', JSON.stringify(historialBusquedas));
    }

    // Escapa texto (código/nombre de producto, vienen de datos escaneados/almacenados) antes de insertarlo como HTML
    function escapeHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    // Mostrar historial
    function mostrarHistorial() {
        const contenedor = document.getElementById('historial-busquedas');

        if (historialBusquedas.length === 0) {
            contenedor.innerHTML = '<p class="text-muted">No hay búsquedas recientes.</p>';
            return;
        }

        let html = '<div class="table-responsive">';
        html += '<table class="table table-striped table-sm">';
        html += '<thead><tr><th>Código</th><th>Producto</th><th>Fecha</th><th>Resultado</th><th>Acción</th></tr></thead>';
        html += '<tbody>';

        historialBusquedas.forEach(item => {
            const fecha = new Date(item.timestamp).toLocaleString();
            html += `<tr>
            <td>${escapeHtml(item.codigo)}</td>
            <td>${escapeHtml(item.nombre) || '-'}</td>
            <td>${fecha}</td>
            <td>${item.encontrado ? '<span class="badge badge-success">Encontrado</span>' : '<span class="badge badge-warning">No encontrado</span>'}</td>
            <td>
                <button type="button" class="btn btn-primary btn-sm btn-buscar-codigo" data-codigo="${escapeHtml(item.codigo)}">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </td>
        </tr>`;
        });

        html += '</tbody></table></div>';
        contenedor.innerHTML = html;

        // Añadir eventos a los botones de búsqueda
        document.querySelectorAll('.btn-buscar-codigo').forEach(btn => {
            btn.addEventListener('click', function () {
                codigoInput.value = this.getAttribute('data-codigo');
                document.getElementById('form-escaner').submit();
            });
        });
    }

    // Mostrar historial inicial
    mostrarHistorial();

    // Limpiar historial
    document.getElementById('limpiar-historial').addEventListener('click', function () {
        if (confirm('¿Está seguro de limpiar el historial de búsquedas?')) {
            historialBusquedas = [];
            localStorage.removeItem('historialBusquedasProductos');
            mostrarHistorial();
        }
    });

    // Mantener el foco en el campo de código después de hacer clic en cualquier lugar
    document.addEventListener('click', function () {
        setTimeout(() => codigoInput.focus(), 100);
    });
});

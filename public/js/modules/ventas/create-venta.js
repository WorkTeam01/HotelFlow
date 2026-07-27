/**
 * ventas-form.js - Scripts para el formulario de ventas
 * 
 * Este archivo contiene las funciones y eventos para el funcionamiento
 * del formulario de creación de ventas.
 * 
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function () {
    const formVenta = document.getElementById('form-venta');
    const productosDisponibles = JSON.parse(formVenta.dataset.productos);
    const clientesDisponibles = JSON.parse(formVenta.dataset.clientes);

    // Referencias a elementos del DOM
    const metodoPagoSelect = document.getElementById('metodopago');
    const pagoRecibidoContainer = document.getElementById('pago-recibido-container');
    const cambioContainer = document.getElementById('cambio-container');
    const pagorecibidoInput = document.getElementById('pagorecibido');
    const cambioInput = document.getElementById('cambio');
    const cambioHidden = document.getElementById('cambio-hidden');
    const totalVentaSpan = document.getElementById('total-venta');
    const buscarProductoInput = document.getElementById('buscar-producto');
    const btnBuscarProducto = document.getElementById('btn-buscar-producto');

    // Inicialización
    togglePagoFields();

    // Inicializar Select2
    initializeSelect2();

    // Mostrar/ocultar campos según método de pago
    function togglePagoFields() {
        const metodo = metodoPagoSelect.value;

        if (metodo === 'Efectivo') {
            pagoRecibidoContainer.style.display = 'block';
            cambioContainer.style.display = 'block';
            pagorecibidoInput.required = true;
            calcularCambio();
        } else if (metodo === 'QR') {
            pagoRecibidoContainer.style.display = 'block';
            cambioContainer.style.display = 'none';
            pagorecibidoInput.required = true;
            pagorecibidoInput.value = document.getElementById('total-venta').textContent || '0.00';
            cambioInput.value = '0.00';
            cambioHidden.value = '0.00';
        } else {
            pagoRecibidoContainer.style.display = 'none';
            cambioContainer.style.display = 'none';
            pagorecibidoInput.required = false;
            pagorecibidoInput.value = '0.00';
            cambioInput.value = '0.00';
            cambioHidden.value = '0.00';
        }
    }

    // Calcular cambio automáticamente
    function calcularCambio() {
        const total = parseFloat(totalVentaSpan.textContent) || 0;
        const pagoRecibido = parseFloat(pagorecibidoInput.value) || 0;
        const cambio = pagoRecibido - total;

        const cambioFormateado = cambio >= 0 ? cambio.toFixed(2) : '0.00';
        cambioInput.value = cambioFormateado;
        cambioHidden.value = cambioFormateado;

        if (pagoRecibido < total) {
            pagorecibidoInput.classList.add('is-invalid');
            cambioInput.classList.add('text-danger');
        } else {
            pagorecibidoInput.classList.remove('is-invalid');
            cambioInput.classList.remove('text-danger');
        }
    }

    // Buscar producto por código
    function buscarProductoPorCodigo(codigo) {
        return productosDisponibles.find(producto =>
            producto.codigo && producto.codigo.toLowerCase() === codigo.toLowerCase()
        );
    }

    // Agregar producto al detalle
    function agregarProductoAlDetalle(producto) {
        // Verificar si el producto ya está en el detalle
        const productosEnDetalle = document.querySelectorAll('.idproducto');
        for (let i = 0; i < productosEnDetalle.length; i++) {
            if (productosEnDetalle[i].value == producto.idproducto) {
                Swal.fire('Producto ya agregado', 'Este producto ya está en el detalle de la venta', 'info');
                return;
            }
        }

        const filaBase = document.getElementById('fila-base');
        const nuevaFila = filaBase.cloneNode(true);

        nuevaFila.style.display = '';
        nuevaFila.classList.add('fila-producto'); // Añadir clase para identificarla

        // Llenar datos del producto
        nuevaFila.querySelector('.idproducto').value = producto.idproducto;
        nuevaFila.querySelector('.nombre-producto').value = producto.nombre;
        nuevaFila.querySelector('.codigo-producto').value = producto.codigo;
        nuevaFila.querySelector('.cantidad').value = 1;
        nuevaFila.querySelector('.cantidad').max = producto.stock;
        nuevaFila.querySelector('.precio').value = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.descuento').value = '0.00';
        nuevaFila.querySelector('.subtotal').textContent = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.stock-disponible').textContent = `Disponible: ${producto.stock}`;

        // Agregar eventos
        nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', eliminarFila);
        agregarEventosCalculo(nuevaFila);

        document.querySelector('#tabla-productos tbody').appendChild(nuevaFila);

        // Calcular totales
        calcularTotalesVenta();

        // Limpiar campo de búsqueda
        buscarProductoInput.value = '';
        buscarProductoInput.focus();
    }

    // Eliminar fila
    function eliminarFila() {
        if (document.querySelectorAll('tr.fila-producto').length > 1) {
            this.closest('tr').remove();
            calcularTotalesVenta();
        } else {
            Swal.fire('Atención', 'Debe haber al menos un producto en la venta', 'warning');
        }
    }

    // Agregar eventos de cálculo
    function agregarEventosCalculo(fila) {
        const cantidad = fila.querySelector('.cantidad');
        const precio = fila.querySelector('.precio');
        const descuento = fila.querySelector('.descuento');

        cantidad.addEventListener('input', function () {
            const maxStock = parseInt(this.max) || 0;
            if (parseInt(this.value) > maxStock) {
                this.value = maxStock;
                Swal.fire('Stock insuficiente', `Máximo disponible: ${maxStock}`, 'warning');
            }
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });

        precio.addEventListener('input', () => {
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });
        descuento.addEventListener('input', () => {
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });
    }

    // Calcular subtotal
    function calcularSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const descuento = parseFloat(fila.querySelector('.descuento').value) || 0;
        const subtotal = (cantidad * precio) - descuento;
        fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);
    }

    // Calcular totales
    function calcularTotalesVenta() {
        let subtotal = 0,
            descuentoTotal = 0,
            total = 0;

        document.querySelectorAll('tr.fila-producto').forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio').value) || 0;
            const descuento = parseFloat(fila.querySelector('.descuento').value) || 0;

            subtotal += cantidad * precio;
            descuentoTotal += descuento;
            total += (cantidad * precio) - descuento;
        });

        document.getElementById('subtotal-venta').textContent = subtotal.toFixed(2);
        document.getElementById('descuento-total').textContent = descuentoTotal.toFixed(2);
        document.getElementById('total-venta').textContent = total.toFixed(2);
        document.getElementById('totalventa-hidden').value = total.toFixed(2);

        if (metodoPagoSelect.value === 'Efectivo') {
            calcularCambio();
        } else if (metodoPagoSelect.value === 'QR') {
            pagorecibidoInput.value = total.toFixed(2);
            cambioInput.value = '0.00';
            cambioHidden.value = '0.00';
        }
    }

    // Función para buscar clientes
    function buscarClientes(termino) {
        termino = termino.toLowerCase();
        return clientesDisponibles.filter(cliente =>
            (cliente.numdocumento && cliente.numdocumento.toLowerCase().includes(termino)) ||
            (cliente.nombre && cliente.nombre.toLowerCase().includes(termino)) ||
            (cliente.apellidopaterno && cliente.apellidopaterno.toLowerCase().includes(termino)) ||
            (cliente.apellidomaterno && cliente.apellidomaterno.toLowerCase().includes(termino))
        );
    }

    // Mostrar sugerencias
    function mostrarSugerencias(clientes) {
        const contenedor = document.getElementById('sugerencias-clientes');
        contenedor.innerHTML = '';

        if (clientes.length === 0) {
            contenedor.style.display = 'none';
            return;
        }

        clientes.forEach(cliente => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
            <strong>${cliente.numdocumento}</strong> - 
            ${cliente.nombre} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}
        `;
            item.addEventListener('click', () => {
                seleccionarCliente(cliente);
            });
            contenedor.appendChild(item);
        });

        contenedor.style.display = 'block';
    }

    // Seleccionar cliente
    function seleccionarCliente(cliente) {
        document.getElementById('idcliente').value = cliente.idpersona;
        document.getElementById('buscar-cliente').value = cliente.numdocumento;
        document.getElementById('nombre-cliente').textContent =
            `${cliente.nombre} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}`;
        document.getElementById('info-cliente-seleccionado').style.display = 'block';
        document.getElementById('sugerencias-clientes').style.display = 'none';
    }

    // Evento para buscar producto
    btnBuscarProducto.addEventListener('click', function () {
        const codigo = buscarProductoInput.value.trim();
        if (!codigo) {
            Swal.fire('Error', 'Ingrese un código de producto', 'error');
            return;
        }

        const producto = buscarProductoPorCodigo(codigo);
        if (producto) {
            agregarProductoAlDetalle(producto);
        } else {
            Swal.fire('Producto no encontrado', 'No se encontró un producto con ese código', 'error');
        }
    });

    // También buscar al presionar Enter
    buscarProductoInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnBuscarProducto.click();
        }
    });

    // Evento para buscar cliente
    document.getElementById('buscar-cliente').addEventListener('input', function () {
        if (this.value.length >= 2) {
            const resultados = buscarClientes(this.value);
            mostrarSugerencias(resultados);
        } else {
            document.getElementById('sugerencias-clientes').style.display = 'none';
        }
    });

    // Evento para quitar cliente seleccionado
    document.getElementById('quitar-cliente').addEventListener('click', function () {
        document.getElementById('idcliente').value = '';
        document.getElementById('buscar-cliente').value = '';
        document.getElementById('info-cliente-seleccionado').style.display = 'none';
        document.getElementById('buscar-cliente').focus();
    });

    // Ocultar sugerencias al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#buscar-cliente') && !e.target.closest('#sugerencias-clientes')) {
            document.getElementById('sugerencias-clientes').style.display = 'none';
        }
    });

    // Event listeners
    metodoPagoSelect.addEventListener('change', function () {
        togglePagoFields();
        if (this.value === 'QR') {
            pagorecibidoInput.value = document.getElementById('total-venta').textContent || '0.00';
        }
    });

    pagorecibidoInput.addEventListener('input', function () {
        if (metodoPagoSelect.value === 'Efectivo') {
            calcularCambio();
        }
    });

    // Validación manual del formulario
    document.getElementById('form-venta').addEventListener('submit', function (e) {
        e.preventDefault();

        // Resetear errores
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        let isValid = true;
        const errorMessages = [];

        // Validar campos principales
        const camposRequeridos = [{
            id: 'idcliente',
            msg: 'Seleccione un cliente'
        },
        {
            id: 'fechaventa',
            msg: 'Ingrese una fecha válida'
        },
        {
            id: 'metodopago',
            msg: 'Seleccione un método de pago'
        }
        ];

        camposRequeridos.forEach(campo => {
            const element = document.getElementById(campo.id);
            if (!element.value) {
                element.classList.add('is-invalid');
                errorMessages.push(campo.msg);
                isValid = false;
            }
        });

        // Validar pago recibido para Efectivo/QR
        if (metodoPagoSelect.value === 'Efectivo' || metodoPagoSelect.value === 'QR') {
            const total = parseFloat(document.getElementById('total-venta').textContent) || 0;
            const pagoRecibido = parseFloat(pagorecibidoInput.value) || 0;

            if (!pagorecibidoInput.value || pagoRecibido <= 0) {
                pagorecibidoInput.classList.add('is-invalid');
                errorMessages.push('Ingrese un monto válido para el pago recibido');
                isValid = false;
            } else if (pagoRecibido < total && metodoPagoSelect.value === 'Efectivo') {
                pagorecibidoInput.classList.add('is-invalid');
                errorMessages.push('El pago recibido no cubre el total de la venta');
                isValid = false;
            }
        }

        // Validar productos
        const productosEnDetalle = document.querySelectorAll('tr.fila-producto');
        if (productosEnDetalle.length === 0) {
            errorMessages.push('Debe agregar al menos un producto a la venta');
            isValid = false;
        }

        productosEnDetalle.forEach((fila, index) => {
            const cantidad = fila.querySelector('.cantidad');
            const precio = fila.querySelector('.precio');
            let filaValida = true;

            if (!cantidad.value || parseFloat(cantidad.value) <= 0) {
                cantidad.classList.add('is-invalid');
                errorMessages.push(`Fila ${index + 1}: Ingrese una cantidad válida`);
                filaValida = false;
                isValid = false;
            }

            if (!precio.value || parseFloat(precio.value) <= 0) {
                precio.classList.add('is-invalid');
                errorMessages.push(`Fila ${index + 1}: Ingrese un precio válido`);
                filaValida = false;
                isValid = false;
            }
        });

        // Mostrar errores o confirmar
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                html: errorMessages.join('<br>'),
            });

            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                setTimeout(() => firstError.focus(), 300);
            }
            return;
        }

        // Confirmación final
        Swal.fire({
            title: '¿Confirmar Venta?',
            html: `<div class="text-left">
                <p><strong>Total:</strong> $${document.getElementById('total-venta').textContent}</p>
                <p>¿Está seguro de registrar esta venta?</p>
              </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) this.submit();
        });
    });
});
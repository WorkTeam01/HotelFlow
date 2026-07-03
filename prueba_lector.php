<?php
// Archivo: prueba_lector.php
// Esta página es SOLO para pruebas, no requiere autenticación ni integración completa
// Colócala en la raíz del proyecto o en una carpeta accesible

// Conexión a la base de datos (opcional, solo si quieres probar la búsqueda real)
require_once __DIR__ . '/config/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Lector de Códigos de Barras - HotelFlow</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f4f6f9;
        }

        .result-box {
            min-height: 200px;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            background-color: #fff;
        }

        .scan-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .header-title {
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .card {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .product-found {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .product-not-found {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-title text-center">
            <h2><i class="fas fa-barcode"></i> Prueba de Lector de Códigos - HotelFlow</h2>
            <p class="mb-0">Herramienta para verificar el funcionamiento del lector de códigos de barras</p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-search"></i> Escanear Código de Barras</h5>
                    </div>
                    <div class="card-body">
                        <form id="scan-form">
                            <div class="form-group">
                                <label for="codigo"><i class="fas fa-barcode"></i> Escanee el código del producto:</label>
                                <input type="text" class="form-control form-control-lg" id="codigo"
                                    placeholder="Coloque el cursor aquí y escanee..." autofocus>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> El código escaneado aparecerá aquí y se procesará automáticamente.
                                </small>
                            </div>
                        </form>

                        <div class="result-box">
                            <h5><i class="fas fa-clipboard-check"></i> Información del último escaneo:</h5>
                            <div id="scan-result">
                                <p class="text-muted">Aquí se mostrará el resultado del escaneo...</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5><i class="fas fa-history"></i> Historial de escaneos:</h5>
                            <div class="scan-history" id="scan-history">
                                <p class="text-muted">Aquí aparecerá el historial de escaneos...</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" id="clear-btn">
                            <i class="fas fa-trash"></i> Limpiar Historial
                        </button>
                        <button type="button" class="btn btn-primary float-right" id="test-db-btn">
                            <i class="fas fa-database"></i> Probar Conexión a BD
                        </button>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle"></i> Instrucciones:</h5>
                    <ol>
                        <li>Conecte el lector de códigos de barras al puerto USB de su computadora.</li>
                        <li>Asegúrese de que el cursor esté en el campo de entrada (haga clic en él si es necesario).</li>
                        <li>Escanee un código de barras de cualquier producto.</li>
                        <li>El código aparecerá en el campo y se procesará automáticamente.</li>
                        <li>Si presiona el botón "Probar Conexión a BD", el sistema intentará buscar el producto en la base de datos.</li>
                        <li>Esta página es solo para pruebas y no modifica la base de datos.</li>
                    </ol>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Verificación del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Configuración de Entrada</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Enfoque automático
                                                <span class="badge badge-success">Activo</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Detección de Enter
                                                <span class="badge badge-success">Activo</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Captura de entrada
                                                <span class="badge badge-success">Activo</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Estado del Sistema</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                JavaScript
                                                <span class="badge badge-success">Funcionando</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Manejo de eventos
                                                <span class="badge badge-success">Activo</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Conexión a BD
                                                <span class="badge badge-warning" id="db-status">No probado</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enfoque automático en el campo de entrada
            $('#codigo').focus();

            // Historial de escaneos
            let scanHistory = [];

            // Manejar el evento de escaneo (cuando se presiona Enter)
            $('#codigo').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    const codigo = $(this).val().trim();
                    if (codigo) {
                        processBarcode(codigo);
                        $(this).val(''); // Limpiar el campo
                    }
                }
            });

            // Procesar el código de barras
            function processBarcode(codigo) {
                // Registrar hora del escaneo
                const now = new Date();
                const timestamp = now.toLocaleTimeString();

                // Guardar en historial
                scanHistory.unshift({
                    codigo: codigo,
                    timestamp: timestamp,
                    tipo: determineCodeType(codigo)
                });

                // Actualizar resultado
                $('#scan-result').html(`
                    <div class="alert alert-success">
                        <strong>Código escaneado:</strong> ${codigo}<br>
                        <strong>Hora:</strong> ${timestamp}<br>
                        <strong>Longitud del código:</strong> ${codigo.length} caracteres<br>
                        <strong>Tipo probable:</strong> ${determineCodeType(codigo)}
                    </div>
                `);

                // Actualizar historial
                updateHistory();

                // Volver a enfocar el campo
                $('#codigo').focus();
            }

            // Determinar tipo de código
            function determineCodeType(code) {
                // Lógica simple para determinar tipo de código
                if (/^\d{8}$/.test(code)) return "EAN-8";
                if (/^\d{13}$/.test(code)) return "EAN-13";
                if (/^\d{12}$/.test(code)) return "UPC-A";
                if (/^\d{14}$/.test(code)) return "GTIN-14";
                if (/^[0-9A-Z\-\.\/\+]{1,48}$/.test(code)) return "CODE-128";
                return "Desconocido";
            }

            // Actualizar historial en pantalla
            function updateHistory() {
                if (scanHistory.length === 0) {
                    $('#scan-history').html('<p class="text-muted">No hay escaneos registrados...</p>');
                    return;
                }

                let html = '<div class="list-group">';
                scanHistory.forEach((scan, index) => {
                    html += `
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${scan.codigo} (${scan.tipo})</h6>
                                <small>${scan.timestamp}</small>
                            </div>
                            <small>Escaneo #${index + 1}</small>
                        </div>
                    `;
                });
                html += '</div>';

                $('#scan-history').html(html);
            }

            // Limpiar historial
            $('#clear-btn').click(function() {
                scanHistory = [];
                updateHistory();
                $('#scan-result').html('<p class="text-muted">Aquí se mostrará el resultado del escaneo...</p>');
            });

            // Probar conexión a la base de datos
            $('#test-db-btn').click(function() {
                const codigo = $('#codigo').val().trim();
                if (!codigo) {
                    alert('Por favor, escanee o ingrese un código primero');
                    $('#codigo').focus();
                    return;
                }

                // Mostrar estado de carga
                $('#db-status').removeClass('badge-warning badge-success badge-danger')
                    .addClass('badge-info')
                    .text('Probando...');

                // Realizar petición AJAX para verificar si el producto existe
                $.ajax({
                    url: 'prueba_producto_db.php',
                    type: 'POST',
                    data: {
                        codigo: codigo
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#db-status').removeClass('badge-warning badge-info badge-danger')
                                .addClass('badge-success')
                                .text('Conectado');

                            // Mostrar información del producto
                            $('#scan-result').html(`
                                <div class="alert alert-success product-found">
                                    <h5><i class="fas fa-check-circle"></i> Producto encontrado en la base de datos</h5>
                                    <strong>Código:</strong> ${response.producto.codigo}<br>
                                    <strong>Nombre:</strong> ${response.producto.nombre}<br>
                                    <strong>Precio:</strong> Bs ${parseFloat(response.producto.precioventa).toFixed(2)}<br>
                                    <strong>Stock:</strong> ${response.producto.stock} unidades<br>
                                </div>
                            `);
                        } else {
                            $('#db-status').removeClass('badge-warning badge-info badge-danger')
                                .addClass('badge-success')
                                .text('Conectado');

                            // Mostrar mensaje de producto no encontrado
                            $('#scan-result').html(`
                                <div class="alert alert-danger product-not-found">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Producto no encontrado</h5>
                                    <p>No se encontró ningún producto con el código: <strong>${codigo}</strong></p>
                                    <p>La conexión a la base de datos funciona correctamente, pero el código no está registrado.</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#db-status').removeClass('badge-warning badge-info badge-success')
                            .addClass('badge-danger')
                            .text('Error');

                        // Mostrar mensaje de error
                        $('#scan-result').html(`
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-circle"></i> Error de conexión</h5>
                                <p>No se pudo conectar a la base de datos o hubo un error en la consulta.</p>
                                <p>Error: ${error}</p>
                            </div>
                        `);
                    }
                });
            });

            // Mantener el foco en el campo de entrada
            $(document).click(function() {
                setTimeout(function() {
                    $('#codigo').focus();
                }, 100);
            });
        });
    </script>
</body>

</html>
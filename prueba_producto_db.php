<?php
// Archivo: test_product_db.php
// Este archivo complementa a prueba_lector.php para verificar la conexión a la BD

// Cabeceras para respuesta JSON
header('Content-Type: application/json');

// Verificar que se recibió un código
if (!isset($_POST['codigo']) || empty($_POST['codigo'])) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se proporcionó un código de barras'
    ]);
    exit;
}

$codigo = trim($_POST['codigo']);

try {
    // Incluir archivo de conexión
    require_once __DIR__ . '/config/conexion.php';
    $conexion = Conexion::getInstance()->getConnection();

    // Consultar si existe el producto con ese código
    $stmt = $conexion->prepare("SELECT idproducto, codigo, nombre, precioventa, stock FROM productos WHERE codigo = :codigo");
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();

    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        // Producto encontrado
        echo json_encode([
            'success' => true,
            'mensaje' => 'Producto encontrado',
            'producto' => $producto
        ]);
    } else {
        // Producto no encontrado
        echo json_encode([
            'success' => false,
            'mensaje' => 'No se encontró ningún producto con el código: ' . $codigo
        ]);
    }
} catch (PDOException $e) {
    // Error de conexión o consulta
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}

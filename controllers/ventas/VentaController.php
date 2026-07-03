<?php

/**
 * Controlador de Ventas
 * 
 * Gestiona las operaciones relacionadas con las ventas
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

class VentaController
{
    /**
     * Modelo de Venta
     * @var Venta
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Venta
        require_once __DIR__ . '/../../models/Venta.php';
        $this->modelo = new Venta();
    }

    /**
     * Muestra la lista de ventas
     */
    public function index()
    {
        // Obtener todas las ventas
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear una nueva venta
     */
    public function crear()
    {
        // Incluir modelos necesarios
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Persona.php';
        require_once __DIR__ . '/../../models/Usuario.php';

        // Obtener productos, clientes y usuarios para los selects
        $productoModel = new Producto();
        $personaModel = new Persona();
        $usuarioModel = new Usuario();

        $datos = [
            'productos' => $productoModel->getAll(),
            'clientes' => $personaModel->getAll(),
            'usuarios' => $usuarioModel->getAll()
        ];

        return $datos;
    }

    /**
     * Prepara los datos de la venta desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosVenta($post_data)
    {
        $datos = [
            'idcliente' => isset($post_data['idcliente']) ? (int)$post_data['idcliente'] : null,
            'idusuario' => isset($post_data['idusuario']) ? (int)$post_data['idusuario'] : 0,
            'totalventa' => isset($post_data['totalventa']) ? (float)$post_data['totalventa'] : 0,
            'fechaventa' => isset($post_data['fechaventa']) ? trim($post_data['fechaventa']) : date('Y-m-d'),
            'metodopago' => isset($post_data['metodopago']) ? trim($post_data['metodopago']) : 'Efectivo',
            'pagorecibido' => isset($post_data['pagorecibido']) ? (float)$post_data['pagorecibido'] : 0,
            'cambio' => isset($post_data['cambio']) ? (float)$post_data['cambio'] : 0,
            'estado' => 1, // Por defecto activa
            'detalles' => []
        ];

        // Procesar detalles de la venta
        if (isset($post_data['productos']) && is_array($post_data['productos'])) {
            foreach ($post_data['productos'] as $key => $idProducto) {
                if (!empty($idProducto)) {
                    $datos['detalles'][] = [
                        'idproducto' => (int)$idProducto,
                        'cantidad' => isset($post_data['cantidades'][$key]) ? (int)$post_data['cantidades'][$key] : 1,
                        'precioventa' => isset($post_data['precios'][$key]) ? (float)$post_data['precios'][$key] : 0,
                        'descuento' => isset($post_data['descuentos'][$key]) ? (float)$post_data['descuentos'][$key] : 0.00
                    ];
                }
            }
        }

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva venta
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la venta
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosVenta($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar venta usando el modelo
        $idVenta = $this->modelo->crear($datos);

        if ($idVenta) {
            return ['success' => true, 'message' => 'Venta registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la venta: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }
    /**
     * Procesa el formulario para guardar una nueva venta
     */
    public function guardara()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'nueva.php'];
        }

        // Preparar datos de la venta
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosVenta($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'nueva.php'];
        }

        // Guardar venta usando el modelo
        $idVenta = $this->modelo->crear($datos);

        if ($idVenta) {
            return ['success' => true, 'message' => 'Venta registrada correctamente', 'icon' => 'success', 'redirect' => 'nueva.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la venta: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'nueva.php'];
        }
    }

    /**
     * Muestra los detalles de una venta
     * 
     * @param int $id ID de la venta
     * @return array|null Datos de la venta o redirige en caso de error
     */
    public function ver($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de venta no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/ventas');
            exit;
        }

        // Obtener datos de la venta con detalles
        $venta = $this->modelo->getById($id);

        if (!$venta) {
            global $URL;
            $_SESSION['mensaje'] = 'Venta no encontrada';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/ventas');
            exit;
        }

        // Devolver los datos de la venta
        return $venta;
    }

    /**
     * Anula una venta
     * 
     * @param int $id ID de la venta
     * @return array Resultado de la operación
     */
    public function anular($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de venta no válido', 'icon' => 'error'];
        }

        if ($this->modelo->anular($id)) {
            return ['success' => true, 'message' => 'Venta anulada correctamente', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al anular la venta: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene ventas por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (YYYY-MM-DD)
     * @return array Lista de ventas en el rango
     */
    public function obtenerPorRangoFechas($fechaInicio, $fechaFin)
    {
        return $this->modelo->getPorRangoFechas($fechaInicio, $fechaFin);
    }

    /**
     * Obtiene ventas por estado
     * 
     * @param int $estado Estado de las ventas (1: Activo, 0: Inactivo)
     * @return array Lista de ventas con el estado especificado
     */
    public function obtenerPorEstado($estado)
    {
        return $this->modelo->getPorEstado($estado);
    }

    /**
     * Obtiene ventas por usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de ventas del usuario
     */
    public function obtenerPorUsuario($idUsuario)
    {
        return $this->modelo->getPorUsuario($idUsuario);
    }

    /**
     * Obtiene ventas por cliente
     * 
     * @param int $idCliente ID del cliente
     * @return array Lista de ventas del cliente
     */
    public function obtenerPorCliente($idCliente)
    {
        return $this->modelo->getPorCliente($idCliente);
    }

    /**
     * Obtiene estadísticas de ventas
     * 
     * @return array Estadísticas de ventas
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }

    /**
     * Genera un ticket de venta (para impresión)
     * 
     * @param int $idVenta ID de la venta
     * @return array Datos para el ticket
     */
    public function generarTicket($idVenta)
    {
        $venta = $this->modelo->getById($idVenta);

        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        // Formatear datos para el ticket
        $ticket = [
            'id' => $venta['idventa'],
            'fecha' => date('d/m/Y H:i', strtotime($venta['fechacreacion'])),
            'cliente' => $venta['cliente_nombre'] ?? 'Consumidor Final',
            'usuario' => $venta['usuario_nombre'],
            'metodo_pago' => $venta['metodopago'],
            'productos' => [],
            'total' => $venta['totalventa'],
            'pago' => $venta['pagorecibido'],
            'cambio' => $venta['cambio']
        ];

        foreach ($venta['detalles'] as $detalle) {
            $ticket['productos'][] = [
                'nombre' => $detalle['producto_nombre'],
                'cantidad' => $detalle['cantidad'],
                'precio' => $detalle['precioventa'],
                'subtotal' => $detalle['cantidad'] * $detalle['precioventa']
            ];
        }

        return ['success' => true, 'data' => $ticket];
    }
}

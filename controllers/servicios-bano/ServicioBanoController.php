<?php

/**
 * Controlador de Servicios de Baños
 * 
 * Gestiona las operaciones relacionadas con los servicios de baños
 * 
 * @author Sistema de Alojamiento
 * @version 2.0
 */
require_once __DIR__ . '/../../services/AuthorizationService.php';

class ServicioBanoController
{
    /**
     * Modelo de ServicioBano
     * @var ServicioBano
     */
    public $modelo;

    /**
     * Modelo de Producto
     * @var Producto
     */
    public $productoModelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de ServicioBano
        require_once __DIR__ . '/../../models/ServicioBano.php';
        $this->modelo = new ServicioBano();

        // Incluir el modelo de Producto
        require_once __DIR__ . '/../../models/Producto.php';
        $this->productoModelo = new Producto();
    }

    /**
     * Registra en el log un error capturado, con contexto sobre dónde ocurrió
     *
     * @param string $contexto Descripción de la operación que falló
     * @param Exception $e Excepción capturada
     */
    private function logError($contexto, $e)
    {
        error_log($contexto . ': ' . $e->getMessage());
    }

    /**
     * Muestra la lista de servicios de baños
     * 
     * @return array Lista de servicios de baños
     */
    public function index()
    {
        // Obtener todos los servicios de baños
        return $this->modelo->getAll();
    }

    /**
     * Prepara los datos para el formulario de creación
     * 
     * @return array Datos para el formulario
     */
    public function crear()
    {
        // Obtener los baños disponibles para el select
        $banos_disponibles = $this->getBanosDisponibles();

        // Obtener los clientes para el select
        $clientes = $this->getClientes();

        // Devolver los datos para el formulario
        return [
            'banos_disponibles' => $banos_disponibles,
            'clientes' => $clientes
        ];
    }

    /**
     * Obtiene todos los baños disponibles
     * 
     * @return array Lista de baños disponibles
     */
    public function getBanosDisponibles()
    {
        return $this->modelo->getBanosDisponibles();
    }

    /**
     * Obtiene todos los clientes registrados
     * 
     * @return array Lista de clientes
     */
    public function getClientes()
    {
        return $this->modelo->getClientes();
    }

    /**
     * Prepara los datos del servicio de baño desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosServicio($post_data)
    {
        // El total sale del precio del baño en BD, nunca del cliente
        $idbano = isset($post_data['idbano']) ? (int)$post_data['idbano'] : 0;
        $total = $this->modelo->getPrecioBano($idbano) ?? 0;
        // Un método fuera de la lista blanca no se corrige en silencio: lo rechaza validarMetodoPago()
        $metodopago = $post_data['metodopago'] ?? 'Efectivo';
        // Solo en efectivo hay un monto recibido distinto del total
        $pagorecibido = $metodopago === 'Efectivo' && isset($post_data['pagorecibido'])
            ? (float)$post_data['pagorecibido']
            : $total;
        $cambio = max(0, $pagorecibido - $total); // Asegurar que el cambio no sea negativo
        $tipoCliente = ($post_data['tipo_cliente'] ?? 'Publico') === 'Huesped' ? 'Huesped' : 'Publico';

        $datos = [
            'idbano' => isset($post_data['idbano']) ? (int)$post_data['idbano'] : 0,
            'idusuario' => $_SESSION['usuario_id'], // Usuario actual que registra el servicio
            'idcliente' => $tipoCliente === 'Huesped' && !empty($post_data['idcliente']) ? (int)$post_data['idcliente'] : null,
            'tipo_cliente' => $tipoCliente,
            'total' => $total,
            'metodopago' => $metodopago,
            'pagorecibido' => $pagorecibido,
            'cambio' => $cambio,
            'observaciones' => mb_substr(trim($post_data['observaciones'] ?? ''), 0, 255)
            // Nota: 'estado' se maneja exclusivamente vía cambiarEstadoServicio(),
            // nunca a partir de datos de creación/actualización del formulario.
        ];

        return $datos;
    }

    private function validarMetodoPago($metodopago)
    {
        return in_array($metodopago, ['Efectivo', 'QR', 'OTROS'], true) ? null : 'El método de pago no es válido.';
    }

    /**
     * Procesa el formulario para guardar un nuevo servicio de baño
     * y descuenta el insumo configurado
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        try {
            $datos = $this->modelo->sanitizarDatos($this->prepararDatosServicio($_POST));

            $errores = $this->modelo->validarDatos($datos);
            if (empty($errores) && ($errorMetodo = $this->validarMetodoPago($datos['metodopago']))) {
                $errores[] = $errorMetodo;
            }
            if (!empty($errores)) {
                return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
            }

            $resultado = $this->modelo->registrar($datos, $_SESSION['usuario_id']);
            if ($resultado === false) {
                return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
            }

            $mensaje = 'Servicio de baño registrado correctamente.';
            if ($resultado['alertaStockBajo']) {
                $mensaje .= " ADVERTENCIA: Stock bajo - Quedan {$resultado['stock_restante']} unidades (mínimo recomendado: {$resultado['stock_minimo']}).";
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'icon' => $resultado['alertaStockBajo'] ? 'warning' : 'success',
                'redirect' => 'index.php'
            ];
        } catch (Exception $e) {
            $this->logError('Error inesperado al crear servicio de baño', $e);
            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado. Intente nuevamente.',
                'icon' => 'error',
                'redirect' => 'create.php'
            ];
        }
    }

    /**
     * Crea un servicio de baño rápido
     * 
     * @param int $idbano ID del baño (opcional)
     * @return array Resultado de la operación
     */
    public function crearServicioRapido($idbano = null)
    {
        try {
            // Si no se proporcionó un baño, obtener el primer baño disponible
            if ($idbano === null) {
                $banos_disponibles = $this->getBanosDisponibles();
                if (empty($banos_disponibles)) {
                    return [
                        'success' => false,
                        'message' => 'No hay baños disponibles en este momento',
                        'icon' => 'error'
                    ];
                }
                $idbano = $banos_disponibles[0]['idbano'];
            }

            $resultado = $this->modelo->registrar([
                'idbano' => $idbano,
                'idcliente' => null,
                'tipo_cliente' => 'Publico',
                'metodopago' => 'Efectivo'
            ], $_SESSION['usuario_id']);

            if ($resultado === false) {
                return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error'];
            }

            $mensaje = 'Servicio de baño registrado correctamente.';
            if ($resultado['alertaStockBajo']) {
                $mensaje .= " ADVERTENCIA: Stock bajo de papel higiénico ({$resultado['stock_restante']} unidades restantes).";
            }

            return [
                'success' => true,
                'message' => $mensaje,
                'icon' => $resultado['alertaStockBajo'] ? 'warning' : 'success',
                'idservicio' => $resultado['idservicio'],
                'total' => $resultado['total']
            ];
        } catch (Exception $e) {
            $this->logError('Error inesperado al crear servicio de baño rápido', $e);
            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado. Intente nuevamente.',
                'icon' => 'error'
            ];
        }
    }

    /**
     * Verifica si se puede crear un servicio de baño
     * 
     * @return array Resultado de la validación
     */
    public function validarDisponibilidadServicio()
    {
        try {
            // Verificar baños disponibles
            $banos = $this->getBanosDisponibles();
            if (empty($banos)) {
                return [
                    'disponible' => false,
                    'message' => 'No hay baños disponibles'
                ];
            }

            // Verificar producto y stock
            $config = require __DIR__ . '/../../config/config.php';
            $nombreInsumo = $config['servicios_bano']['producto_consumible'];
            $producto = $this->productoModelo->getByNombre($nombreInsumo);

            if (!$producto) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "' . $nombreInsumo . '" no configurado'
                ];
            }

            if (!isset($producto['estado']) || $producto['estado'] != 1) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "' . $nombreInsumo . '" inactivo'
                ];
            }

            $stockActual = (int)($producto['stock'] ?? 0);
            if ($stockActual <= 0) {
                return [
                    'disponible' => false,
                    'message' => 'Sin stock de papel higiénico'
                ];
            }

            return [
                'disponible' => true,
                'message' => 'Servicio disponible',
                'stock_actual' => $stockActual,
                'stock_minimo' => $producto['stock_minimo'] ?? 0
            ];
        } catch (Exception $e) {
            $this->logError('Error al verificar disponibilidad de servicio de baño', $e);
            return [
                'disponible' => false,
                'message' => 'Error al verificar disponibilidad del servicio.'
            ];
        }
    }

    /**
     * Datos para los formularios de detalle y edición de un servicio de baño.
     * No redirige: la vista decide el destino antes de incluir el layout.
     *
     * @param int|null $id ID del servicio de baño
     * @return array|null Datos del servicio, o null si el ID es inválido o no existe
     */
    public function obtener($id = null)
    {
        if (!$id) {
            return null;
        }

        $servicio = $this->modelo->getById($id);
        if (!$servicio) {
            return null;
        }

        return [
            'servicio' => $servicio,
            'banos_disponibles' => $this->getBanosDisponibles(),
            'clientes' => $this->getClientes()
        ];
    }

    /**
     * Procesa el formulario para actualizar un servicio de baño
     * 
     * @return array Resultado de la operación
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del servicio
        $id = isset($_POST['idservicio']) ? (int)$_POST['idservicio'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de servicio de baño no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        if (!$this->modelo->getById($id)) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Solo se editan campos descriptivos; el dinero y el estado no vienen del formulario
        $datos = ['observaciones' => mb_substr(trim($_POST['observaciones'] ?? ''), 0, 255)];

        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Servicio de baño actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        }

        return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => "update.php?id=$id"];
    }

    /**
     * Cambia el estado de un servicio de baño.
     *
     * El estado actual se lee siempre de la BD (nunca del cliente) y la matriz de
     * transiciones se aplica en el modelo. Por ahora solo se puede cancelar.
     *
     * @param int $id ID del servicio de baño
     * @param string $nuevo_estado Nuevo estado
     * @param string $motivo Obligatorio al cancelar
     * @return array Resultado de la operación
     */
    public function cambiarEstadoServicio($id = null, $nuevo_estado = null, $motivo = '')
    {
        if ($id === null || $nuevo_estado === null) {
            return ['success' => false, 'message' => 'Datos no válidos para cambiar el estado del servicio', 'icon' => 'error'];
        }

        if (!in_array($nuevo_estado, ServicioBano::ESTADOS_VALIDOS, true)) {
            return ['success' => false, 'message' => 'Estado no válido', 'icon' => 'error'];
        }

        if ($nuevo_estado !== 'cancelado') {
            return ['success' => false, 'message' => 'Esa acción no está disponible.', 'icon' => 'warning'];
        }

        if (!$this->modelo->getById($id)) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado', 'icon' => 'error'];
        }

        $auth = new AuthorizationService();
        $esAdmin = $auth->esAdministrador($_SESSION['usuario_id']);

        if ($this->modelo->cancelar($id, $_SESSION['usuario_id'], $esAdmin, $motivo)) {
            return ['success' => true, 'message' => 'Servicio de baño cancelado correctamente', 'icon' => 'success'];
        }

        return ['success' => false, 'message' => $this->modelo->getLastError(), 'icon' => 'error'];
    }

    /**
     * Acciones de estado que el usuario puede ejecutar sobre un servicio; las vistas solo pintan botones.
     *
     * @param array $servicio Fila de servicio (necesita 'estado')
     * @return array Lista de estados destino permitidos
     */
    public function accionesEstado(array $servicio)
    {
        $auth = new AuthorizationService();
        $esAdmin = $auth->esAdministrador($_SESSION['usuario_id']);

        return array_values(array_filter(
            ['cancelado'],
            fn($destino) => ServicioBano::transicionPermitida($servicio['estado'], $destino, $esAdmin)
        ));
    }

    /**
     * Obtiene estadísticas de servicios de baños
     * 
     * @return array Estadísticas de servicios de baños
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}

<?php

/**
 * Controlador de Servicios de Baños
 * 
 * Gestiona las operaciones relacionadas con los servicios de baños
 * 
 * @author Sistema de Alojamiento
 * @version 2.0
 */
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
        // Calcular el cambio
        $total = isset($post_data['total']) ? (float)$post_data['total'] : 0;
        $pagorecibido = isset($post_data['pagorecibido']) ? (float)$post_data['pagorecibido'] : 0;
        $cambio = max(0, $pagorecibido - $total); // Asegurar que el cambio no sea negativo

        $datos = [
            'idbano' => isset($post_data['idbano']) ? (int)$post_data['idbano'] : 0,
            'idusuario' => $_SESSION['usuario_id'], // Usuario actual que registra el servicio
            'idcliente' => isset($post_data['idcliente']) && !empty($post_data['idcliente']) ? (int)$post_data['idcliente'] : null,
            'tipo_cliente' => isset($post_data['tipo_cliente']) ? $post_data['tipo_cliente'] : 'Publico',
            'total' => $total,
            'metodopago' => isset($post_data['metodopago']) ? $post_data['metodopago'] : 'Efectivo', // Valor por defecto
            'pagorecibido' => $pagorecibido,
            'cambio' => $cambio,
            'fecha' => isset($post_data['fecha']) ? $post_data['fecha'] : date('Y-m-d H:i:s')
            // Nota: 'estado' se maneja exclusivamente vía cambiarEstadoServicio(),
            // nunca a partir de datos de creación/actualización del formulario.
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo servicio de baño
     * y reduce el stock del producto "Rollo de papel"
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
            // Preparar datos del servicio
            $datos = $this->modelo->sanitizarDatos($this->prepararDatosServicio($_POST));

            // Validar datos en el modelo
            $errores = $this->modelo->validarDatos($datos);

            if (!empty($errores)) {
                return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
            }

            // VALIDACIÓN ESTRICTA: Verificar producto "Rollo de papel"
            $producto = $this->productoModelo->getByNombre('Rollo de papel');

            if (!$producto) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: El producto "Rollo de papel" no existe en el sistema. Contacte al administrador.',
                    'icon' => 'error',
                    'redirect' => 'create.php'
                ];
            }

            // Verificar que el producto esté activo
            if (!isset($producto['estado']) || $producto['estado'] != 1) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: El producto "Rollo de papel" está inactivo.',
                    'icon' => 'error',
                    'redirect' => 'create.php'
                ];
            }

            // Verificar stock suficiente
            $stockActual = (int)($producto['stock'] ?? 0);
            if ($stockActual <= 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: No hay stock disponible de "Rollo de papel". Stock actual: ' . $stockActual,
                    'icon' => 'error',
                    'redirect' => 'create.php'
                ];
            }

            // Intentar reducir el stock del producto
            $resultadoReduccion = $this->productoModelo->reducirStock($producto['idproducto']);

            if ($resultadoReduccion === false) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: Error al reducir stock de "Rollo de papel": ' . $this->productoModelo->getLastError(),
                    'icon' => 'error',
                    'redirect' => 'create.php'
                ];
            }

            // Si llegamos aquí, el stock se redujo correctamente, ahora crear el servicio
            if ($this->modelo->crear($datos)) {
                $mensaje = 'Servicio de baño registrado correctamente.';

                // Verificar si hay alerta de stock bajo
                if (is_array($resultadoReduccion) && isset($resultadoReduccion['alertaStockBajo']) && $resultadoReduccion['alertaStockBajo']) {
                    $stockRestante = $stockActual - 1;
                    $stockMinimo = $producto['stock_minimo'] ?? 0;
                    $mensaje .= " ADVERTENCIA: Stock bajo - Quedan {$stockRestante} unidades (mínimo recomendado: {$stockMinimo}).";
                }

                return [
                    'success' => true,
                    'message' => $mensaje,
                    'icon' => is_array($resultadoReduccion) && isset($resultadoReduccion['alertaStockBajo']) && $resultadoReduccion['alertaStockBajo'] ? 'warning' : 'success',
                    'redirect' => 'index.php'
                ];
            } else {
                // Si no se pudo crear el servicio, intentar restaurar el stock
                $this->restaurarStock($producto['idproducto']);

                return [
                    'success' => false,
                    'message' => 'Error al crear el servicio de baño: ' . $this->modelo->getLastError(),
                    'icon' => 'error',
                    'redirect' => 'create.php'
                ];
            }
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

            // VALIDACIÓN ESTRICTA: Verificar producto "Rollo de papel"
            $producto = $this->productoModelo->getByNombre('Rollo de papel');

            if (!$producto) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: El producto "Rollo de papel" no existe en el sistema.',
                    'icon' => 'error'
                ];
            }

            // Verificar que el producto esté activo
            if (!isset($producto['estado']) || $producto['estado'] != 1) {
                return [
                    'success' => false,
                    'message' => 'No se puede crear el servicio: El producto "Rollo de papel" está inactivo.',
                    'icon' => 'error'
                ];
            }

            // Verificar stock suficiente
            $stockActual = (int)($producto['stock'] ?? 0);
            if ($stockActual <= 0) {
                return [
                    'success' => false,
                    'message' => 'No hay stock disponible de papel higiénico. Stock actual: ' . $stockActual,
                    'icon' => 'error'
                ];
            }

            // Intentar reducir el stock
            $resultadoReduccion = $this->productoModelo->reducirStock($producto['idproducto']);

            if ($resultadoReduccion === false) {
                return [
                    'success' => false,
                    'message' => 'Error al reducir stock de "Rollo de papel": ' . $this->productoModelo->getLastError(),
                    'icon' => 'error'
                ];
            }

            // Crear el servicio rápido
            $resultado = $this->modelo->crearServicioRapido($idbano, $_SESSION['usuario_id']);

            if ($resultado) {
                $mensaje = 'Servicio de baño registrado correctamente.';

                // Verificar alerta de stock bajo
                if (is_array($resultadoReduccion) && isset($resultadoReduccion['alertaStockBajo']) && $resultadoReduccion['alertaStockBajo']) {
                    $stockRestante = $stockActual - 1;
                    $mensaje .= " ADVERTENCIA: Stock bajo de papel higiénico ({$stockRestante} unidades restantes).";
                }

                return [
                    'success' => true,
                    'message' => $mensaje,
                    'icon' => is_array($resultadoReduccion) && isset($resultadoReduccion['alertaStockBajo']) && $resultadoReduccion['alertaStockBajo'] ? 'warning' : 'success',
                    'idservicio' => $resultado['idservicio'],
                    'total' => $resultado['total']
                ];
            } else {
                // Si no se pudo crear el servicio, restaurar el stock
                $this->restaurarStock($producto['idproducto']);

                return [
                    'success' => false,
                    'message' => 'Error al crear el servicio de baño: ' . $this->modelo->getLastError(),
                    'icon' => 'error'
                ];
            }
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
     * Restaurar stock en caso de error al crear servicio
     * 
     * @param int $idproducto ID del producto
     * @return bool
     */
    private function restaurarStock($idproducto)
    {
        try {
            // Usar el método incrementarStock del modelo Producto
            return $this->productoModelo->incrementarStock($idproducto, 1);
        } catch (Exception $e) {
            $this->logError('Error al restaurar stock', $e);
            return false;
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
            $producto = $this->productoModelo->getByNombre('Rollo de papel');

            if (!$producto) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "Rollo de papel" no configurado'
                ];
            }

            if (!isset($producto['estado']) || $producto['estado'] != 1) {
                return [
                    'disponible' => false,
                    'message' => 'Producto "Rollo de papel" inactivo'
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
     * Muestra el formulario para editar un servicio de baño
     * 
     * @param int $id ID del servicio de baño
     * @return array|null Datos del servicio de baño o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de servicio de baño no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/servicios-bano');
            exit;
        }

        // Obtener datos del servicio
        $servicio = $this->modelo->getById($id);

        if (!$servicio) {
            global $URL;
            $_SESSION['mensaje'] = 'Servicio de baño no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/servicios-bano');
            exit;
        }

        // Obtener los baños disponibles para el select
        $banos_disponibles = $this->getBanosDisponibles();

        // Obtener los clientes para el select
        $clientes = $this->getClientes();

        // Devolver los datos del servicio
        return [
            'servicio' => $servicio,
            'banos_disponibles' => $banos_disponibles,
            'clientes' => $clientes
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

        // Obtener datos actuales del servicio
        $servicio_actual = $this->modelo->getById($id);
        if (!$servicio_actual) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos del servicio
        $datos = $this->prepararDatosServicio($_POST);

        // Mantener el usuario original que registró el servicio
        $datos['idusuario'] = $servicio_actual['idusuario'];

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar servicio
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Servicio de baño actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar el servicio de baño: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un servicio de baño
     *
     * El estado actual SIEMPRE se recalcula desde la BD (nunca se confía en un
     * valor recibido del cliente), y el nuevo estado se valida contra los
     * valores reales del enum de la columna.
     *
     * @param int $id ID del servicio de baño
     * @param string $nuevo_estado Nuevo estado ('iniciado'|'finalizado'|'cancelado')
     * @return array Resultado de la operación
     */
    public function cambiarEstadoServicio($id = null, $nuevo_estado = null)
    {
        if ($id === null || $nuevo_estado === null) {
            return ['success' => false, 'message' => 'Datos no válidos para cambiar el estado del servicio', 'icon' => 'error'];
        }

        if (!in_array($nuevo_estado, ServicioBano::ESTADOS_VALIDOS, true)) {
            return ['success' => false, 'message' => 'Estado no válido', 'icon' => 'error'];
        }

        // Verificar que el servicio existe (estado actual recalculado desde BD)
        $servicio = $this->modelo->getById($id);
        if (!$servicio) {
            return ['success' => false, 'message' => 'Servicio de baño no encontrado', 'icon' => 'error'];
        }

        if ($servicio['estado'] === $nuevo_estado) {
            return ['success' => false, 'message' => 'El servicio ya se encuentra en ese estado', 'icon' => 'warning'];
        }

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $mensajes = [
                'iniciado' => 'reactivado',
                'finalizado' => 'finalizado',
                'cancelado' => 'cancelado'
            ];

            return [
                'success' => true,
                'message' => "Servicio de baño " . $mensajes[$nuevo_estado] . " correctamente",
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del servicio: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
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

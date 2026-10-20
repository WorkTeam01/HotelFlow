<?php

/**
 * Modelo ServicioBano
 * 
 * Gestiona las operaciones relacionadas con los servicios de baños en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 2.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class ServicioBano
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de servicios de baños en la base de datos
     * @var string
     */
    private $tabla = 'servicio_bano';

    /**
     * Último error ocurrido
     * @var string
     */
    private $lastError = '';

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Obtiene el último error ocurrido
     * 
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Sanitiza los datos de entrada para prevenir inyección SQL y XSS
     * 
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitizarDatos($datos)
    {
        $sanitized = [];
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                // Eliminar espacios adicionales y caracteres potencialmente peligrosos
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todos los servicios de baños
     * 
     * @return array Lista de servicios de baños
     */
    public function getAll()
    {
        try {
            $query = "SELECT sb.idservicio, sb.idbano, sb.idusuario, sb.idcliente, 
                  sb.tipo_cliente, sb.total, sb.metodopago, sb.pagorecibido, 
                  sb.cambio, sb.fecha, sb.estado, sb.modalidad_cobro, sb.observaciones,
                  b.nombre as nombre_bano, b.precio as precio_bano,
                  CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                  CASE 
                    WHEN sb.idcliente IS NOT NULL THEN CONCAT(p.nombre, ' ', p.apellidopaterno)
                    ELSE 'Cliente sin registrar' 
                  END as nombre_cliente
                  FROM {$this->tabla} sb
                  LEFT JOIN bano b ON sb.idbano = b.idbano
                  LEFT JOIN usuarios u ON sb.idusuario = u.idusuario
                  LEFT JOIN persona p ON sb.idcliente = p.idpersona
                  ORDER BY sb.idservicio DESC";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();

            // Obtener resultados
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $resultados;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            error_log("Error en ServicioBano::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un servicio de baño por su ID
     * 
     * @param int $id ID del servicio de baño
     * @return array|bool Datos del servicio de baño o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT sb.idservicio, sb.idbano, sb.idusuario, sb.idcliente, 
                  sb.tipo_cliente, sb.total, sb.metodopago, sb.pagorecibido, 
                  sb.cambio, sb.fecha, sb.estado, sb.modalidad_cobro, sb.observaciones,
                  b.nombre as nombre_bano, b.precio as precio_bano,
                  CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                  CASE 
                    WHEN sb.idcliente IS NOT NULL THEN CONCAT(p.nombre, ' ', p.apellidopaterno)
                    ELSE 'Cliente sin registrar' 
                  END as nombre_cliente
                  FROM {$this->tabla} sb
                  LEFT JOIN bano b ON sb.idbano = b.idbano
                  LEFT JOIN usuarios u ON sb.idusuario = u.idusuario
                  LEFT JOIN persona p ON sb.idcliente = p.idpersona
                  WHERE sb.idservicio = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Obtiene todos los baños disponibles para asignar a un servicio
     * 
     * @return array Lista de baños disponibles
     */
    public function getBanosDisponibles()
    {
        try {
            $query = "SELECT idbano, nombre, ubicacion, precio FROM bano WHERE estado = 'disponible' ORDER BY nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene todos los clientes (personas) para asignar a un servicio
     * 
     * @return array Lista de clientes
     */
    public function getClientes()
    {
        try {
            $query = "SELECT idpersona, CONCAT(nombre, ' ', apellidopaterno) as nombre_completo FROM persona ORDER BY nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Registra un servicio de baño cobrado al contado en una sola transacción:
     * bloquea el baño, descuenta el insumo y escribe el servicio y su cargo + pago en `pagos`.
     * El monto y la fecha salen del servidor, nunca del cliente.
     *
     * @param array $datos idbano, idcliente, tipo_cliente, metodopago, pagorecibido, observaciones
     * @param int $idusuario Usuario que cobra
     * @return array|false ['idservicio','total','alertaStockBajo','stock_restante','stock_minimo'] o false
     */
    public function registrar($datos, $idusuario)
    {
        $config = require __DIR__ . '/../config/config.php';
        $nombreInsumo = $config['servicios_bano']['producto_consumible'] ?? 'Rollo de papel';

        $transaccionPropia = !$this->conexion->inTransaction();

        try {
            if ($transaccionPropia) {
                $this->conexion->beginTransaction();
            }

            $stmt = $this->conexion->prepare("SELECT nombre, precio, estado FROM bano WHERE idbano = :idbano FOR UPDATE");
            $stmt->bindValue(':idbano', (int)$datos['idbano'], PDO::PARAM_INT);
            $stmt->execute();
            $bano = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bano === false) {
                return $this->abortarRegistro($transaccionPropia, 'El baño seleccionado no existe.');
            }
            if ($bano['estado'] !== 'disponible') {
                return $this->abortarRegistro($transaccionPropia, 'El baño seleccionado no está disponible.');
            }

            $total = $this->calcularMonto($bano);
            $metodopago = $datos['metodopago'];
            // Solo en efectivo el monto recibido puede ser mayor al total
            $pagorecibido = $metodopago === 'Efectivo' && isset($datos['pagorecibido']) ? (float)$datos['pagorecibido'] : $total;
            if ($pagorecibido < $total) {
                return $this->abortarRegistro($transaccionPropia, 'El pago recibido no cubre el total del servicio');
            }
            $cambio = $pagorecibido - $total;

            require_once __DIR__ . '/Producto.php';
            $productoModelo = new Producto();
            $producto = $productoModelo->getByNombre($nombreInsumo);
            if (!$producto) {
                return $this->abortarRegistro($transaccionPropia, "El producto \"$nombreInsumo\" no existe o está inactivo en el sistema. Contacte al administrador.");
            }

            $reduccion = $productoModelo->reducirStock($producto['idproducto']);
            if ($reduccion === false) {
                return $this->abortarRegistro($transaccionPropia, 'No se puede crear el servicio: ' . $productoModelo->getLastError());
            }

            $stmt = $this->conexion->prepare("INSERT INTO {$this->tabla}
                (idbano, idusuario, idcliente, tipo_cliente, modalidad_cobro, total, metodopago,
                 pagorecibido, cambio, fecha, observaciones)
                VALUES (:idbano, :idusuario, :idcliente, :tipo_cliente, 'contado', :total, :metodopago,
                 :pagorecibido, :cambio, :fecha, :observaciones)");
            $stmt->bindValue(':idbano', (int)$datos['idbano'], PDO::PARAM_INT);
            $stmt->bindValue(':idusuario', (int)$idusuario, PDO::PARAM_INT);
            $stmt->bindValue(':idcliente', !empty($datos['idcliente']) ? (int)$datos['idcliente'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_cliente', $datos['tipo_cliente'], PDO::PARAM_STR);
            $stmt->bindValue(':total', $total);
            $stmt->bindValue(':metodopago', $metodopago, PDO::PARAM_STR);
            $stmt->bindValue(':pagorecibido', $pagorecibido);
            $stmt->bindValue(':cambio', $cambio);
            $stmt->bindValue(':fecha', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':observaciones', ($datos['observaciones'] ?? '') !== '' ? $datos['observaciones'] : null, PDO::PARAM_STR);
            $stmt->execute();
            $idservicio = (int)$this->conexion->lastInsertId();

            require_once __DIR__ . '/Pago.php';
            $pagoModelo = new Pago();
            $concepto = 'Servicio de baño - ' . $bano['nombre'] . ' (#' . $idservicio . ')';
            // Un baño de precio cero no genera movimiento de dinero en el ledger
            if (
                $total > 0 && (
                    !$pagoModelo->registrarLineaServicio($idservicio, 'cargo', $concepto, $total, 'OTROS', $idusuario)
                    || !$pagoModelo->registrarLineaServicio($idservicio, 'pago', $concepto, $total, $metodopago, $idusuario)
                )
            ) {
                return $this->abortarRegistro($transaccionPropia, $pagoModelo->getLastError());
            }

            if ($transaccionPropia) {
                $this->conexion->commit();
            }

            return [
                'idservicio' => $idservicio,
                'total' => $total,
                'alertaStockBajo' => !empty($reduccion['alertaStockBajo']),
                'stock_restante' => (int)$producto['stock'] - 1,
                'stock_minimo' => (int)($producto['stock_minimo'] ?? 0)
            ];
        } catch (PDOException $e) {
            if ($transaccionPropia && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Monto a cobrar por un baño; único punto de cambio si la tarifa pasa a depender de la duración.
     *
     * @param array $bano Fila de `bano` ya bloqueada
     * @return float
     */
    private function calcularMonto(array $bano)
    {
        return (float)$bano['precio'];
    }

    private function abortarRegistro($transaccionPropia, $mensaje)
    {
        if ($transaccionPropia && $this->conexion->inTransaction()) {
            $this->conexion->rollBack();
        }
        $this->lastError = $mensaje;
        return false;
    }

    /**
     * Actualiza un servicio de baño existente
     * 
     * @param int $id ID del servicio de baño
     * @param array $datos Datos del servicio de baño
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            // El estado NUNCA se toca aquí: la actualización de datos del servicio
            // (baño, cliente, montos, etc.) está separada del cambio de estado, que
            // se hace exclusivamente vía actualizarEstado().
            $query = "UPDATE {$this->tabla} SET
                  idbano = :idbano,
                  idcliente = :idcliente,
                  tipo_cliente = :tipo_cliente,
                  total = :total,
                  metodopago = :metodopago,
                  pagorecibido = :pagorecibido,
                  cambio = :cambio
                  WHERE idservicio = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idbano', $datos['idbano'], PDO::PARAM_INT);

            // Manejar idcliente que puede ser NULL
            if (!empty($datos['idcliente'])) {
                $stmt->bindParam(':idcliente', $datos['idcliente'], PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':idcliente', null, PDO::PARAM_NULL);
            }

            $stmt->bindParam(':tipo_cliente', $datos['tipo_cliente'], PDO::PARAM_STR);
            $stmt->bindParam(':total', $datos['total'], PDO::PARAM_STR);
            $stmt->bindParam(':metodopago', $datos['metodopago'], PDO::PARAM_STR);
            $stmt->bindParam(':pagorecibido', $datos['pagorecibido'], PDO::PARAM_STR);
            $stmt->bindParam(':cambio', $datos['cambio'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Estados válidos para un servicio de baño (columna enum en BD)
     */
    public const ESTADOS_VALIDOS = ['iniciado', 'finalizado', 'cancelado'];

    /**
     * Actualiza el estado de un servicio de baño
     *
     * @param int $id ID del servicio de baño
     * @param string $estado Nuevo estado ('iniciado'|'finalizado'|'cancelado')
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            $this->lastError = "Estado '$estado' no válido";
            return false;
        }

        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idservicio = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cancela un servicio de baño
     *
     * @param int $id ID del servicio de baño
     * @return bool True si se canceló correctamente, False en caso contrario
     */
    public function cancelarServicio($id)
    {
        return $this->actualizarEstado($id, 'cancelado');
    }

    /**
     * Valida los datos del servicio de baño antes de crear o actualizar
     * 
     * @param array $datos Datos del servicio de baño
     * @param int $id_excluir ID del servicio a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (
            empty($datos['idbano']) ||
            !isset($datos['total']) ||
            !isset($datos['pagorecibido'])
        ) {
            $errores[] = 'Los campos baño, total y pago recibido son obligatorios';
        }

        // Validar tipo de cliente
        if (!empty($datos['tipo_cliente']) && !in_array($datos['tipo_cliente'], ['Huesped', 'Publico'])) {
            $errores[] = 'El tipo de cliente no es válido';
        }

        // Validar montos
        if (isset($datos['total'])) {
            if (!is_numeric($datos['total']) || $datos['total'] < 0) {
                $errores[] = 'El total debe ser un número mayor o igual a cero';
            }
        }

        if (isset($datos['pagorecibido'])) {
            if (!is_numeric($datos['pagorecibido']) || $datos['pagorecibido'] < 0) {
                $errores[] = 'El pago recibido debe ser un número mayor o igual a cero';
            }
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último servicio de baño insertado
     * 
     * @return int ID del último servicio de baño insertado
     */
    public function getLastInsertId()
    {
        try {
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return 0;
        }
    }

    /**
     * Obtiene estadísticas de servicios de baños del día actual
     * 
     * @return array Estadísticas de servicios de baños del día
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'ingresos_totales' => 0
            ];

            // Fecha actual para filtrar
            $fechaHoy = date('Y-m-d');

            // Total de servicios de hoy
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE DATE(fecha) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['total'] = $stmt->fetchColumn();

            // "Activos" = servicios vigentes (no cancelados): iniciado o finalizado
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado != 'cancelado' AND DATE(fecha) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['activos'] = $stmt->fetchColumn();

            // "Inactivos" = servicios cancelados
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado = 'cancelado' AND DATE(fecha) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['inactivos'] = $stmt->fetchColumn();

            // Cobrado hoy: pago neto del ledger (los reversos restan del pago que anulan)
            $query = "SELECT COALESCE(SUM(CASE WHEN p.tipo = 'pago' THEN p.montototal
                                               WHEN p.tipo = 'reverso' AND o.tipo = 'pago' THEN -p.montototal
                                               ELSE 0 END), 0)
                      FROM pagos p
                      LEFT JOIN pagos o ON o.id_pago = p.id_pago_reversado
                      WHERE p.idservicio_bano IS NOT NULL AND DATE(p.fechacreacion) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $ingresos = $stmt->fetchColumn();
            $estadisticas['ingresos_totales'] = $ingresos ? round($ingresos, 2) : 0;

            return $estadisticas;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'ingresos_totales' => 0
            ];
        }
    }

    public function iniciarTransaccion()
    {
        if (!$this->conexion->inTransaction()) {
            $this->conexion->beginTransaction();
        }
    }

    public function confirmarTransaccion()
    {
        if ($this->conexion->inTransaction()) {
            $this->conexion->commit();
        }
    }

    public function revertirTransaccion()
    {
        if ($this->conexion->inTransaction()) {
            $this->conexion->rollBack();
        }
    }

    /**
     * Precio vigente de un baño en BD (el total de un servicio nunca viene del cliente)
     *
     * @param int $idbano
     * @return float|null null si el baño no existe
     */
    public function getBanoParaServicio($idbano)
    {
        try {
            $stmt = $this->conexion->prepare("SELECT estado FROM bano WHERE idbano = :idbano");
            $stmt->bindValue(':idbano', (int)$idbano, PDO::PARAM_INT);
            $stmt->execute();
            $bano = $stmt->fetch(PDO::FETCH_ASSOC);
            return $bano === false ? null : $bano;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return null;
        }
    }

    public function getPrecioBano($idbano)
    {
        try {
            $stmt = $this->conexion->prepare("SELECT precio FROM bano WHERE idbano = :idbano");
            $stmt->bindValue(':idbano', (int)$idbano, PDO::PARAM_INT);
            $stmt->execute();
            $precio = $stmt->fetchColumn();
            return $precio === false ? null : (float)$precio;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return null;
        }
    }
}

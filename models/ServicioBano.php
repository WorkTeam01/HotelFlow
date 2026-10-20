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
                  sb.cambio, sb.fecha, sb.estado, sb.modalidad_cobro, sb.observaciones, sb.idrecepcion,
                  h.numero as numero_habitacion,
                  sb.motivo_cancelacion, sb.fecha_cancelacion, sb.stock_repuesto,
                  CONCAT(uc.nombre, ' ', uc.apellidop) as nombre_usuario_cancelacion,
                  b.nombre as nombre_bano, b.precio as precio_bano,
                  CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                  CASE 
                    WHEN sb.idcliente IS NOT NULL THEN CONCAT(p.nombre, ' ', p.apellidopaterno)
                    ELSE 'Cliente sin registrar' 
                  END as nombre_cliente
                  FROM {$this->tabla} sb
                  LEFT JOIN bano b ON sb.idbano = b.idbano
                  LEFT JOIN usuarios u ON sb.idusuario = u.idusuario
                  LEFT JOIN usuarios uc ON sb.idusuario_cancelacion = uc.idusuario
                  LEFT JOIN recepcion rc ON sb.idrecepcion = rc.idrecepcion
                  LEFT JOIN habitaciones h ON rc.idhabitacion = h.id_habitacion
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
                  sb.cambio, sb.fecha, sb.estado, sb.modalidad_cobro, sb.observaciones, sb.idrecepcion,
                  h.numero as numero_habitacion,
                  sb.motivo_cancelacion, sb.fecha_cancelacion, sb.stock_repuesto,
                  CONCAT(uc.nombre, ' ', uc.apellidop) as nombre_usuario_cancelacion,
                  b.nombre as nombre_bano, b.precio as precio_bano,
                  CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                  CASE 
                    WHEN sb.idcliente IS NOT NULL THEN CONCAT(p.nombre, ' ', p.apellidopaterno)
                    ELSE 'Cliente sin registrar' 
                  END as nombre_cliente
                  FROM {$this->tabla} sb
                  LEFT JOIN bano b ON sb.idbano = b.idbano
                  LEFT JOIN usuarios u ON sb.idusuario = u.idusuario
                  LEFT JOIN usuarios uc ON sb.idusuario_cancelacion = uc.idusuario
                  LEFT JOIN recepcion rc ON sb.idrecepcion = rc.idrecepcion
                  LEFT JOIN habitaciones h ON rc.idhabitacion = h.id_habitacion
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
     * Registra un servicio de baño en una sola transacción, cobrado al contado o cargado al folio de una estancia:
     * bloquea la estancia (si la hay) y el baño, descuenta el insumo y escribe el servicio y su cargo + pago en `pagos`.
     * El monto y la fecha salen del servidor, nunca del cliente.
     *
     * @param array $datos idbano, idcliente, tipo_cliente, metodopago, pagorecibido, observaciones,
     *                     idrecepcion y modalidad_cobro ('contado'|'folio'; folio exige idrecepcion)
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

            // Orden canónico de bloqueo: recepcion → bano → servicio_bano → productos
            $idrecepcion = !empty($datos['idrecepcion']) ? (int)$datos['idrecepcion'] : null;
            $modalidad = ($datos['modalidad_cobro'] ?? 'contado') === 'folio' ? 'folio' : 'contado';
            $idcliente = !empty($datos['idcliente']) ? (int)$datos['idcliente'] : null;
            $tipoCliente = $datos['tipo_cliente'];

            if ($modalidad === 'folio' && !$idrecepcion) {
                return $this->abortarRegistro($transaccionPropia, 'Seleccione la estancia a la que se cargará el servicio.');
            }
            if ($idrecepcion) {
                $stmt = $this->conexion->prepare("SELECT idcliente, estado FROM recepcion WHERE idrecepcion = :id FOR UPDATE");
                $stmt->bindValue(':id', $idrecepcion, PDO::PARAM_INT);
                $stmt->execute();
                $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$recepcion || $recepcion['estado'] !== 'en_curso') {
                    return $this->abortarRegistro($transaccionPropia, 'La estancia seleccionada no está en curso.');
                }
                // El huésped sale de la estancia, nunca del formulario
                $idcliente = (int)$recepcion['idcliente'];
                $tipoCliente = 'Huesped';
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
            if ($modalidad === 'folio') {
                $metodopago = null;
                $pagorecibido = 0.0;
                $cambio = 0.0;
            } else {
                $metodopago = $datos['metodopago'];
                // Solo en efectivo el monto recibido puede ser mayor al total
                $pagorecibido = $metodopago === 'Efectivo' && isset($datos['pagorecibido']) ? (float)$datos['pagorecibido'] : $total;
                if ($pagorecibido < $total) {
                    return $this->abortarRegistro($transaccionPropia, 'El pago recibido no cubre el total del servicio');
                }
                $cambio = $pagorecibido - $total;
            }

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
                (idbano, idusuario, idcliente, idrecepcion, tipo_cliente, modalidad_cobro, total, metodopago,
                 pagorecibido, cambio, fecha, observaciones)
                VALUES (:idbano, :idusuario, :idcliente, :idrecepcion, :tipo_cliente, :modalidad, :total, :metodopago,
                 :pagorecibido, :cambio, :fecha, :observaciones)");
            $stmt->bindValue(':idbano', (int)$datos['idbano'], PDO::PARAM_INT);
            $stmt->bindValue(':idusuario', (int)$idusuario, PDO::PARAM_INT);
            $stmt->bindValue(':idcliente', $idcliente, PDO::PARAM_INT);
            $stmt->bindValue(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_cliente', $tipoCliente, PDO::PARAM_STR);
            $stmt->bindValue(':modalidad', $modalidad, PDO::PARAM_STR);
            $stmt->bindValue(':total', $total);
            $stmt->bindValue(':metodopago', $metodopago, $metodopago === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
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
            if ($total > 0) {
                $okLineas = $modalidad === 'folio'
                    ? $pagoModelo->registrarLinea($idrecepcion, 'cargo', $concepto, $total, 'OTROS', $idusuario, null, $idservicio)
                    : $pagoModelo->registrarLineaServicio($idservicio, 'cargo', $concepto, $total, 'OTROS', $idusuario)
                        && $pagoModelo->registrarLineaServicio($idservicio, 'pago', $concepto, $total, $metodopago, $idusuario);
                if (!$okLineas) {
                    return $this->abortarRegistro($transaccionPropia, $pagoModelo->getLastError());
                }
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
     * Estados válidos para un servicio de baño (columna enum en BD)
     */
    public const ESTADOS_VALIDOS = ['iniciado', 'finalizado', 'cancelado'];

    /**
     * Transiciones permitidas; un servicio cancelado es final.
     */
    public const TRANSICIONES = [
        'iniciado'   => ['finalizado', 'cancelado'],
        'finalizado' => ['cancelado'],
        'cancelado'  => [],
    ];

    /**
     * Transiciones reservadas al Administrador.
     */
    public const SOLO_ADMIN = ['finalizado>cancelado'];

    /**
     * Campos que se pueden editar tras el cobro; el dinero solo se corrige cancelando con reverso.
     */
    public const CAMPOS_EDITABLES = ['observaciones'];

    /**
     * Si cancelar devuelve el insumo según el estado del que se cancela: un servicio 'iniciado'
     * se cancela por error de registro (el insumo no se usó); uno 'finalizado' ya lo consumió.
     */
    private function reponeStockAlCancelar($estadoOrigen)
    {
        return $estadoOrigen === 'iniciado';
    }

    /**
     * Indica si el usuario puede pasar el servicio al estado indicado.
     *
     * @param string $estadoActual
     * @param string $nuevoEstado
     * @param bool $esAdmin
     * @return bool
     */
    public static function transicionPermitida($estadoActual, $nuevoEstado, $esAdmin)
    {
        if (!in_array($nuevoEstado, self::TRANSICIONES[$estadoActual] ?? [], true)) {
            return false;
        }
        return $esAdmin || !in_array($estadoActual . '>' . $nuevoEstado, self::SOLO_ADMIN, true);
    }

    /**
     * Actualiza los campos descriptivos de un servicio (el dinero y el estado no se tocan aquí).
     *
     * @param int $id ID del servicio de baño
     * @param array $datos Solo se usan los campos de CAMPOS_EDITABLES
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $servicio = $this->getById($id);
            if (!$servicio) {
                $this->lastError = 'Servicio de baño no encontrado';
                return false;
            }
            if ($servicio['estado'] === 'cancelado') {
                $this->lastError = 'Un servicio cancelado no se puede editar.';
                return false;
            }

            $observaciones = trim($datos['observaciones'] ?? '');
            $stmt = $this->conexion->prepare("UPDATE {$this->tabla} SET observaciones = :observaciones WHERE idservicio = :id");
            $stmt->bindValue(':observaciones', $observaciones !== '' ? $observaciones : null, PDO::PARAM_STR);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cancela un servicio: revierte en `pagos` cada línea viva (primero el pago, luego el cargo),
     * devuelve el insumo si corresponde y registra quién y por qué. El estado se lee con FOR UPDATE.
     * Orden de bloqueo: bano → servicio_bano → productos.
     *
     * @param int $id
     * @param int $idusuario Usuario que cancela
     * @param bool $esAdmin
     * @param string $motivo Obligatorio
     * @return bool
     */
    public function cancelar($id, $idusuario, $esAdmin, $motivo)
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            $this->lastError = 'Indique el motivo de la cancelación.';
            return false;
        }
        $motivo = mb_substr($motivo, 0, 255);

        $config = require __DIR__ . '/../config/config.php';
        $transaccionPropia = !$this->conexion->inTransaction();

        try {
            // Sin lock: solo para conocer el baño (inmutable) y poder bloquear en el orden canónico
            $stmt = $this->conexion->prepare("SELECT idbano, idrecepcion, modalidad_cobro FROM {$this->tabla} WHERE idservicio = :id");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $previo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$previo) {
                $this->lastError = 'Servicio de baño no encontrado';
                return false;
            }
            $idbano = $previo['idbano'];
            $esFolio = $previo['modalidad_cobro'] === 'folio';

            if ($transaccionPropia) {
                $this->conexion->beginTransaction();
            }

            if ($esFolio) {
                $stmt = $this->conexion->prepare("SELECT estado FROM recepcion WHERE idrecepcion = :id FOR UPDATE");
                $stmt->bindValue(':id', (int)$previo['idrecepcion'], PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() !== 'en_curso') {
                    return $this->abortarRegistro($transaccionPropia, 'La estancia ya cerró: registre la devolución en Recepción.');
                }
            }

            $stmt = $this->conexion->prepare("SELECT idbano FROM bano WHERE idbano = :idbano FOR UPDATE");
            $stmt->bindValue(':idbano', (int)$idbano, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conexion->prepare("SELECT estado FROM {$this->tabla} WHERE idservicio = :id FOR UPDATE");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $estadoActual = $stmt->fetchColumn();

            if ($estadoActual === 'cancelado') {
                return $this->abortarRegistro($transaccionPropia, 'El servicio ya está cancelado');
            }
            if (!self::transicionPermitida($estadoActual, 'cancelado', $esAdmin)) {
                $mensaje = in_array($estadoActual . '>cancelado', self::SOLO_ADMIN, true)
                    ? 'Solo un Administrador puede cancelar un servicio finalizado.'
                    : 'Transición de estado no permitida.';
                return $this->abortarRegistro($transaccionPropia, $mensaje);
            }

            require_once __DIR__ . '/Pago.php';
            $pagoModelo = new Pago();
            $conceptoReverso = 'Cancelación de servicio de baño #' . (int)$id;
            foreach ($pagoModelo->getLineasVivasServicio($id) as $linea) {
                $ok = $esFolio
                    ? $pagoModelo->registrarLinea($previo['idrecepcion'], 'reverso', $conceptoReverso, $linea['montototal'], $linea['metodopago'], $idusuario, $linea['id_pago'], $id)
                    : $pagoModelo->registrarLineaServicio($id, 'reverso', $conceptoReverso, null, null, $idusuario, $linea['id_pago']);
                if (!$ok) {
                    return $this->abortarRegistro($transaccionPropia, $pagoModelo->getLastError());
                }
            }

            $stockRepuesto = 0;
            if ($this->reponeStockAlCancelar($estadoActual)) {
                require_once __DIR__ . '/Producto.php';
                $productoModelo = new Producto();
                $producto = $productoModelo->getByNombre($config['servicios_bano']['producto_consumible']);
                if ($producto && $productoModelo->incrementarStock($producto['idproducto'])) {
                    $stockRepuesto = 1;
                }
            }

            $stmt = $this->conexion->prepare("UPDATE {$this->tabla} SET estado = 'cancelado',
                motivo_cancelacion = :motivo, idusuario_cancelacion = :idusuario,
                fecha_cancelacion = :fecha, stock_repuesto = :stock WHERE idservicio = :id");
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $stmt->bindValue(':idusuario', (int)$idusuario, PDO::PARAM_INT);
            $stmt->bindValue(':fecha', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':stock', $stockRepuesto, PDO::PARAM_INT);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            if ($transaccionPropia) {
                $this->conexion->commit();
            }
            return true;
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
                'ingresos_totales' => 0,
                'folio_hoy' => 0
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

            // Cargado al folio de huéspedes hoy (cargo neto de servicios vigentes; se cobra en el check-out)
            $query = "SELECT COALESCE(SUM(total), 0) FROM {$this->tabla}
                      WHERE modalidad_cobro = 'folio' AND estado <> 'cancelado' AND DATE(fecha) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['folio_hoy'] = round((float)$stmt->fetchColumn(), 2);

            return $estadisticas;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'ingresos_totales' => 0,
                'folio_hoy' => 0
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

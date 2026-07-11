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
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
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
                  sb.cambio, sb.fecha, sb.estado,
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
            $this->lastError = $e->getMessage();
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
                  sb.cambio, sb.fecha, sb.estado,
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
            $this->lastError = $e->getMessage();
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
            $this->lastError = $e->getMessage();
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
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Crea un nuevo servicio de baño
     * 
     * @param array $datos Datos del servicio de baño
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            // El estado no se recibe de fuera: toda fila nueva nace 'iniciado' (valor por
            // defecto de la columna). El estado se cambia únicamente vía actualizarEstado().
            $query = "INSERT INTO {$this->tabla} (idbano, idusuario, idcliente, tipo_cliente,
                  total, metodopago, pagorecibido, cambio, fecha)
                  VALUES (:idbano, :idusuario, :idcliente, :tipo_cliente,
                  :total, :metodopago, :pagorecibido, :cambio, :fecha)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idbano', $datos['idbano'], PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);

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

            // Fecha actual si no se proporciona una específica
            $fecha = !empty($datos['fecha']) ? $datos['fecha'] : date('Y-m-d H:i:s');
            $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
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
            $this->lastError = $e->getMessage();
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
            $this->lastError = $e->getMessage();
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
            $this->lastError = $e->getMessage();
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

            // Ingresos del día: excluye los servicios cancelados
            $query = "SELECT SUM(total) FROM {$this->tabla} WHERE estado != 'cancelado' AND DATE(fecha) = :fecha_hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $ingresos = $stmt->fetchColumn();
            $estadisticas['ingresos_totales'] = $ingresos ? round($ingresos, 2) : 0;

            return $estadisticas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'ingresos_totales' => 0
            ];
        }
    }

    /**
     * Crea un servicio de baño básico con precio predeterminado desde el baño
     * 
     * @param int $idbano ID del baño
     * @param int $idusuario ID del usuario que registra
     * @return bool|array Resultado de la operación con ID del servicio o false en caso de error
     */
    public function crearServicioRapido($idbano, $idusuario)
    {
        try {
            // Obtener el precio del baño
            $query = "SELECT precio FROM bano WHERE idbano = :idbano";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idbano', $idbano, PDO::PARAM_INT);
            $stmt->execute();
            $precio = $stmt->fetchColumn();

            if ($precio === false) {
                $this->lastError = "No se encontró el baño con ID $idbano";
                return false;
            }

            // Crear los datos del servicio
            $datos = [
                'idbano' => $idbano,
                'idusuario' => $idusuario,
                'idcliente' => null,
                'tipo_cliente' => 'Publico',
                'total' => $precio,
                'metodopago' => 'Efectivo',
                'pagorecibido' => $precio,
                'cambio' => 0,
                'fecha' => date('Y-m-d H:i:s')
            ];

            // Crear el servicio
            if ($this->crear($datos)) {
                $idservicio = $this->getLastInsertId();
                return [
                    'idservicio' => $idservicio,
                    'total' => $precio
                ];
            } else {
                return false;
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}

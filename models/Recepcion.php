<?php

/**
 * Modelo Recepcion
 * 
 * Gestiona las operaciones relacionadas con las recepciones/check-ins en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class Recepcion
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de recepciones en la base de datos
     * @var string
     */
    private $tabla = 'recepcion';

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
     * Obtiene todas las recepciones activas con información de piso
     * 
     * @return array Lista de recepciones
     */
    public function getAll()
    {
        try {
            $query = "SELECT r.*, 
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion, 
                      t.tipo_estancia as tipo_tarifa,
                      u.nombre as nombre_usuario,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      LEFT JOIN tarifas t ON r.idtarifa = t.idtarifa
                      LEFT JOIN usuarios u ON r.idusuario = u.idusuario
                      ORDER BY r.fechaentrada DESC";

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
     * Obtiene todas las recepciones con un estado específico - CORREGIDO con información de piso
     * 
     * @param string $estado Estado de la recepción ('reservado', 'en_curso', 'finalizado', 'cancelado')
     * @return array Lista de recepciones
     */
    public function getAllByEstado($estado)
    {
        try {
            $query = "SELECT r.*, 
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion, 
                      t.tipo_estancia as tipo_tarifa,
                      u.nombre as nombre_usuario,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      LEFT JOIN tarifas t ON r.idtarifa = t.idtarifa
                      LEFT JOIN usuarios u ON r.idusuario = u.idusuario
                      WHERE r.estado = :estado
                      ORDER BY r.fechaentrada DESC";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene una recepción por su ID - CORREGIDO con información de piso
     * 
     * @param int $id ID de la recepción
     * @return array|bool Datos de la recepción o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT r.*, 
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      p.tipodocumento as tipodoc_cliente, p.numdocumento as numdoc_cliente,
                      p.telefono as telefono_cliente, p.email as email_cliente,
                      h.numero as numero_habitacion, h.precio_base,
                      t.tipo_estancia as tipo_tarifa, t.tipo_estancia, t.duracion,
                      u.nombre as nombre_usuario, 
                      th.descripcion as descripcion_habitacion,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      LEFT JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
                      LEFT JOIN tarifas t ON r.idtarifa = t.idtarifa
                      LEFT JOIN usuarios u ON r.idusuario = u.idusuario
                      WHERE r.idrecepcion = :id";

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
     * Crea una nueva recepción
     * 
     * @param array $datos Datos de la recepción
     * @return bool|int ID de la recepción creada o false si falló
     */
    public function crear($datos)
    {
        try {
            // Iniciar transacción para asegurar la integridad de los datos
            $this->conexion->beginTransaction();

            // Verificar disponibilidad de la habitación
            $query = "SELECT estado FROM habitaciones 
          WHERE id_habitacion = :idhabitacion FOR UPDATE";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
            $stmt->execute();
            $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$habitacion || $habitacion['estado'] !== 'disponible') {
                $this->conexion->rollBack();
                $this->lastError = "La habitación no está disponible";
                return false;
            }

            // Recalcular montototal a partir del precio real de la tarifa en BD,
            // nunca confiar en el montototal enviado por el cliente
            $stmtTarifa = $this->conexion->prepare("SELECT precio FROM tarifas WHERE idtarifa = :idtarifa AND estado = 1 FOR UPDATE");
            $stmtTarifa->bindParam(':idtarifa', $datos['idtarifa'], PDO::PARAM_INT);
            $stmtTarifa->execute();
            $tarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);

            if (!$tarifa) {
                $this->conexion->rollBack();
                $this->lastError = "La tarifa seleccionada no es válida";
                return false;
            }

            $datos['montototal'] = (float)$tarifa['precio'];
            $datos['montopagado'] = $datos['montototal'];

            // El cambio se recalcula a partir del dinero recibido real y el monto total real
            if (($datos['metodopago'] ?? null) === 'Efectivo') {
                $dineroRecibido = (float)($datos['dinero_recibido'] ?? 0);
                $datos['cambio'] = max(0, $dineroRecibido - $datos['montototal']);
            } else {
                $datos['cambio'] = null;
            }

            // El estado inicial de una recepción nueva solo puede ser 'reservado' (pendiente de
            // check-in) o 'en_curso' (check-in inmediato) — nunca 'finalizado'/'cancelado' desde
            // el cliente; las transiciones posteriores van por el endpoint cambiarEstado()
            if (!in_array($datos['estado'] ?? null, ['reservado', 'en_curso'], true)) {
                $datos['estado'] = 'en_curso';
            }

            // Crear la recepción
            $query = "INSERT INTO {$this->tabla} (
          idcliente, idhabitacion, idtarifa, idusuario,
          fechaentrada, fechasalida_prevista, montototal, 
          montopagado, estado, observaciones, metodopago, cambio) 
          VALUES (
          :idcliente, :idhabitacion, :idtarifa, :idusuario,
          :fechaentrada, :fechasalida_prevista, :montototal, 
          :montopagado, :estado, :observaciones, :metodopago, :cambio)";

            $stmt = $this->conexion->prepare($query);

            // Vincular parámetros
            $stmt->bindParam(':idcliente', $datos['idcliente'], PDO::PARAM_INT);
            $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
            $stmt->bindParam(':idtarifa', $datos['idtarifa'], PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':fechaentrada', $datos['fechaentrada'], PDO::PARAM_STR);
            $stmt->bindParam(':fechasalida_prevista', $datos['fechasalida_prevista'], PDO::PARAM_STR);
            $stmt->bindParam(':montototal', $datos['montototal'], PDO::PARAM_STR);
            $stmt->bindParam(':montopagado', $datos['montopagado'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            // Método de pago puede ser NULL
            if (empty($datos['metodopago'])) {
                $stmt->bindValue(':metodopago', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':metodopago', $datos['metodopago'], PDO::PARAM_STR);
            }

            // El cambio solo existe en pagos en efectivo
            if (isset($datos['cambio']) && $datos['metodopago'] === 'Efectivo') {
                $stmt->bindParam(':cambio', $datos['cambio'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':cambio', null, PDO::PARAM_NULL);
            }

            // Observaciones puede ser NULL
            if (empty($datos['observaciones'])) {
                $stmt->bindValue(':observaciones', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':observaciones', $datos['observaciones'], PDO::PARAM_STR);
            }

            $result = $stmt->execute();

            if (!$result) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(" ", $stmt->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $idrecepcion = $this->conexion->lastInsertId();

            // Escribir la línea inicial del folio (cargo por la estancia + pago si corresponde),
            // dentro de la misma transacción. `pagos` es la fuente de verdad del folio;
            // montototal/montopagado en esta tabla quedan como cache calculado a partir de ella
            // (ver Pago::registrarLinea/calcularSaldo) y no deben modificarse fuera de este flujo.
            $metodopagoFolio = $datos['metodopago'] ?? 'OTROS';
            $queryCargo = "INSERT INTO pagos (idrecepcion, tipo, concepto, montototal, metodopago, idusuario)
                           VALUES (:idrecepcion, 'cargo', 'Estancia', :monto, :metodopago, :idusuario)";
            $stmtCargo = $this->conexion->prepare($queryCargo);
            $stmtCargo->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
            $stmtCargo->bindParam(':monto', $datos['montototal'], PDO::PARAM_STR);
            $stmtCargo->bindParam(':metodopago', $metodopagoFolio, PDO::PARAM_STR);
            $stmtCargo->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);

            if (!$stmtCargo->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtCargo->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            if ((float)$datos['montopagado'] > 0) {
                $queryPago = "INSERT INTO pagos (idrecepcion, tipo, concepto, montototal, metodopago, idusuario)
                              VALUES (:idrecepcion, 'pago', 'Pago inicial (check-in)', :monto, :metodopago, :idusuario)";
                $stmtPago = $this->conexion->prepare($queryPago);
                $stmtPago->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
                $stmtPago->bindParam(':monto', $datos['montopagado'], PDO::PARAM_STR);
                $stmtPago->bindParam(':metodopago', $metodopagoFolio, PDO::PARAM_STR);
                $stmtPago->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);

                if (!$stmtPago->execute()) {
                    $this->conexion->rollBack();
                    error_log('[' . static::class . '] ' . implode(' ', $stmtPago->errorInfo()));
                    $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                    return false;
                }
            }

            // Actualizar el estado de la habitación si el estado es en_curso
            if ($datos['estado'] === 'en_curso') {
                $estado_habitacion = 'ocupada';
                $query = "UPDATE habitaciones SET estado = :estado 
                 WHERE id_habitacion = :idhabitacion";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':estado', $estado_habitacion, PDO::PARAM_STR);
                $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
                $result = $stmt->execute();

                if (!$result) {
                    $this->conexion->rollBack();
                    error_log('[' . static::class . '] ' . implode(" ", $stmt->errorInfo()));
                    $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                    return false;
                }
            }

            // Confirmar la transacción
            $this->conexion->commit();
            return $idrecepcion;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza una recepción existente
     * 
     * @param int $id ID de la recepción
     * @param array $datos Datos de la recepción
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $this->conexion->beginTransaction();

            // Capturar el estado anterior antes de modificar, para poder comparar
            // correctamente tras el UPDATE (getById() luego devolvería el nuevo estado)
            $recepcion_anterior = $this->getById($id);
            $estado_anterior = $recepcion_anterior ? $recepcion_anterior['estado'] : null;

            // Construir consulta de actualización dinámicamente
            $campos = [];
            $params = [':id' => $id];

            // Mapear campos a actualizar
            $campos_mapeados = [
                'idcliente' => 'idcliente = :idcliente',
                'idhabitacion' => 'idhabitacion = :idhabitacion',
                'idtarifa' => 'idtarifa = :idtarifa',
                'fechaentrada' => 'fechaentrada = :fechaentrada',
                'fechasalida' => 'fechasalida = :fechasalida',
                'fechasalida_prevista' => 'fechasalida_prevista = :fechasalida_prevista',
                'montototal' => 'montototal = :montototal',
                'montopagado' => 'montopagado = :montopagado',
                'estado' => 'estado = :estado',
                'observaciones' => 'observaciones = :observaciones',
                'metodopago' => 'metodopago = :metodopago',
                'cambio' => 'cambio = :cambio'
            ];

            // Añadir campos que existen en los datos
            foreach ($campos_mapeados as $campo => $sql) {
                if (array_key_exists($campo, $datos)) {
                    if ($campo === 'observaciones' && empty($datos[$campo])) {
                        $campos[] = "observaciones = NULL";
                    } else if ($campo === 'cambio') {
                        if (isset($datos[$campo]) && $datos['metodopago'] === 'Efectivo') {
                            $campos[] = $sql;
                            $params[':' . $campo] = (float)$datos[$campo];
                        } else {
                            $campos[] = "cambio = NULL";
                        }
                    } else if ($campo === 'metodopago' && empty($datos[$campo])) {
                        $campos[] = "metodopago = NULL";
                    } else {
                        $campos[] = $sql;
                        $params[':' . $campo] = $datos[$campo];
                    }
                }
            }

            // Si no hay campos para actualizar, retornar
            if (empty($campos)) {
                $this->conexion->rollBack();
                return true;
            }

            // Construir la consulta
            $query = "UPDATE {$this->tabla} SET " . implode(", ", $campos) . " WHERE idrecepcion = :id";

            $stmt = $this->conexion->prepare($query);

            // Vincular parámetros
            foreach ($params as $param => $value) {
                if ($param === ':cambio' && $value !== null) {
                    // Asegurarse de que el cambio se pase como número decimal
                    $stmt->bindValue($param, (float)$value, PDO::PARAM_STR);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }

            $result = $stmt->execute();

            if (!$result) {
                $this->conexion->rollBack();
                error_log("Error al ejecutar la actualización: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            // Actualizar estado de habitación si es necesario
            if (isset($datos['estado']) && $recepcion_anterior) {
                $idhabitacion = $recepcion_anterior['idhabitacion'];

                if ($datos['estado'] === 'en_curso' && $estado_anterior !== 'en_curso') {
                    // Ocupar la habitación
                    $query = "UPDATE habitaciones SET estado = 'ocupada'
                          WHERE id_habitacion = :idhabitacion";
                    $stmt = $this->conexion->prepare($query);
                    $stmt->bindParam(':idhabitacion', $idhabitacion, PDO::PARAM_INT);
                    $result = $stmt->execute();

                    if (!$result) {
                        $this->conexion->rollBack();
                        error_log("Error al actualizar el estado de la habitación: " . print_r($stmt->errorInfo(), true));
                        return false;
                    }
                } else if ($datos['estado'] === 'finalizado' && $estado_anterior !== 'finalizado') {
                    // Pasar la habitación a limpieza
                    $query = "UPDATE habitaciones SET estado = 'limpieza'
                          WHERE id_habitacion = :idhabitacion";
                    $stmt = $this->conexion->prepare($query);
                    $stmt->bindParam(':idhabitacion', $idhabitacion, PDO::PARAM_INT);
                    $result = $stmt->execute();

                    if (!$result) {
                        $this->conexion->rollBack();
                        error_log("Error al actualizar el estado de la habitación: " . print_r($stmt->errorInfo(), true));
                        return false;
                    }
                } else if ($datos['estado'] === 'cancelado' && $estado_anterior !== 'cancelado') {
                    // Liberar la habitación
                    $query = "UPDATE habitaciones SET estado = 'disponible'
                          WHERE id_habitacion = :idhabitacion";
                    $stmt = $this->conexion->prepare($query);
                    $stmt->bindParam(':idhabitacion', $idhabitacion, PDO::PARAM_INT);
                    $result = $stmt->execute();

                    if (!$result) {
                        $this->conexion->rollBack();
                        error_log("Error al actualizar el estado de la habitación: " . print_r($stmt->errorInfo(), true));
                        return false;
                    }
                }
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            error_log("Excepción PDO en actualizar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el estado de una recepción
     * 
     * @param int $id ID de la recepción
     * @param string $estado Nuevo estado
     * @return bool True si se cambió correctamente, False en caso contrario
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $this->conexion->beginTransaction();

            // Obtener datos actuales de la recepción
            $recepcion = $this->getById($id);
            if (!$recepcion) {
                $this->conexion->rollBack();
                $this->lastError = "Recepción no encontrada";
                return false;
            }

            // Actualizar estado de la recepción
            $query = "UPDATE {$this->tabla} SET estado = :estado";

            // Si se está finalizando (checkout), registrar fecha de salida
            if ($estado === 'finalizado' && empty($recepcion['fechasalida'])) {
                $query .= ", fechasalida = :fechasalida";
                $fechaSalida = date('Y-m-d H:i:s');
            }

            $query .= " WHERE idrecepcion = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if (isset($fechaSalida)) {
                $stmt->bindParam(':fechasalida', $fechaSalida, PDO::PARAM_STR);
            }

            $result = $stmt->execute();

            if (!$result) {
                $this->conexion->rollBack();
                return false;
            }

            // Actualizar estado de la habitación según corresponda
            if ($estado === 'finalizado') {
                // Al hacer checkout, marcar habitación para limpieza
                $query = "UPDATE habitaciones SET estado = 'limpieza' 
                          WHERE id_habitacion = :idhabitacion";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idhabitacion', $recepcion['idhabitacion'], PDO::PARAM_INT);
                $result = $stmt->execute();

                if (!$result) {
                    $this->conexion->rollBack();
                    return false;
                }
            } elseif ($estado === 'cancelado' && $recepcion['estado'] !== 'finalizado') {
                // Si se cancela y no estaba finalizado, liberar la habitación
                $query = "UPDATE habitaciones SET estado = 'disponible' 
                          WHERE id_habitacion = :idhabitacion";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':idhabitacion', $recepcion['idhabitacion'], PDO::PARAM_INT);
                $result = $stmt->execute();

                if (!$result) {
                    $this->conexion->rollBack();
                    return false;
                }
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Obtiene las habitaciones disponibles para recepción
     * 
     * @return array Lista de habitaciones disponibles
     */
    public function getHabitacionesDisponibles()
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, p.nombre as piso_nombre 
                      FROM habitaciones h
                      JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                      JOIN pisos p ON h.idpiso = p.idpiso
                      WHERE h.estado = 'disponible'
                      ORDER BY h.numero ASC";
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
     * Obtiene los clientes para el selector
     * 
     * @return array Lista de clientes
     */
    public function getClientes()
    {
        try {
            $query = "SELECT idpersona, CONCAT(nombre, ' ', apellidopaterno) as nombre_completo, 
                      tipodocumento, numdocumento, telefono
                      FROM persona 
                      WHERE estado = 1 
                      ORDER BY nombre, apellidopaterno";
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
     * Obtiene las tarifas disponibles
     * 
     * @return array Lista de tarifas
     */
    public function getTarifas()
    {
        try {
            $query = "SELECT t.*, th.nombre as tipo_habitacion_nombre
                      FROM tarifas t
                      JOIN tipo_habitacion th ON t.id_tipo = th.id_tipo
                      WHERE t.estado = 1
                      ORDER BY t.id_tipo, t.tipo_estancia, t.duracion";
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
     * Calcula las estadísticas de recepción
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        try {
            // Total de recepciones
            $query = "SELECT COUNT(*) as total FROM {$this->tabla}";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Por estado
            $query = "SELECT estado, COUNT(*) as cantidad FROM {$this->tabla} GROUP BY estado";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Construir array de estados
            $estados = [
                'reservado' => 0,
                'en_curso' => 0,
                'finalizado' => 0,
                'cancelado' => 0
            ];

            foreach ($por_estado as $estado) {
                $estados[$estado['estado']] = $estado['cantidad'];
            }

            // Ingresos totales
            $query = "SELECT SUM(montototal) as ingresos FROM {$this->tabla} 
                  WHERE estado IN ('en_curso', 'finalizado')";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0;

            // Check-ins de hoy
            $hoy = date('Y-m-d');
            $query = "SELECT COUNT(*) as checkins_hoy FROM {$this->tabla} 
                  WHERE DATE(fechaentrada) = :hoy";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy);
            $stmt->execute();
            $checkins_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['checkins_hoy'];

            // Check-outs previstos para hoy
            $query = "SELECT COUNT(*) as checkouts_hoy FROM {$this->tabla} 
                  WHERE DATE(fechasalida_prevista) = :hoy AND estado = 'en_curso'";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy);
            $stmt->execute();
            $checkouts_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['checkouts_hoy'];

            // Habitaciones en mantenimiento
            $query = "SELECT COUNT(*) as mantenimiento FROM habitaciones 
                  WHERE estado IN ('mantenimiento', 'limpieza')";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC)['mantenimiento'] ?? 0;

            // Total de habitaciones
            $query = "SELECT COUNT(*) as total_habitaciones FROM habitaciones";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $total_habitaciones = $stmt->fetch(PDO::FETCH_ASSOC)['total_habitaciones'] ?? 0;

            // Ingresos de hoy

            $query = "SELECT SUM(montototal) as ingresos_hoy FROM {$this->tabla} 
                WHERE DATE(fechaentrada) = :hoy AND estado IN ('en_curso', 'finalizado')";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy);
            $stmt->execute();
            $ingresos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['ingresos_hoy'] ?? 0;

            return [
                'total' => $total,
                'reservado' => $estados['reservado'],
                'en_curso' => $estados['en_curso'],
                'finalizado' => $estados['finalizado'],
                'cancelado' => $estados['cancelado'],
                'ingresos_totales' => $ingresos,
                'ingresos_hoy' => $ingresos_hoy,
                'checkins_hoy' => $checkins_hoy,
                'checkouts_hoy' => $checkouts_hoy,
                'mantenimiento' => $mantenimiento,
                'total_habitaciones' => $total_habitaciones
            ];
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total' => 0,
                'reservado' => 0,
                'en_curso' => 0,
                'finalizado' => 0,
                'cancelado' => 0,
                'ingresos_totales' => 0,
                'ingresos_hoy' => 0,
                'checkins_hoy' => 0,
                'checkouts_hoy' => 0,
                'mantenimiento' => 0,
                'total_habitaciones' => 0
            ];
        }
    }

    /**
     * Obtiene las habitaciones en estado de mantenimiento o limpieza con información de piso
     * 
     * @return array Lista de habitaciones en mantenimiento
     */
    public function getHabitacionesEnMantenimiento()
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, p.nombre as piso_nombre 
                  FROM habitaciones h
                  JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                  JOIN pisos p ON h.idpiso = p.idpiso
                  WHERE h.estado IN ('mantenimiento', 'limpieza')
                  ORDER BY h.numero ASC";
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
     * Obtiene el último error ocurrido
     * 
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Sanitiza los datos de entrada
     * 
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitizarDatos($datos)
    {
        $datosSanitizados = [];
        foreach ($datos as $clave => $valor) {
            if ($valor === null) {
                $datosSanitizados[$clave] = null;
            } else if (is_string($valor)) {
                // Sanitizar solo si es un string y no está vacío
                if (trim($valor) === '') {
                    $datosSanitizados[$clave] = null;
                } else {
                    $datosSanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
                }
            } else {
                $datosSanitizados[$clave] = $valor;
            }
        }
        return $datosSanitizados;
    }

    /**
     * Valida los datos de una recepción
     * 
     * @param array $datos Datos a validar
     * @return array Lista de errores, vacía si no hay errores
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar cliente
        if (empty($datos['idcliente'])) {
            $errores[] = 'El cliente es obligatorio.';
        }

        // Validar habitación
        if (empty($datos['idhabitacion'])) {
            $errores[] = 'La habitación es obligatoria.';
        }

        // Validar tarifa
        if (empty($datos['idtarifa'])) {
            $errores[] = 'La tarifa es obligatoria.';
        }

        // Validar fechas
        if (empty($datos['fechaentrada'])) {
            $errores[] = 'La fecha de entrada es obligatoria.';
        }

        if (empty($datos['fechasalida_prevista'])) {
            $errores[] = 'La fecha de salida prevista es obligatoria.';
        } elseif (!empty($datos['fechaentrada'])) {
            $entrada = strtotime($datos['fechaentrada']);
            $salidaPrevista = strtotime($datos['fechasalida_prevista']);

            if ($entrada === false || $salidaPrevista === false) {
                $errores[] = 'El formato de las fechas no es válido.';
            } elseif ($salidaPrevista <= $entrada) {
                $errores[] = 'La fecha de salida prevista debe ser posterior a la fecha de entrada.';
            }
        }

        // Validar montos
        if (!isset($datos['montototal']) || $datos['montototal'] <= 0) {
            $errores[] = 'El monto total debe ser mayor que cero.';
        }

        if (!isset($datos['montopagado']) || $datos['montopagado'] < 0) {
            $errores[] = 'El monto pagado no puede ser negativo.';
        }

        // Validar estado
        if (empty($datos['estado']) || !in_array($datos['estado'], ['reservado', 'en_curso', 'finalizado', 'cancelado'])) {
            $errores[] = 'El estado debe ser válido (reservado, en_curso, finalizado, cancelado).';
        }

        // Validar método de pago si se proporciona
        if (!empty($datos['metodopago']) && !in_array($datos['metodopago'], ['Efectivo', 'QR', 'Otros'])) {
            $errores[] = 'El método de pago debe ser válido (Efectivo, QR, Otros).';
        }

        // Si es efectivo, el cambio debe ser válido (no negativo)
        if (isset($datos['metodopago']) && $datos['metodopago'] === 'Efectivo' && isset($datos['cambio']) && $datos['cambio'] < 0) {
            $errores[] = 'El cambio no puede ser negativo.';
        }

        return $errores;
    }
}

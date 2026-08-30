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
     * Bloquea la fila de recepción (FOR UPDATE) dentro de la transacción actual y
     * devuelve sus datos completos (mismo shape que getById()). Debe llamarse siempre
     * que actualizar()/cambiarEstado() vayan a decidir una transición de estado, para
     * evitar que dos peticiones concurrentes sobre el mismo registro pisen el estado
     * de la habitación de forma inconsistente. Orden canónico de bloqueo: recepcion -> habitaciones.
     *
     * @param int $id ID de la recepción
     * @return array|bool Datos de la recepción o false si no existe
     */
    private function getByIdForUpdate($id)
    {
        $stmt = $this->conexion->prepare("SELECT idrecepcion FROM {$this->tabla} WHERE idrecepcion = :id FOR UPDATE");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        return $this->getById($id);
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
                      t.tipo_estancia as tipo_tarifa, t.tipo_estancia, t.duracion, t.precio as precio_tarifa,
                      u.nombre as nombre_usuario, 
                      th.descripcion as descripcion_habitacion, th.nombre as tipo_nombre,
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

            // Impedir overbooking: la habitación no puede tener otra reserva/estancia
            // solapada con este rango de fechas (comprobado tras el FOR UPDATE de habitaciones).
            if ($this->existeSolape($datos['idhabitacion'], $datos['fechaentrada'], $datos['fechasalida_prevista'])) {
                $this->conexion->rollBack();
                $this->lastError = 'La habitación ya tiene una reserva o estancia que se solapa con esas fechas.';
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

            // Bloquear la fila de recepción (orden canónico: recepcion -> habitaciones)
            // para evitar que dos actualizaciones concurrentes sobre el mismo registro
            // pisen el estado de la habitación de forma inconsistente.
            $recepcion_anterior = $this->getByIdForUpdate($id);
            $estado_anterior = $recepcion_anterior ? $recepcion_anterior['estado'] : null;

            // Si cambian las fechas de la estancia, revalidar solape sobre la misma
            // habitación (excluyéndose a sí misma). Bloquear habitaciones después de
            // recepcion (orden canónico) antes de la comprobación.
            if (
                $recepcion_anterior
                && (array_key_exists('fechaentrada', $datos) || array_key_exists('fechasalida_prevista', $datos))
            ) {
                $entradaNueva = $datos['fechaentrada'] ?? $recepcion_anterior['fechaentrada'];
                $salidaNueva = $datos['fechasalida_prevista'] ?? $recepcion_anterior['fechasalida_prevista'];

                $stmtLockHab = $this->conexion->prepare(
                    "SELECT estado FROM habitaciones WHERE id_habitacion = :idhabitacion FOR UPDATE"
                );
                $stmtLockHab->bindValue(':idhabitacion', (int) $recepcion_anterior['idhabitacion'], PDO::PARAM_INT);
                $stmtLockHab->execute();

                if ($this->existeSolape((int) $recepcion_anterior['idhabitacion'], $entradaNueva, $salidaNueva, (int) $id)) {
                    $this->conexion->rollBack();
                    $this->lastError = 'Las nuevas fechas se solapan con otra reserva o estancia de esa habitación.';
                    return false;
                }
            }

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

                if (
                    ($datos['estado'] === 'en_curso' && $estado_anterior !== 'en_curso') ||
                    ($datos['estado'] === 'finalizado' && $estado_anterior !== 'finalizado') ||
                    ($datos['estado'] === 'cancelado' && $estado_anterior !== 'cancelado')
                ) {
                    // Bloquear la habitación antes de mover su estado (mismo orden que crear()/cambiarEstado())
                    $stmtLock = $this->conexion->prepare("SELECT estado FROM habitaciones WHERE id_habitacion = :idhabitacion FOR UPDATE");
                    $stmtLock->bindParam(':idhabitacion', $idhabitacion, PDO::PARAM_INT);
                    $stmtLock->execute();

                    $estado_habitacion_nuevo = match ($datos['estado']) {
                        'en_curso' => 'ocupada',
                        'finalizado' => 'limpieza',
                        'cancelado' => 'disponible',
                        default => null,
                    };

                    $query = "UPDATE habitaciones SET estado = :estado_habitacion
                          WHERE id_habitacion = :idhabitacion";
                    $stmt = $this->conexion->prepare($query);
                    $stmt->bindParam(':estado_habitacion', $estado_habitacion_nuevo, PDO::PARAM_STR);
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

            // Bloquear la fila de recepción (orden canónico: recepcion -> habitaciones) para
            // evitar dos checkouts/cancelaciones concurrentes sobre el mismo registro.
            $recepcion = $this->getByIdForUpdate($id);
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
            if ($estado === 'finalizado' || ($estado === 'cancelado' && $recepcion['estado'] !== 'finalizado')) {
                // Bloquear la habitación antes de mover su estado (mismo orden que crear()/actualizar())
                $stmtLock = $this->conexion->prepare("SELECT estado FROM habitaciones WHERE id_habitacion = :idhabitacion FOR UPDATE");
                $stmtLock->bindParam(':idhabitacion', $recepcion['idhabitacion'], PDO::PARAM_INT);
                $stmtLock->execute();

                $estado_habitacion_nuevo = $estado === 'finalizado' ? 'limpieza' : 'disponible';

                $query = "UPDATE habitaciones SET estado = :estado_habitacion
                          WHERE id_habitacion = :idhabitacion";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':estado_habitacion', $estado_habitacion_nuevo, PDO::PARAM_STR);
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
     * Mueve una recepción en_curso a otra habitación, liberando la anterior y
     * dejando rastro en `recepcion_movimientos`. Orden canónico de bloqueo:
     * recepcion -> habitaciones (ambas habitaciones bloqueadas ordenadas por
     * id_habitacion ASC para evitar deadlock con otras transacciones que también
     * bloqueen dos habitaciones). Si el precio_base de la habitación destino es
     * mayor al de la actual, la diferencia se registra como cargo en el folio.
     *
     * @param int $id ID de la recepción
     * @param int $idHabitacionDestino ID de la habitación destino
     * @param int $idusuario Usuario que realiza el cambio
     * @param string|null $motivo Motivo del cambio (opcional)
     * @return bool True si se realizó el cambio, False en caso contrario
     */
    public function cambiarHabitacion($id, $idHabitacionDestino, $idusuario, $motivo = null)
    {
        try {
            $this->conexion->beginTransaction();

            $recepcion = $this->getByIdForUpdate($id);
            if (!$recepcion) {
                $this->conexion->rollBack();
                $this->lastError = 'Recepción no encontrada.';
                return false;
            }

            if ($recepcion['estado'] !== 'en_curso') {
                $this->conexion->rollBack();
                $this->lastError = 'Solo se puede cambiar de habitación una estancia en curso.';
                return false;
            }

            $idHabitacionOrigen = (int)$recepcion['idhabitacion'];

            if ($idHabitacionOrigen === (int)$idHabitacionDestino) {
                $this->conexion->rollBack();
                $this->lastError = 'La habitación destino debe ser distinta a la actual.';
                return false;
            }

            // Bloquear ambas habitaciones en orden ascendente de ID, sin importar
            // cuál es origen/destino, para no deadlockear con otro cambio simultáneo.
            $idsOrdenados = [$idHabitacionOrigen, (int)$idHabitacionDestino];
            sort($idsOrdenados);

            $stmtLock = $this->conexion->prepare(
                "SELECT id_habitacion, estado, precio_base FROM habitaciones WHERE id_habitacion IN (:id1, :id2) ORDER BY id_habitacion ASC FOR UPDATE"
            );
            $stmtLock->bindValue(':id1', $idsOrdenados[0], PDO::PARAM_INT);
            $stmtLock->bindValue(':id2', $idsOrdenados[1], PDO::PARAM_INT);
            $stmtLock->execute();
            $habitaciones = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

            $habitacionesPorId = [];
            foreach ($habitaciones as $hab) {
                $habitacionesPorId[(int)$hab['id_habitacion']] = $hab;
            }

            $habitacionDestino = $habitacionesPorId[(int)$idHabitacionDestino] ?? null;
            $habitacionOrigen = $habitacionesPorId[$idHabitacionOrigen] ?? null;

            // Revalidar disponibilidad DESPUÉS del FOR UPDATE, nunca antes
            if (!$habitacionDestino || $habitacionDestino['estado'] !== 'disponible') {
                $this->conexion->rollBack();
                $this->lastError = 'La habitación destino ya no está disponible.';
                return false;
            }

            $stmtMoverRecepcion = $this->conexion->prepare(
                "UPDATE {$this->tabla} SET idhabitacion = :idhabitacion WHERE idrecepcion = :id"
            );
            $stmtMoverRecepcion->bindParam(':idhabitacion', $idHabitacionDestino, PDO::PARAM_INT);
            $stmtMoverRecepcion->bindParam(':id', $id, PDO::PARAM_INT);

            if (!$stmtMoverRecepcion->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtMoverRecepcion->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $stmtOrigen = $this->conexion->prepare("UPDATE habitaciones SET estado = 'limpieza' WHERE id_habitacion = :id");
            $stmtOrigen->bindParam(':id', $idHabitacionOrigen, PDO::PARAM_INT);
            if (!$stmtOrigen->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtOrigen->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $stmtDestino = $this->conexion->prepare("UPDATE habitaciones SET estado = 'ocupada' WHERE id_habitacion = :id");
            $stmtDestino->bindParam(':id', $idHabitacionDestino, PDO::PARAM_INT);
            if (!$stmtDestino->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtDestino->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $stmtMov = $this->conexion->prepare(
                "INSERT INTO recepcion_movimientos (idrecepcion, tipo, valor_anterior, valor_nuevo, motivo, idusuario)
                 VALUES (:idrecepcion, 'cambio_habitacion', :anterior, :nuevo, :motivo, :idusuario)"
            );
            $stmtMov->bindParam(':idrecepcion', $id, PDO::PARAM_INT);
            $valorAnterior = (string)($habitacionOrigen['id_habitacion'] ?? $idHabitacionOrigen);
            $valorNuevo = (string)$idHabitacionDestino;
            $stmtMov->bindParam(':anterior', $valorAnterior, PDO::PARAM_STR);
            $stmtMov->bindParam(':nuevo', $valorNuevo, PDO::PARAM_STR);
            if (empty($motivo)) {
                $stmtMov->bindValue(':motivo', null, PDO::PARAM_NULL);
            } else {
                $stmtMov->bindParam(':motivo', $motivo, PDO::PARAM_STR);
            }
            $stmtMov->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);

            if (!$stmtMov->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtMov->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            // Diferencia de tarifa: si la habitación destino es más cara, se carga la
            // diferencia al folio dentro de la misma transacción (fuente de verdad: pagos)
            $precioOrigen = (float)($habitacionOrigen['precio_base'] ?? 0);
            $precioDestino = (float)($habitacionDestino['precio_base'] ?? 0);
            $diferencia = $precioDestino - $precioOrigen;

            if ($diferencia > 0.01) {
                $numeroDestino = $idHabitacionDestino;
                $concepto = 'Cambio de habitación (diferencia de tarifa)';
                $queryCargo = "INSERT INTO pagos (idrecepcion, tipo, concepto, montototal, metodopago, idusuario)
                               VALUES (:idrecepcion, 'cargo', :concepto, :monto, 'OTROS', :idusuario)";
                $stmtCargo = $this->conexion->prepare($queryCargo);
                $stmtCargo->bindParam(':idrecepcion', $id, PDO::PARAM_INT);
                $stmtCargo->bindParam(':concepto', $concepto, PDO::PARAM_STR);
                $stmtCargo->bindParam(':monto', $diferencia, PDO::PARAM_STR);
                $stmtCargo->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);

                if (!$stmtCargo->execute()) {
                    $this->conexion->rollBack();
                    error_log('[' . static::class . '] ' . implode(' ', $stmtCargo->errorInfo()));
                    $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                    return false;
                }

                // Recalcular el cache de recepcion a partir del folio real (mismo criterio que Pago::registrarLinea)
                $stmtSaldo = $this->conexion->prepare(
                    "SELECT
                        COALESCE(SUM(CASE WHEN p.tipo = 'cargo' THEN p.montototal
                            WHEN p.tipo = 'reverso' AND orig.tipo = 'cargo' THEN -p.montototal ELSE 0 END), 0) AS total_cargos,
                        COALESCE(SUM(CASE WHEN p.tipo = 'pago' THEN p.montototal
                            WHEN p.tipo = 'reverso' AND orig.tipo = 'pago' THEN -p.montototal ELSE 0 END), 0) AS total_pagado
                     FROM pagos p LEFT JOIN pagos orig ON orig.id_pago = p.id_pago_reversado
                     WHERE p.idrecepcion = :idrecepcion FOR UPDATE"
                );
                $stmtSaldo->bindParam(':idrecepcion', $id, PDO::PARAM_INT);
                $stmtSaldo->execute();
                $saldo = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

                $stmtCache = $this->conexion->prepare(
                    "UPDATE {$this->tabla} SET montototal = :montototal, montopagado = :montopagado WHERE idrecepcion = :id"
                );
                $stmtCache->bindValue(':montototal', $saldo['total_cargos'], PDO::PARAM_STR);
                $stmtCache->bindValue(':montopagado', $saldo['total_pagado'], PDO::PARAM_STR);
                $stmtCache->bindParam(':id', $id, PDO::PARAM_INT);

                if (!$stmtCache->execute()) {
                    $this->conexion->rollBack();
                    error_log('[' . static::class . '] ' . implode(' ', $stmtCache->errorInfo()));
                    $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
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
     * Historial de cambios de habitación/extensión de una recepción, en orden cronológico.
     *
     * @param int $idrecepcion
     * @return array
     */
    public function getMovimientos($idrecepcion)
    {
        try {
            $query = "SELECT m.*, u.nombre as nombre_usuario,
                      ho.numero as numero_origen, hd.numero as numero_destino
                      FROM recepcion_movimientos m
                      LEFT JOIN usuarios u ON m.idusuario = u.idusuario
                      LEFT JOIN habitaciones ho ON ho.id_habitacion = m.valor_anterior
                      LEFT JOIN habitaciones hd ON hd.id_habitacion = m.valor_nuevo
                      WHERE m.idrecepcion = :idrecepcion
                      ORDER BY m.fechacreacion ASC, m.idmovimiento ASC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
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
     * Salidas previstas para hoy: estancias en curso cuya fecha de salida prevista
     * cae hoy. Ordenadas por hora de salida.
     *
     * @return array
     */
    public function getSalidasHoy()
    {
        try {
            $query = "SELECT r.*,
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      WHERE r.estado = 'en_curso' AND DATE(r.fechasalida_prevista) = CURDATE()
                      ORDER BY r.fechasalida_prevista ASC";
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
     * Huéspedes en casa (in-house): todas las estancias en curso, con el saldo real
     * del folio calculado por subconsulta contra `pagos` (los reversos restan del
     * tipo de línea que reversan).
     *
     * @return array
     */
    public function getInHouse()
    {
        try {
            $query = "SELECT r.*,
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion,
                      piso.nombre as piso_nombre,
                      COALESCE((
                        SELECT SUM(CASE
                            WHEN pg.tipo = 'cargo' THEN pg.montototal
                            WHEN pg.tipo = 'pago' THEN -pg.montototal
                            WHEN pg.tipo = 'reverso' AND og.tipo = 'cargo' THEN -pg.montototal
                            WHEN pg.tipo = 'reverso' AND og.tipo = 'pago' THEN pg.montototal
                            ELSE 0 END)
                        FROM pagos pg LEFT JOIN pagos og ON og.id_pago = pg.id_pago_reversado
                        WHERE pg.idrecepcion = r.idrecepcion
                      ), 0) AS saldo
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      WHERE r.estado = 'en_curso'
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
     * Room rack: una fila por habitación (siempre COUNT(*) FROM habitaciones filas),
     * con la recepción activa asociada si existe (estancia en curso, o reserva cuya
     * entrada es hoy). La subconsulta LIMIT 1 garantiza como máximo una recepción por
     * habitación, priorizando 'en_curso' sobre 'reservado'.
     *
     * @return array
     */
    public function getMapaHabitaciones()
    {
        try {
            $query = "SELECT h.id_habitacion, h.numero, h.estado, h.precio_base,
                      piso.nombre as piso_nombre, th.nombre as tipo_nombre,
                      r.idrecepcion, r.estado as estado_recepcion, r.fechaentrada, r.fechasalida_prevista,
                      CASE WHEN p.idpersona IS NULL THEN NULL
                           ELSE CONCAT(p.nombre, ' ', p.apellidopaterno) END as huesped
                      FROM habitaciones h
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      LEFT JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
                      LEFT JOIN {$this->tabla} r ON r.idrecepcion = (
                          SELECT r2.idrecepcion FROM {$this->tabla} r2
                          WHERE r2.idhabitacion = h.id_habitacion
                            AND (r2.estado = 'en_curso'
                                 OR (r2.estado = 'reservado' AND DATE(r2.fechaentrada) = CURDATE()))
                          ORDER BY FIELD(r2.estado, 'en_curso', 'reservado'), r2.fechaentrada ASC
                          LIMIT 1
                      )
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      ORDER BY piso.nombre ASC, h.numero ASC";
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
     * KPIs del día para la barra del panel. Todos los cálculos los hace el motor SQL;
     * el controlador solo reenvía el array.
     *
     * @return array{ocupacion_pct:float, adr:float, ingresos_dia:float,
     *               llegadas_pendientes:int, salidas_pendientes:int, habitaciones_sucias:int}
     */
    public function getKpisDia()
    {
        $base = [
            'ocupacion_pct' => 0.0,
            'adr' => 0.0,
            'ingresos_dia' => 0.0,
            'llegadas_pendientes' => 0,
            'salidas_pendientes' => 0,
            'habitaciones_sucias' => 0,
        ];

        try {
            // Ocupación: habitaciones ocupadas / total de habitaciones
            $stmt = $this->conexion->query(
                "SELECT
                    (SELECT COUNT(*) FROM habitaciones) AS total,
                    (SELECT COUNT(*) FROM habitaciones WHERE estado = 'ocupada') AS ocupadas,
                    (SELECT COUNT(*) FROM habitaciones WHERE estado = 'limpieza') AS sucias"
            );
            $hab = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'ocupadas' => 0, 'sucias' => 0];
            $total = (int) $hab['total'];
            $base['habitaciones_sucias'] = (int) $hab['sucias'];
            if ($total > 0) {
                $base['ocupacion_pct'] = round((int) $hab['ocupadas'] * 100 / $total, 1);
            }

            // ADR (estándar hotelero): ingreso de alojamiento / habitaciones vendidas.
            // "Vendidas" = estancias en curso o finalizadas cuyo rango cubre hoy.
            $stmt = $this->conexion->query(
                "SELECT COALESCE(SUM(t.precio), 0) AS alojamiento, COUNT(*) AS vendidas
                 FROM {$this->tabla} r
                 LEFT JOIN tarifas t ON r.idtarifa = t.idtarifa
                 WHERE r.estado IN ('en_curso', 'finalizado')
                   AND DATE(r.fechaentrada) <= CURDATE()
                   AND (r.fechasalida_prevista IS NULL OR DATE(r.fechasalida_prevista) >= CURDATE())"
            );
            $adr = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['alojamiento' => 0, 'vendidas' => 0];
            if ((int) $adr['vendidas'] > 0) {
                $base['adr'] = round((float) $adr['alojamiento'] / (int) $adr['vendidas'], 2);
            }

            // Ingresos del día: pagos cobrados hoy (pagos.fechacreacion), neto de reversos de pago
            $stmt = $this->conexion->query(
                "SELECT COALESCE(SUM(CASE
                    WHEN p.tipo = 'pago' THEN p.montototal
                    WHEN p.tipo = 'reverso' AND og.tipo = 'pago' THEN -p.montototal
                    ELSE 0 END), 0) AS ingresos
                 FROM pagos p LEFT JOIN pagos og ON og.id_pago = p.id_pago_reversado
                 WHERE DATE(p.fechacreacion) = CURDATE()"
            );
            $base['ingresos_dia'] = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0);

            // Pendientes de hoy
            $stmt = $this->conexion->query(
                "SELECT
                    (SELECT COUNT(*) FROM {$this->tabla}
                     WHERE estado = 'reservado' AND DATE(fechaentrada) = CURDATE()) AS llegadas,
                    (SELECT COUNT(*) FROM {$this->tabla}
                     WHERE estado = 'en_curso' AND DATE(fechasalida_prevista) = CURDATE()) AS salidas"
            );
            $pend = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['llegadas' => 0, 'salidas' => 0];
            $base['llegadas_pendientes'] = (int) $pend['llegadas'];
            $base['salidas_pendientes'] = (int) $pend['salidas'];

            return $base;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return $base;
        }
    }

    /**
     * Comprueba si una habitación ya tiene una reserva o estancia que se solapa con
     * el rango [entrada, salida). Dos rangos se solapan si entrada < salida_existente
     * y salida > entrada_existente. Se invoca DENTRO de la transacción de
     * crear()/actualizar(), después del FOR UPDATE sobre `habitaciones`.
     *
     * Fail-open: si la consulta falla se loguea y se devuelve false (no bloquea la
     * operación por un fallo de infraestructura; mismo criterio que el rate-limit de login).
     *
     * @param int         $idhabitacion
     * @param string      $entrada    Fecha/hora de entrada (Y-m-d H:i:s)
     * @param string      $salida     Fecha/hora de salida prevista
     * @param int|null    $excluirId  Recepción a excluir de la comprobación (al editarse a sí misma)
     * @return bool
     */
    public function existeSolape($idhabitacion, $entrada, $salida, $excluirId = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tabla}
                      WHERE idhabitacion = :idhabitacion
                        AND estado IN ('reservado', 'en_curso')
                        AND fechaentrada < :salida
                        AND fechasalida_prevista > :entrada
                        AND (:excluir IS NULL OR idrecepcion <> :excluir)";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindValue(':idhabitacion', (int) $idhabitacion, PDO::PARAM_INT);
            $stmt->bindValue(':salida', $salida, PDO::PARAM_STR);
            $stmt->bindValue(':entrada', $entrada, PDO::PARAM_STR);
            if ($excluirId === null) {
                $stmt->bindValue(':excluir', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':excluir', (int) $excluirId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Búsqueda global de reserva/huésped para el Select2 remoto del módulo.
     * Coincide contra nombre/apellido/documento del huésped, número de habitación
     * e id de recepción. Named params distintos (:q1..:q5) porque la conexión usa
     * ATTR_EMULATE_PREPARES=false y no admite reusar un named param.
     *
     * @param string $termino
     * @param int    $limite
     * @return array
     */
    public function buscarGlobal($termino, $limite)
    {
        try {
            $like = '%' . $termino . '%';
            $query = "SELECT r.idrecepcion, r.estado, r.fechaentrada, r.fechasalida_prevista,
                      h.numero as numero_habitacion,
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as huesped,
                      p.numdocumento
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      WHERE p.nombre LIKE :q1
                         OR p.apellidopaterno LIKE :q2
                         OR p.numdocumento LIKE :q3
                         OR h.numero LIKE :q4
                         OR CAST(r.idrecepcion AS CHAR) LIKE :q5
                      ORDER BY r.fechaentrada DESC
                      LIMIT :limite";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q4', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q5', $like, PDO::PARAM_STR);
            $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Reservas cuya fecha de entrada prevista es hoy (llegadas de hoy), sin importar
     * si ya vencieron dentro del día. Solo estado 'reservado'.
     *
     * @return array
     */
    public function getLlegadasHoy()
    {
        try {
            $query = "SELECT r.*,
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      WHERE r.estado = 'reservado' AND DATE(r.fechaentrada) = CURDATE()
                      ORDER BY r.fechaentrada ASC";
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
     * Reservas con fecha de entrada prevista futura (después de hoy). Solo estado 'reservado'.
     *
     * @return array
     */
    public function getReservasProximas()
    {
        try {
            $query = "SELECT r.*,
                      p.nombre as nombre_cliente, p.apellidopaterno as apellido_cliente,
                      h.numero as numero_habitacion,
                      piso.nombre as piso_nombre
                      FROM {$this->tabla} r
                      LEFT JOIN persona p ON r.idcliente = p.idpersona
                      LEFT JOIN habitaciones h ON r.idhabitacion = h.id_habitacion
                      LEFT JOIN pisos piso ON h.idpiso = piso.idpiso
                      WHERE r.estado = 'reservado' AND DATE(r.fechaentrada) > CURDATE()
                      ORDER BY r.fechaentrada ASC";
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
     * Libera automáticamente las reservas ('reservado') cuya fecha de entrada prevista
     * venció hace más de $horas horas: pasan a 'cancelado' con una observación de
     * no-show, y su habitación (si seguía 'reservada'/'ocupada' por esta reserva)
     * vuelve a 'disponible'. Fail-open: cualquier error se loguea pero nunca bloquea
     * la carga de index.php (mismo criterio que el rate-limit de login). No toca
     * nunca reservas 'en_curso'.
     *
     * @param int $horas Umbral de horas de gracia tras la entrada prevista
     * @return int Cantidad de reservas liberadas
     */
    public function liberarNoShows($horas)
    {
        try {
            $query = "SELECT idrecepcion, idhabitacion FROM {$this->tabla}
                      WHERE estado = 'reservado'
                      AND fechaentrada < DATE_SUB(NOW(), INTERVAL :horas HOUR)
                      FOR UPDATE";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindValue(':horas', (int)$horas, PDO::PARAM_INT);

            $this->conexion->beginTransaction();
            $stmt->execute();
            $vencidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($vencidas)) {
                $this->conexion->commit();
                return 0;
            }

            // Nota: una reserva ('reservado') nunca cambia habitaciones.estado — solo
            // crear() con estado 'en_curso' lo hace — así que la habitación ya sigue
            // 'disponible' y no requiere ningún UPDATE adicional aquí.
            $observacion = 'Liberada automáticamente por no-show (sin check-in tras ' . (int)$horas . ' horas).';

            foreach ($vencidas as $reserva) {
                $stmtUpd = $this->conexion->prepare(
                    "UPDATE {$this->tabla}
                     SET estado = 'cancelado',
                         observaciones = TRIM(CONCAT(COALESCE(observaciones, ''), ' ', :observacion))
                     WHERE idrecepcion = :id"
                );
                $stmtUpd->bindValue(':observacion', $observacion, PDO::PARAM_STR);
                $stmtUpd->bindValue(':id', $reserva['idrecepcion'], PDO::PARAM_INT);
                $stmtUpd->execute();
            }

            $this->conexion->commit();
            return count($vencidas);
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('[' . static::class . '] ' . $e->getMessage());
            return 0;
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
        if (!empty($datos['metodopago']) && !in_array($datos['metodopago'], ['Efectivo', 'QR', 'OTROS'])) {
            $errores[] = 'El método de pago debe ser válido (Efectivo, QR, OTROS).';
        }

        // Si es efectivo, el cambio debe ser válido (no negativo)
        if (isset($datos['metodopago']) && $datos['metodopago'] === 'Efectivo' && isset($datos['cambio']) && $datos['cambio'] < 0) {
            $errores[] = 'El cambio no puede ser negativo.';
        }

        return $errores;
    }
}

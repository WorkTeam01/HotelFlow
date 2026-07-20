<?php

/**
 * Modelo AsignacionLimpieza
 * 
 * Gestiona las operaciones relacionadas con las asignaciones de limpieza en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class AsignacionLimpieza
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de asignaciones de limpieza en la base de datos
     * @var string
     */
    private $tabla = 'asignaciones_limpieza';

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
     * Obtiene todas las asignaciones de limpieza
     * 
     * @param bool $incluirRelaciones Si es true, incluye información de usuarios y habitaciones
     * @return array Lista de asignaciones de limpieza
     */
    public function getAll($incluirRelaciones = true)
    {
        try {
            $query = "SELECT a.* ";

            if ($incluirRelaciones) {
                $query .= ", CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                         h.numero as numero_habitacion ";
            }

            $query .= "FROM {$this->tabla} a ";

            if ($incluirRelaciones) {
                $query .= "LEFT JOIN usuarios u ON a.idusuario = u.idusuario
                          LEFT JOIN habitaciones h ON a.idhabitacion = h.id_habitacion ";
            }

            $query .= "ORDER BY a.fecha DESC, a.hora DESC";

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
     * Obtiene una asignación de limpieza por su ID
     * 
     * @param int $id ID de la asignación de limpieza
     * @param bool $incluirRelaciones Si es true, incluye información de usuarios y habitaciones
     * @return array|bool Datos de la asignación de limpieza o false si no existe
     */
    public function getById($id, $incluirRelaciones = true)
    {
        try {
            $query = "SELECT a.* ";

            if ($incluirRelaciones) {
                $query .= ", CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                         h.numero as numero_habitacion ";
            }

            $query .= "FROM {$this->tabla} a ";

            if ($incluirRelaciones) {
                $query .= "LEFT JOIN usuarios u ON a.idusuario = u.idusuario
                          LEFT JOIN habitaciones h ON a.idhabitacion = h.id_habitacion ";
            }

            $query .= "WHERE a.idasignacion = :id";

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
     * Obtiene todas las habitaciones disponibles para asignaciones
     * 
     * @param int|null $id_asignacion ID de la asignación actual (para edición)
     * @return array Lista de habitaciones
     */
    public function getHabitaciones($id_asignacion = null)
    {
        try {
            // Si estamos editando, primero debemos incluir la habitación de la asignación actual
            // sin importar su estado
            if ($id_asignacion) {
                $query = "SELECT h.id_habitacion, h.numero, th.nombre as tipo 
                    FROM habitaciones h
                    JOIN tipo_habitacion th ON h.id_tipo = th.id_tipo
                    WHERE h.estado = 'limpieza'
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM {$this->tabla} al 
                        WHERE al.idhabitacion = h.id_habitacion 
                        AND al.estado IN ('pendiente', 'enprogreso')
                        AND al.idasignacion != :id_asignacion
                    )
                    UNION
                    SELECT h.id_habitacion, h.numero, th.nombre as tipo 
                    FROM habitaciones h
                    JOIN tipo_habitacion th ON h.id_tipo = th.id_tipo
                    JOIN {$this->tabla} al ON h.id_habitacion = al.idhabitacion
                    WHERE al.idasignacion = :id_asignacion_actual
                    ORDER BY numero";

                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':id_asignacion', $id_asignacion, PDO::PARAM_INT);
                $stmt->bindParam(':id_asignacion_actual', $id_asignacion, PDO::PARAM_INT);
            }
            // Para creación, solo mostrar habitaciones disponibles en estado limpieza
            else {
                $query = "SELECT h.id_habitacion, h.numero, th.nombre as tipo 
                    FROM habitaciones h
                    JOIN tipo_habitacion th ON h.id_tipo = th.id_tipo
                    WHERE h.estado = 'limpieza'
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM {$this->tabla} al 
                        WHERE al.idhabitacion = h.id_habitacion 
                        AND al.estado IN ('pendiente', 'enprogreso')
                    )
                    ORDER BY h.numero";

                $stmt = $this->conexion->prepare($query);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Verifica si una habitación ya tiene asignaciones pendientes o en progreso
     * 
     * @param int $id_habitacion ID de la habitación
     * @param int|null $id_asignacion ID de la asignación actual (para excluirla en la verificación)
     * @return bool True si la habitación ya tiene asignaciones, False en caso contrario
     */
    public function habitacionTieneAsignacionesPendientes($id_habitacion, $id_asignacion = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tabla} 
                WHERE idhabitacion = :idhabitacion 
                AND estado IN ('pendiente', 'enprogreso')";

            // Si estamos editando, excluir la asignación actual
            if ($id_asignacion) {
                $query .= " AND idasignacion != :id_asignacion";
            }

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idhabitacion', $id_habitacion, PDO::PARAM_INT);

            if ($id_asignacion) {
                $stmt->bindParam(':id_asignacion', $id_asignacion, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Obtiene todos los usuarios de limpieza
     * 
     * @return array Lista de usuarios de limpieza
     */
    public function getUsuariosLimpieza()
    {
        try {
            $query = "SELECT idusuario, CONCAT(nombre, ' ', apellidop) as nombre_completo
                     FROM usuarios 
                     WHERE cargo = 'limpieza' AND estado = 1
                     ORDER BY nombre";
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
     * Crea una nueva asignación de limpieza
     * 
     * @param array $datos Datos de la asignación de limpieza
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            // Validar datos antes de crear
            $errores = $this->validarDatos($datos);
            if (!empty($errores)) {
                $this->lastError = implode(', ', $errores);
                return false;
            }

            $query = "INSERT INTO {$this->tabla} (idusuario, idhabitacion, fecha, hora, estado, observaciones) 
                  VALUES (:idusuario, :idhabitacion, :fecha, :hora, :estado, :observaciones)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $datos['fecha'], PDO::PARAM_STR);
            $stmt->bindParam(':hora', $datos['hora'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            // Manejar observaciones que pueden ser NULL
            if (!empty($datos['observaciones'])) {
                $stmt->bindParam(':observaciones', $datos['observaciones'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':observaciones', null, PDO::PARAM_NULL);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza una asignación de limpieza existente
     * 
     * @param int $id ID de la asignación de limpieza
     * @param array $datos Datos de la asignación de limpieza
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            // Verificar si la asignación está en estado "verificada"
            $query = "SELECT estado FROM {$this->tabla} WHERE idasignacion = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $estado_actual = $stmt->fetchColumn();

            if ($estado_actual === 'verificada') {
                $this->lastError = 'No se puede modificar una asignación que ya ha sido verificada';
                return false;
            }

            // Validar datos antes de actualizar
            $errores = $this->validarDatos($datos, $id);
            if (!empty($errores)) {
                $this->lastError = implode(', ', $errores);
                return false;
            }

            $query = "UPDATE {$this->tabla} SET 
                  idusuario = :idusuario,
                  idhabitacion = :idhabitacion, 
                  fecha = :fecha, 
                  hora = :hora, 
                  estado = :estado, 
                  observaciones = :observaciones 
                  WHERE idasignacion = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $datos['fecha'], PDO::PARAM_STR);
            $stmt->bindParam(':hora', $datos['hora'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            // Manejar observaciones que pueden ser NULL
            if (!empty($datos['observaciones'])) {
                $stmt->bindParam(':observaciones', $datos['observaciones'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':observaciones', null, PDO::PARAM_NULL);
            }

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza el estado de una asignación de limpieza
     * 
     * @param int $id ID de la asignación de limpieza
     * @param string $estado Nuevo estado ('pendiente', 'enprogreso', 'completada', 'verificada')
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado WHERE idasignacion = :id";
            $stmt = $this->conexion->prepare($query);
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
     * Elimina una asignación de limpieza
     * 
     * @param int $id ID de la asignación de limpieza
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminar($id)
    {
        try {
            $query = "DELETE FROM {$this->tabla} WHERE idasignacion = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Valida los datos de una asignación de limpieza
     * 
     * @param array $datos Datos a validar
     * @param int|null $id_asignacion ID de la asignación actual (para edición)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_asignacion = null)
    {
        $errores = [];

        // Validar usuario
        if (empty($datos['idusuario'])) {
            $errores[] = 'El usuario es obligatorio';
        }

        // Validar habitación
        if (empty($datos['idhabitacion'])) {
            $errores[] = 'La habitación es obligatoria';
        } else {
            // Verificar si la habitación existe
            $query = "SELECT COUNT(*) FROM habitaciones WHERE id_habitacion = :idhabitacion";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() == 0) {
                $errores[] = 'La habitación seleccionada no existe';
            } else {
                // Verificar si la habitación ya tiene asignaciones pendientes o en progreso
                if ($this->habitacionTieneAsignacionesPendientes($datos['idhabitacion'], $id_asignacion)) {
                    $errores[] = 'La habitación seleccionada ya tiene una asignación de limpieza pendiente o en progreso';
                }

                // Verificar si la habitación está en estado de limpieza
                // Excepto si estamos editando y es la misma habitación
                if (
                    $id_asignacion === null ||
                    ($id_asignacion !== null && $this->getAsignacionHabitacion($id_asignacion) != $datos['idhabitacion'])
                ) {

                    $query = "SELECT estado FROM habitaciones WHERE id_habitacion = :idhabitacion";
                    $stmt = $this->conexion->prepare($query);
                    $stmt->bindParam(':idhabitacion', $datos['idhabitacion'], PDO::PARAM_INT);
                    $stmt->execute();
                    $estado = $stmt->fetchColumn();

                    if ($estado != 'limpieza') {
                        $errores[] = 'Solo se pueden asignar habitaciones que estén en estado de limpieza';
                    }
                }
            }
        }

        // Validar fecha
        if (empty($datos['fecha'])) {
            $errores[] = 'La fecha es obligatoria';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'])) {
            $errores[] = 'El formato de fecha debe ser YYYY-MM-DD';
        }

        // Validar hora
        if (empty($datos['hora'])) {
            $errores[] = 'La hora es obligatoria';
        } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $datos['hora'])) {
            $errores[] = 'El formato de hora debe ser HH:MM o HH:MM:SS';
        }

        // Validar estado
        if (empty($datos['estado'])) {
            $errores[] = 'El estado es obligatorio';
        } elseif (!in_array($datos['estado'], ['pendiente', 'enprogreso', 'completada', 'verificada'])) {
            $errores[] = 'El estado debe ser pendiente, enprogreso, completada o verificada';
        }

        return $errores;
    }

    /**
     * Obtiene el ID de habitación de una asignación
     * 
     * @param int $id_asignacion ID de la asignación
     * @return int|null ID de la habitación o null si no existe
     */
    private function getAsignacionHabitacion($id_asignacion)
    {
        try {
            $query = "SELECT idhabitacion FROM {$this->tabla} WHERE idasignacion = :id_asignacion";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id_asignacion', $id_asignacion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return null;
        }
    }

    /**
     * Obtiene el ID del último registro insertado
     * 
     * @return string ID del último registro insertado
     */
    public function getLastInsertId()
    {
        return $this->conexion->lastInsertId();
    }

    /**
     * Obtiene estadísticas de asignaciones de limpieza del día actual
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        try {
            $stats = [];
            $fechaHoy = date('Y-m-d');

            // Total de asignaciones de hoy
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM {$this->tabla} WHERE DATE(fecha) = :fecha_hoy");
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Asignaciones pendientes de hoy
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as pendientes FROM {$this->tabla} WHERE estado = 'pendiente' AND DATE(fecha) = :fecha_hoy");
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];

            // Asignaciones en progreso de hoy
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as enprogreso FROM {$this->tabla} WHERE estado = 'enprogreso' AND DATE(fecha) = :fecha_hoy");
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['enprogreso'] = $stmt->fetch(PDO::FETCH_ASSOC)['enprogreso'];

            // Asignaciones completadas de hoy
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as completadas FROM {$this->tabla} WHERE estado = 'completada' AND DATE(fecha) = :fecha_hoy");
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['completadas'] = $stmt->fetch(PDO::FETCH_ASSOC)['completadas'];

            // Asignaciones verificadas de hoy
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as verificadas FROM {$this->tabla} WHERE estado = 'verificada' AND DATE(fecha) = :fecha_hoy");
            $stmt->bindParam(':fecha_hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['verificadas'] = $stmt->fetch(PDO::FETCH_ASSOC)['verificadas'];

            // Asignaciones para hoy (ya estaba filtrado por fecha = hoy)
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as hoy FROM {$this->tabla} WHERE fecha = :hoy");
            $stmt->bindParam(':hoy', $fechaHoy, PDO::PARAM_STR);
            $stmt->execute();
            $stats['hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['hoy'];

            return $stats;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total' => 0,
                'pendientes' => 0,
                'enprogreso' => 0,
                'completadas' => 0,
                'verificadas' => 0,
                'hoy' => 0
            ];
        }
    }

    /**
     * Obtiene asignaciones por estado
     * 
     * @param string $estado Estado de las asignaciones a obtener
     * @return array Lista de asignaciones con el estado especificado
     */
    public function getAsignacionesPorEstado($estado)
    {
        try {
            $query = "SELECT a.*, CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                     h.numero as numero_habitacion 
                     FROM {$this->tabla} a
                     LEFT JOIN usuarios u ON a.idusuario = u.idusuario
                     LEFT JOIN habitaciones h ON a.idhabitacion = h.id_habitacion
                     WHERE a.estado = :estado
                     ORDER BY a.fecha, a.hora";

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
}

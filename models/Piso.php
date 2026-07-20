<?php

/**
 * Modelo Piso
 * 
 * Gestiona las operaciones relacionadas con los pisos en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class Piso
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de pisos en la base de datos
     * @var string
     */
    private $tabla = 'pisos';

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
     * Obtiene todos los pisos
     * 
     * @return array Lista de pisos
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY nombre ASC";
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
     * Obtiene un piso por su ID
     * 
     * @param int $id ID del piso
     * @return array|bool Datos del piso o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idpiso = :id";
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
     * Crea un nuevo piso
     * 
     * @param array $datos Datos del piso
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre, descripcion, estado) 
                      VALUES (:nombre, :descripcion, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);

            // Manejar NULL en descripción
            if ($datos['descripcion'] === null || $datos['descripcion'] === '') {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }

            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza un piso existente
     * 
     * @param int $id ID del piso
     * @param array $datos Datos del piso
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      nombre = :nombre,
                      descripcion = :descripcion, 
                      estado = :estado,
                      fechaactualizacion = CURRENT_TIMESTAMP 
                      WHERE idpiso = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);

            // Manejar NULL en descripción
            if ($datos['descripcion'] === null || $datos['descripcion'] === '') {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }

            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cambia el estado de un piso
     * 
     * @param int $id ID del piso
     * @param int $estado Nuevo estado (1: Activo, 0: Inactivo)
     * @return bool True si se cambió correctamente, False en caso contrario
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado, fechaactualizacion = CURRENT_TIMESTAMP WHERE idpiso = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
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
     * Valida los datos de un piso
     * 
     * @param array $datos Datos a validar
     * @return array Lista de errores, vacía si no hay errores
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar nombre
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre del piso es obligatorio.';
        } elseif (strlen($datos['nombre']) > 50) {
            $errores[] = 'El nombre del piso no debe exceder los 50 caracteres.';
        }

        // Validar descripción (opcional)
        if (isset($datos['descripcion']) && $datos['descripcion'] !== null && strlen($datos['descripcion']) > 255) {
            $errores[] = 'La descripción no debe exceder los 255 caracteres.';
        }

        // Validar estado
        if (!isset($datos['estado']) || ($datos['estado'] != 0 && $datos['estado'] != 1)) {
            $errores[] = 'El estado debe ser 0 (Inactivo) o 1 (Activo).';
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último piso insertado
     * 
     * @return string ID del último piso insertado
     */
    public function getLastInsertId()
    {
        return $this->conexion->lastInsertId();
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
     * Obtiene estadísticas de pisos
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        try {
            // Total de pisos
            $queryTotal = "SELECT COUNT(*) as total FROM {$this->tabla}";
            $stmtTotal = $this->conexion->prepare($queryTotal);
            $stmtTotal->execute();
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

            // Pisos activos
            $queryActivos = "SELECT COUNT(*) as activos FROM {$this->tabla} WHERE estado = 1";
            $stmtActivos = $this->conexion->prepare($queryActivos);
            $stmtActivos->execute();
            $activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['activos'];

            // Pisos inactivos
            $queryInactivos = "SELECT COUNT(*) as inactivos FROM {$this->tabla} WHERE estado = 0";
            $stmtInactivos = $this->conexion->prepare($queryInactivos);
            $stmtInactivos->execute();
            $inactivos = $stmtInactivos->fetch(PDO::FETCH_ASSOC)['inactivos'];

            // Habitaciones por piso
            $queryHabitaciones = "SELECT p.idpiso, p.nombre, COUNT(h.id_habitacion) as num_habitaciones 
                                 FROM {$this->tabla} p
                                 LEFT JOIN habitaciones h ON p.idpiso = h.idpiso
                                 GROUP BY p.idpiso
                                 ORDER BY num_habitaciones DESC
                                 LIMIT 1";
            $stmtHabitaciones = $this->conexion->prepare($queryHabitaciones);
            $stmtHabitaciones->execute();
            $pisoMasHabitaciones = $stmtHabitaciones->fetch(PDO::FETCH_ASSOC);

            // Total de habitaciones
            $queryTotalHabitaciones = "SELECT COUNT(*) as total_habitaciones FROM habitaciones";
            $stmtTotalHabitaciones = $this->conexion->prepare($queryTotalHabitaciones);
            $stmtTotalHabitaciones->execute();
            $totalHabitaciones = $stmtTotalHabitaciones->fetch(PDO::FETCH_ASSOC)['total_habitaciones'];

            return [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'piso_mas_habitaciones' => $pisoMasHabitaciones ? $pisoMasHabitaciones['nombre'] : '-',
                'num_max_habitaciones' => $pisoMasHabitaciones ? $pisoMasHabitaciones['num_habitaciones'] : 0,
                'total_habitaciones' => $totalHabitaciones
            ];
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'piso_mas_habitaciones' => '-',
                'num_max_habitaciones' => 0,
                'total_habitaciones' => 0
            ];
        }
    }

    /**
     * Verifica si existe un piso con el mismo nombre
     * 
     * @param string $nombre Nombre del piso
     * @param int $id_excluir ID del piso a excluir (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre";

            // Si se proporciona un ID para excluir, agregarlo a la consulta
            if ($id_excluir !== null) {
                $query .= " AND idpiso != :id_excluir";
            }

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);

            if ($id_excluir !== null) {
                $stmt->bindParam(':id_excluir', $id_excluir, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }
}

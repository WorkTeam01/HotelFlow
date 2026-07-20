<?php

/**
 * Modelo Tarifa
 * 
 * Gestiona las operaciones relacionadas con las tarifas de alojamiento en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Tarifa
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de tarifas en la base de datos
     * @var string
     */
    private $tabla = 'tarifas';

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
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todas las tarifas con información del tipo de habitación
     * 
     * @return array Lista de tarifas
     */
    public function getAll()
    {
        try {
            $query = "SELECT t.*, th.nombre as tipo_habitacion 
                      FROM {$this->tabla} t
                      JOIN tipo_habitacion th ON t.id_tipo = th.id_tipo
                      ORDER BY t.idtarifa DESC";
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
     * Obtiene una tarifa por su ID
     * 
     * @param int $id ID de la tarifa
     * @return array|bool Datos de la tarifa o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT t.*, th.nombre as tipo_habitacion 
                      FROM {$this->tabla} t
                      JOIN tipo_habitacion th ON t.id_tipo = th.id_tipo
                      WHERE t.idtarifa = :id";
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
     * Crea una nueva tarifa
     * 
     * @param array $datos Datos de la tarifa
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} 
                     (id_tipo, tipo_estancia, duracion, precio, descripcion, estado) 
                     VALUES 
                     (:id_tipo, :tipo_estancia, :duracion, :precio, :descripcion, :estado)";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':id_tipo', $datos['id_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo_estancia', $datos['tipo_estancia'], PDO::PARAM_STR);
            $stmt->bindParam(':duracion', $datos['duracion'], PDO::PARAM_INT);
            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            // Manejar campos opcionales
            $this->bindOptionalParam($stmt, ':descripcion', $datos['descripcion'] ?? null);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza una tarifa existente
     * 
     * @param int $id ID de la tarifa
     * @param array $datos Datos de la tarifa
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                     id_tipo = :id_tipo, 
                     tipo_estancia = :tipo_estancia, 
                     duracion = :duracion, 
                     precio = :precio, 
                     descripcion = :descripcion, 
                     estado = :estado 
                     WHERE idtarifa = :id";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':id_tipo', $datos['id_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo_estancia', $datos['tipo_estancia'], PDO::PARAM_STR);
            $stmt->bindParam(':duracion', $datos['duracion'], PDO::PARAM_INT);
            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            // Manejar campos opcionales
            $this->bindOptionalParam($stmt, ':descripcion', $datos['descripcion'] ?? null);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza el estado de una tarifa
     * 
     * @param int $id ID de la tarifa
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idtarifa = :id";
            $stmt = $this->conexion->prepare($sql);
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
     * Activa una tarifa
     * 
     * @param int $id ID de la tarifa
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva una tarifa
     * 
     * @param int $id ID de la tarifa
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Obtiene tarifas por tipo de habitación
     * 
     * @param int $id_tipo ID del tipo de habitación
     * @return array Lista de tarifas
     */
    public function getByTipoHabitacion($id_tipo)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} 
                     WHERE id_tipo = :id_tipo AND estado = 1
                     ORDER BY tipo_estancia, duracion";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene la tarifa por tipo de habitación y tipo de estancia
     * 
     * @param int $id_tipo ID del tipo de habitación
     * @param string $tipo_estancia 'horas' o 'dias'
     * @return array Lista de tarifas
     */
    public function getByTipoEstancia($id_tipo, $tipo_estancia)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} 
                     WHERE id_tipo = :id_tipo 
                     AND tipo_estancia = :tipo_estancia 
                     AND estado = 1
                     ORDER BY duracion";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_estancia', $tipo_estancia, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Valida los datos de la tarifa antes de crear o actualizar
     * 
     * @param array $datos Datos de la tarifa
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar campos obligatorios
        $campos_obligatorios = ['id_tipo', 'tipo_estancia', 'duracion', 'precio'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar que el tipo de estancia sea válido
        if (isset($datos['tipo_estancia']) && !in_array($datos['tipo_estancia'], ['horas', 'dias'])) {
            $errores[] = "El tipo de estancia debe ser 'horas' o 'dias'";
        }

        // Validar que la duración sea positiva
        if (isset($datos['duracion']) && $datos['duracion'] <= 0) {
            $errores[] = "La duración debe ser un número positivo";
        }

        // Validar que el precio sea positivo
        if (isset($datos['precio']) && $datos['precio'] <= 0) {
            $errores[] = "El precio debe ser un número positivo";
        }

        return $errores;
    }

    /**
     * Verifica si ya existe una tarifa con los mismos parámetros
     * 
     * @param int $id_tipo ID del tipo de habitación
     * @param string $tipo_estancia 'horas' o 'dias'
     * @param int $duracion Duración en horas o días
     * @param int $id_excluir ID de la tarifa a excluir (para actualizaciones)
     * @return bool True si existe, False en caso contrario
     */
    public function existeTarifa($id_tipo, $tipo_estancia, $duracion, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} 
                         WHERE id_tipo = :id_tipo 
                         AND tipo_estancia = :tipo_estancia 
                         AND duracion = :duracion
                         AND idtarifa != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} 
                         WHERE id_tipo = :id_tipo 
                         AND tipo_estancia = :tipo_estancia 
                         AND duracion = :duracion";
                $stmt = $this->conexion->prepare($query);
            }

            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_estancia', $tipo_estancia, PDO::PARAM_STR);
            $stmt->bindParam(':duracion', $duracion, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Método auxiliar para manejar parámetros opcionales en consultas preparadas
     * 
     * @param PDOStatement $stmt Declaración preparada
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor del parámetro
     * @param int $type Tipo de dato (PDO::PARAM_*)
     */
    private function bindOptionalParam(&$stmt, $param, $value, $type = PDO::PARAM_STR)
    {
        if ($value === null) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam($param, $value, $type);
        }
    }
}

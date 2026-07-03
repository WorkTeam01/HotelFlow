<?php
/**
 * Modelo TipoHabitacion
 * 
 * Gestiona las operaciones relacionadas con los tipos de habitación en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class TipoHabitacion
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de tipos de habitación en la base de datos
     * @var string
     */
    private $tabla = 'tipo_habitacion';

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
     * Obtiene todos los tipos de habitación
     * 
     * @param bool $soloActivos Si es true, solo devuelve tipos activos
     * @return array Lista de tipos de habitación
     */
    public function getAll($soloActivos = false)
    {
        try {
            $query = "SELECT * FROM {$this->tabla}";
            
            if ($soloActivos) {
                $query .= " WHERE estado = 1";
            }
            
            $query .= " ORDER BY nombre ASC";
            
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un tipo de habitación por su ID
     * 
     * @param int $id ID del tipo de habitación
     * @return array|bool Datos del tipo de habitación o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE id_tipo = :id";
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
     * Crea un nuevo tipo de habitación
     * 
     * @param array $datos Datos del tipo de habitación
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre, descripcion, capacidad_maxima, estado) 
                      VALUES (:nombre, :descripcion, :capacidad_maxima, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            
            // Manejar descripción opcional
            if (empty($datos['descripcion'])) {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':capacidad_maxima', $datos['capacidad_maxima'], PDO::PARAM_INT);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un tipo de habitación existente
     * 
     * @param int $id ID del tipo de habitación
     * @param array $datos Datos del tipo de habitación
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      nombre = :nombre, 
                      descripcion = :descripcion, 
                      capacidad_maxima = :capacidad_maxima,
                      estado = :estado 
                      WHERE id_tipo = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            
            // Manejar descripción opcional
            if (empty($datos['descripcion'])) {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':capacidad_maxima', $datos['capacidad_maxima'], PDO::PARAM_INT);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de un tipo de habitación
     * 
     * @param int $id ID del tipo de habitación
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE id_tipo = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Activa un tipo de habitación
     * 
     * @param int $id ID del tipo de habitación
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva un tipo de habitación
     * 
     * @param int $id ID del tipo de habitación
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe un tipo de habitación con el mismo nombre
     * 
     * @param string $nombre Nombre del tipo de habitación
     * @param int $id_excluir ID del tipo de habitación a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre AND id_tipo != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida los datos del tipo de habitación antes de crear o actualizar
     * 
     * @param array $datos Datos del tipo de habitación
     * @param int $id_excluir ID del tipo de habitación a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre del tipo de habitación es obligatorio';
        }

        // Validar nombre único
        if (!empty($datos['nombre']) && $this->existeNombre($datos['nombre'], $id_excluir)) {
            $errores[] = 'Ya existe un tipo de habitación con este nombre';
        }

        // Validar capacidad máxima
        if (!isset($datos['capacidad_maxima']) || $datos['capacidad_maxima'] < 1) {
            $errores[] = 'La capacidad máxima debe ser al menos 1';
        }

        // Validar estado si está presente
        if (isset($datos['estado']) && !in_array($datos['estado'], [0, 1])) {
            $errores[] = 'El estado debe ser 0 (inactivo) o 1 (activo)';
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último tipo de habitación insertado
     * 
     * @return int ID del último tipo de habitación insertado
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
     * Obtiene los tipos de habitación con información básica para selects
     * 
     * @param bool $soloActivos Si es true, solo devuelve tipos activos
     * @return array Lista de tipos de habitación en formato id => nombre
     */
    public function getParaSelect($soloActivos = true)
    {
        try {
            $query = "SELECT id_tipo, nombre FROM {$this->tabla}";
            
            if ($soloActivos) {
                $query .= " WHERE estado = 1";
            }
            
            $query .= " ORDER BY nombre ASC";
            
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $opciones = [];
            
            foreach ($resultados as $tipo) {
                $opciones[$tipo['id_tipo']] = $tipo['nombre'];
            }
            
            return $opciones;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}
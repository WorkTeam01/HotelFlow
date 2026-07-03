<?php

/**
 * Modelo Baño
 * 
 * Gestiona las operaciones relacionadas con los baños en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class Bano
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de baños en la base de datos
     * @var string
     */
    private $tabla = 'bano';

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
     * Obtiene todos los baños
     * 
     * @return array Lista de baños
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY idbano DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un baño por su ID
     * 
     * @param int $id ID del baño
     * @return array|bool Datos del baño o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idbano = :id";
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
     * Crea un nuevo baño
     * 
     * @param array $datos Datos del baño
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (nombre, ubicacion, precio, estado) 
                      VALUES (:nombre, :ubicacion, :precio, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':ubicacion', $datos['ubicacion'], PDO::PARAM_STR);
            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un baño existente
     * 
     * @param int $id ID del baño
     * @param array $datos Datos del baño
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      nombre = :nombre, 
                      ubicacion = :ubicacion,
                      precio = :precio, 
                      estado = :estado 
                      WHERE idbano = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':ubicacion', $datos['ubicacion'], PDO::PARAM_STR);
            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza el estado de un baño
     * 
     * @param int $id ID del baño
     * @param string $estado Nuevo estado ('disponible', 'mantenimiento', 'fuera_servicio')
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idbano = :idbano";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':idbano', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Marca un baño como disponible
     * 
     * @param int $id ID del baño
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function marcarDisponible($id)
    {
        return $this->actualizarEstado($id, 'disponible');
    }

    /**
     * Marca un baño en mantenimiento
     * 
     * @param int $id ID del baño
     * @return bool True si se marcó correctamente, False en caso contrario
     */
    public function marcarMantenimiento($id)
    {
        return $this->actualizarEstado($id, 'mantenimiento');
    }

    /**
     * Marca un baño como fuera de servicio
     * 
     * @param int $id ID del baño
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function marcarFueraServicio($id)
    {
        return $this->actualizarEstado($id, 'fuera_servicio');
    }

    /**
     * Verifica si existe un baño con el nombre especificado
     * 
     * @param string $nombre Nombre del baño
     * @param int $id_excluir ID del baño a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNombre($nombre, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE nombre = :nombre AND idbano != :id";
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
     * Valida los datos del baño antes de crear o actualizar
     * 
     * @param array $datos Datos del baño
     * @param int $id_excluir ID del baño a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['nombre']) || empty($datos['ubicacion'])) {
            $errores[] = 'Los campos nombre y ubicación son obligatorios';
        }

        // Validar nombre único
        if (!empty($datos['nombre'])) {
            if ($this->existeNombre($datos['nombre'], $id_excluir)) {
                $errores[] = 'El nombre del baño ya está registrado';
            }

            // Validar longitud del nombre
            if (strlen($datos['nombre']) > 50) {
                $errores[] = 'El nombre no debe exceder los 50 caracteres';
            }
        }

        // Validar longitud de la ubicación
        if (!empty($datos['ubicacion']) && strlen($datos['ubicacion']) > 100) {
            $errores[] = 'La ubicación no debe exceder los 100 caracteres';
        }

        // Validar precio
        if (isset($datos['precio'])) {
            if (!is_numeric($datos['precio']) || $datos['precio'] < 0) {
                $errores[] = 'El precio debe ser un número válido mayor o igual a cero';
            }
        }

        // Validar estado
        if (isset($datos['estado']) && !in_array($datos['estado'], ['disponible', 'mantenimiento', 'fuera_servicio'])) {
            $errores[] = 'El estado del baño no es válido';
        }

        return $errores;
    }

    /**
     * Obtiene el ID del último baño insertado
     * 
     * @return int ID del último baño insertado
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
     * Obtiene estadísticas de los baños
     * 
     * @return array Estadísticas de los baños
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = [
                'total' => 0,
                'disponibles' => 0,
                'mantenimiento' => 0,
                'fuera_servicio' => 0
            ];

            // Total de baños
            $query = "SELECT COUNT(*) FROM {$this->tabla}";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['total'] = $stmt->fetchColumn();

            // Baños disponibles
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado = 'disponible'";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['disponibles'] = $stmt->fetchColumn();

            // Baños en mantenimiento
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado = 'mantenimiento'";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['mantenimiento'] = $stmt->fetchColumn();

            // Baños fuera de servicio
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE estado = 'fuera_servicio'";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['fuera_servicio'] = $stmt->fetchColumn();

            return $estadisticas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'disponibles' => 0,
                'mantenimiento' => 0,
                'fuera_servicio' => 0
            ];
        }
    }
}

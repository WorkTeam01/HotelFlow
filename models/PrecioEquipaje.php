<?php

/**
 * Modelo PrecioEquipaje
 * 
 * Gestiona las operaciones relacionadas con los precios de equipaje en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class PrecioEquipaje
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de precios de equipaje en la base de datos
     * @var string
     */
    private $tabla = 'precio_equipaje';

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
     * Obtiene todos los precios de equipaje
     * 
     * @return array Lista de precios de equipaje
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY tamano ASC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene un precio de equipaje por su ID
     * 
     * @param int $id ID del precio de equipaje
     * @return array|bool Datos del precio de equipaje o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idprecioe = :id";
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
     * Crea un nuevo precio de equipaje
     * 
     * @param array $datos Datos del precio de equipaje
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (tamano, descripcion, precio, estado) 
                      VALUES (:tamano, :descripcion, :precio, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':tamano', $datos['tamano'], PDO::PARAM_STR);

            // Manejar NULL en descripción
            if ($datos['descripcion'] === null || $datos['descripcion'] === '') {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }

            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza un precio de equipaje existente
     * 
     * @param int $id ID del precio de equipaje
     * @param array $datos Datos del precio de equipaje
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      tamano = :tamano,
                      descripcion = :descripcion, 
                      precio = :precio, 
                      estado = :estado 
                      WHERE idprecioe = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':tamano', $datos['tamano'], PDO::PARAM_STR);

            // Manejar NULL en descripción
            if ($datos['descripcion'] === null || $datos['descripcion'] === '') {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }

            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Cambia el estado de un precio de equipaje
     * 
     * @param int $id ID del precio de equipaje
     * @param int $estado Nuevo estado (1: Activo, 0: Inactivo)
     * @return bool True si se cambió correctamente, False en caso contrario
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado WHERE idprecioe = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
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
     * Valida los datos de un precio de equipaje
     * 
     * @param array $datos Datos a validar
     * @return array Lista de errores, vacía si no hay errores
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar tamaño
        if (empty($datos['tamano'])) {
            $errores[] = 'El tamaño del equipaje es obligatorio.';
        } elseif (!in_array($datos['tamano'], ['Pequeño', 'Mediano', 'Grande', 'Extra_Grande'])) {
            $errores[] = 'El tamaño debe ser Pequeño, Mediano, Grande o Extra_Grande.';
        }

        // Validar precio
        if (empty($datos['precio'])) {
            $errores[] = 'El precio es obligatorio.';
        } elseif (!is_numeric($datos['precio']) || $datos['precio'] <= 0) {
            $errores[] = 'El precio debe ser un número mayor que cero.';
        }

        // Validar estado
        if (!isset($datos['estado']) || ($datos['estado'] != 0 && $datos['estado'] != 1)) {
            $errores[] = 'El estado debe ser 0 (Inactivo) o 1 (Activo).';
        }

        return $errores;
    }

    /**
     * Obtiene el último ID insertado
     * 
     * @return string Último ID insertado
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
     * Obtiene estadísticas de precios de equipaje
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        try {
            // Total de precios
            $queryTotal = "SELECT COUNT(*) as total FROM {$this->tabla}";
            $stmtTotal = $this->conexion->prepare($queryTotal);
            $stmtTotal->execute();
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

            // Precios activos
            $queryActivos = "SELECT COUNT(*) as activos FROM {$this->tabla} WHERE estado = 1";
            $stmtActivos = $this->conexion->prepare($queryActivos);
            $stmtActivos->execute();
            $activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['activos'];

            // Precios inactivos
            $queryInactivos = "SELECT COUNT(*) as inactivos FROM {$this->tabla} WHERE estado = 0";
            $stmtInactivos = $this->conexion->prepare($queryInactivos);
            $stmtInactivos->execute();
            $inactivos = $stmtInactivos->fetch(PDO::FETCH_ASSOC)['inactivos'];

            // Precio mínimo y máximo
            $queryPrecios = "SELECT MIN(precio) as min_precio, MAX(precio) as max_precio FROM {$this->tabla}";
            $stmtPrecios = $this->conexion->prepare($queryPrecios);
            $stmtPrecios->execute();
            $precios = $stmtPrecios->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'min_precio' => $precios['min_precio'] ?? 0,
                'max_precio' => $precios['max_precio'] ?? 0
            ];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'min_precio' => 0,
                'max_precio' => 0
            ];
        }
    }
}

<?php
/**
 * Modelo Persona
 * 
 * Gestiona las operaciones relacionadas con las personas (clientes) en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Persona
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de personas en la base de datos
     * @var string
     */
    private $tabla = 'persona';

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
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Sanitiza los datos de entrada para prevenir inyección SQL y XSS
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
     * Obtiene todas las personas
     * @return array Lista de personas
     */
    public function getAll()
    {
        try {
            $query = "SELECT * FROM {$this->tabla} ORDER BY idpersona DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una persona por su ID
     * @param int $id ID de la persona
     * @return array|bool Datos de la persona o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE idpersona = :id";
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
     * Crea una nueva persona
     * @param array $datos Datos de la persona
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (
                nombre, apellidopaterno, apellidomaterno, tipodocumento, numdocumento, 
                direccion, telefono, email, fechanacimiento, genero, estado
            ) VALUES (
                :nombre, :apellidopaterno, :apellidomaterno, :tipodocumento, :numdocumento, 
                :direccion, :telefono, :email, :fechanacimiento, :genero, :estado
            )";

            $stmt = $this->conexion->prepare($query);
            
            // Campos obligatorios
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidopaterno', $datos['apellidopaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':tipodocumento', $datos['tipodocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':numdocumento', $datos['numdocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);

            // Campos opcionales
            $this->bindOptionalParam($stmt, ':apellidomaterno', $datos['apellidomaterno']);
            $this->bindOptionalParam($stmt, ':direccion', $datos['direccion']);
            $this->bindOptionalParam($stmt, ':telefono', $datos['telefono']);
            $this->bindOptionalParam($stmt, ':email', $datos['email']);
            $this->bindOptionalParam($stmt, ':fechanacimiento', $datos['fechanacimiento']);
            $this->bindOptionalParam($stmt, ':genero', $datos['genero']);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza una persona existente
     * @param int $id ID de la persona
     * @param array $datos Datos de la persona
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                nombre = :nombre, 
                apellidopaterno = :apellidopaterno, 
                apellidomaterno = :apellidomaterno, 
                tipodocumento = :tipodocumento, 
                numdocumento = :numdocumento, 
                direccion = :direccion, 
                telefono = :telefono, 
                email = :email, 
                fechanacimiento = :fechanacimiento, 
                genero = :genero, 
                estado = :estado 
                WHERE idpersona = :id";

            $stmt = $this->conexion->prepare($query);
            
            // Campos obligatorios
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidopaterno', $datos['apellidopaterno'], PDO::PARAM_STR);
            $stmt->bindParam(':tipodocumento', $datos['tipodocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':numdocumento', $datos['numdocumento'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            // Campos opcionales
            $this->bindOptionalParam($stmt, ':apellidomaterno', $datos['apellidomaterno']);
            $this->bindOptionalParam($stmt, ':direccion', $datos['direccion']);
            $this->bindOptionalParam($stmt, ':telefono', $datos['telefono']);
            $this->bindOptionalParam($stmt, ':email', $datos['email']);
            $this->bindOptionalParam($stmt, ':fechanacimiento', $datos['fechanacimiento']);
            $this->bindOptionalParam($stmt, ':genero', $datos['genero']);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Maneja el binding de parámetros opcionales (pueden ser nulos)
     * @param PDOStatement $stmt
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor del parámetro
     */
    private function bindOptionalParam($stmt, $param, $value)
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    /**
     * Actualiza el estado de una persona
     * @param int $id ID de la persona
     * @param int $estado Nuevo estado (1: activo, 0: inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE idpersona = :id";
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
     * Activa una persona
     * @param int $id ID de la persona
     * @return bool True si se activó correctamente, False en caso contrario
     */
    public function activar($id)
    {
        return $this->actualizarEstado($id, 1);
    }

    /**
     * Desactiva una persona
     * @param int $id ID de la persona
     * @return bool True si se desactivó correctamente, False en caso contrario
     */
    public function desactivar($id)
    {
        return $this->actualizarEstado($id, 0);
    }

    /**
     * Verifica si existe una persona con el email especificado
     * @param string $email Email a verificar
     * @param int $id_excluir ID de la persona a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeEmail($email, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE email = :email AND idpersona != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE email = :email";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si existe una persona con el mismo tipo y número de documento
     * @param string $tipodocumento Tipo de documento
     * @param string $numdocumento Número de documento
     * @param int $id_excluir ID de la persona a excluir de la verificación (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeDocumento($tipodocumento, $numdocumento, $id_excluir = null)
    {
        try {
            if ($id_excluir) {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE tipodocumento = :tipodocumento AND numdocumento = :numdocumento AND idpersona != :id";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':tipodocumento', $tipodocumento, PDO::PARAM_STR);
                $stmt->bindParam(':numdocumento', $numdocumento, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
            } else {
                $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE tipodocumento = :tipodocumento AND numdocumento = :numdocumento";
                $stmt = $this->conexion->prepare($query);
                $stmt->bindParam(':tipodocumento', $tipodocumento, PDO::PARAM_STR);
                $stmt->bindParam(':numdocumento', $numdocumento, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida los datos de la persona antes de crear o actualizar
     * @param array $datos Datos de la persona
     * @param int $id_excluir ID de la persona a excluir de la validación (opcional)
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos, $id_excluir = null)
    {
        $errores = [];

        // Validar campos obligatorios
        $camposObligatorios = [
            'nombre' => 'Nombre',
            'apellidopaterno' => 'Apellido Paterno',
            'tipodocumento' => 'Tipo de Documento',
            'numdocumento' => 'Número de Documento'
        ];

        foreach ($camposObligatorios as $campo => $nombre) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$nombre} es obligatorio";
            }
        }

        // Validar email único si se proporcionó
        if (!empty($datos['email'])) {
            if ($this->existeEmail($datos['email'], $id_excluir)) {
                $errores[] = 'El email ya está registrado';
            }

            if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El formato del email no es válido';
            }
        }

        // Validar documento único
        if (!empty($datos['tipodocumento']) && !empty($datos['numdocumento'])) {
            if ($this->existeDocumento($datos['tipodocumento'], $datos['numdocumento'], $id_excluir)) {
                $errores[] = 'Ya existe una persona con este tipo y número de documento';
            }
        }

        return $errores;
    }

    /**
     * Busca personas por diferentes criterios
     * @param array $criterios Criterios de búsqueda
     * @return array Resultados de la búsqueda
     */
    public function buscar($criterios)
    {
        try {
            $query = "SELECT * FROM {$this->tabla} WHERE 1=1";
            $params = [];

            // Filtro por nombre o apellidos
            if (!empty($criterios['busqueda'])) {
                $query .= " AND (nombre LIKE :busqueda OR apellidopaterno LIKE :busqueda OR apellidomaterno LIKE :busqueda)";
                $params[':busqueda'] = "%{$criterios['busqueda']}%";
            }

            // Filtro por tipo de documento
            if (!empty($criterios['tipodocumento'])) {
                $query .= " AND tipodocumento = :tipodocumento";
                $params[':tipodocumento'] = $criterios['tipodocumento'];
            }

            // Filtro por número de documento
            if (!empty($criterios['numdocumento'])) {
                $query .= " AND numdocumento LIKE :numdocumento";
                $params[':numdocumento'] = "%{$criterios['numdocumento']}%";
            }

            // Filtro por estado
            if (isset($criterios['estado']) && $criterios['estado'] !== '') {
                $query .= " AND estado = :estado";
                $params[':estado'] = $criterios['estado'];
            }

            $query .= " ORDER BY idpersona DESC";

            $stmt = $this->conexion->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene el ID de la última persona insertada
     * @return int ID de la última persona insertada
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
}
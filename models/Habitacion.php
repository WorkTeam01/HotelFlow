<?php

/**
 * Modelo Habitacion
 * 
 * Gestiona las operaciones relacionadas con las habitaciones en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class Habitacion
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de habitaciones en la base de datos
     * @var string
     */
    private $tabla = 'habitaciones';

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
     * Obtiene todas las habitaciones con información de tipo y piso
     * 
     * @param string $orden Campo por el que ordenar
     * @param string $direccion Dirección de ordenamiento (ASC, DESC)
     * @return array Lista de habitaciones
     */
    public function getAll($orden = 'numero', $direccion = 'ASC')
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, p.nombre as piso_nombre
                      FROM {$this->tabla} h
                      LEFT JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                      LEFT JOIN pisos p ON h.idpiso = p.idpiso
                      ORDER BY h.$orden $direccion";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene habitaciones filtradas por estado
     * 
     * @param string $estado Estado por el que filtrar
     * @return array Lista de habitaciones filtradas
     */
    public function getByEstado($estado)
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, p.nombre as piso_nombre
                      FROM {$this->tabla} h
                      LEFT JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                      LEFT JOIN pisos p ON h.idpiso = p.idpiso
                      WHERE h.estado = :estado
                      ORDER BY h.numero ASC";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene una habitación por su ID
     * 
     * @param int $id ID de la habitación
     * @return array|bool Datos de la habitación o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, t.descripcion as tipo_descripcion, 
                             t.capacidad_maxima, p.nombre as piso_nombre
                      FROM {$this->tabla} h
                      LEFT JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                      LEFT JOIN pisos p ON h.idpiso = p.idpiso
                      WHERE h.id_habitacion = :id";

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
     * Crea una nueva habitación
     * 
     * @param array $datos Datos de la habitación
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (numero, id_tipo, idpiso, capacidad_actual, precio_base, estado) 
                      VALUES (:numero, :id_tipo, :idpiso, :capacidad_actual, :precio_base, :estado)";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numero', $datos['numero'], PDO::PARAM_STR);
            $stmt->bindParam(':id_tipo', $datos['id_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':idpiso', $datos['idpiso'], PDO::PARAM_INT);
            $stmt->bindParam(':capacidad_actual', $datos['capacidad_actual'], PDO::PARAM_INT);
            $stmt->bindParam(':precio_base', $datos['precio_base'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Actualiza una habitación existente
     * 
     * @param int $id ID de la habitación
     * @param array $datos Datos de la habitación
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      numero = :numero,
                      id_tipo = :id_tipo,
                      idpiso = :idpiso,
                      capacidad_actual = :capacidad_actual,
                      precio_base = :precio_base,
                      estado = :estado,
                      fechaactualizacion = CURRENT_TIMESTAMP
                      WHERE id_habitacion = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numero', $datos['numero'], PDO::PARAM_STR);
            $stmt->bindParam(':id_tipo', $datos['id_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':idpiso', $datos['idpiso'], PDO::PARAM_INT);
            $stmt->bindParam(':capacidad_actual', $datos['capacidad_actual'], PDO::PARAM_INT);
            $stmt->bindParam(':precio_base', $datos['precio_base'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Cambia el estado de una habitación
     * 
     * @param int $id ID de la habitación
     * @param string $estado Nuevo estado
     * @return bool True si se cambió correctamente, False en caso contrario
     */
    public function cambiarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET 
                      estado = :estado,
                      fechaactualizacion = CURRENT_TIMESTAMP
                      WHERE id_habitacion = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Elimina una habitación por su ID
     * 
     * @param int $id ID de la habitación
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminar($id)
    {
        try {
            // Primero verificar si la habitación tiene registros relacionados
            if ($this->tieneRegistrosRelacionados($id)) {
                $this->lastError = "No se puede eliminar la habitación porque tiene registros relacionados.";
                return false;
            }

            $query = "DELETE FROM {$this->tabla} WHERE id_habitacion = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Verifica si una habitación tiene registros relacionados
     * 
     * @param int $id ID de la habitación
     * @return bool True si tiene registros relacionados, False en caso contrario
     */
    private function tieneRegistrosRelacionados($id)
    {
        try {
            // Verificar si hay recepciones relacionadas
            $query = "SELECT COUNT(*) FROM recepcion WHERE idhabitacion = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Verificar si hay asignaciones de limpieza relacionadas
            $query = "SELECT COUNT(*) FROM asignaciones_limpieza WHERE idhabitacion = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return true; // Por seguridad, asumimos que hay registros relacionados
        }
    }

    /**
     * Obtiene todos los tipos de habitación
     * 
     * @return array Lista de tipos de habitación
     */
    public function getTiposHabitacion()
    {
        try {
            $query = "SELECT * FROM tipo_habitacion WHERE estado = 1 ORDER BY nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene todos los pisos
     * 
     * @return array Lista de pisos
     */
    public function getPisos()
    {
        try {
            $query = "SELECT * FROM pisos WHERE estado = 1 ORDER BY nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
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
            if (is_string($valor)) {
                $datosSanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } else {
                $datosSanitizados[$clave] = $valor;
            }
        }
        return $datosSanitizados;
    }

    /**
     * Valida los datos de una habitación
     * 
     * @param array $datos Datos a validar
     * @return array Lista de errores, vacía si no hay errores
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar número de habitación
        if (empty($datos['numero'])) {
            $errores[] = 'El número de habitación es obligatorio.';
        } elseif (strlen($datos['numero']) > 10) {
            $errores[] = 'El número de habitación no debe exceder los 10 caracteres.';
        }

        // Validar tipo de habitación
        if (empty($datos['id_tipo'])) {
            $errores[] = 'El tipo de habitación es obligatorio.';
        }

        // Validar piso
        if (empty($datos['idpiso'])) {
            $errores[] = 'El piso es obligatorio.';
        }

        // Validar capacidad actual
        if (!isset($datos['capacidad_actual']) || $datos['capacidad_actual'] < 0) {
            $errores[] = 'La capacidad actual debe ser un número mayor o igual a cero.';
        }

        // Validar precio base
        if (empty($datos['precio_base']) || !is_numeric($datos['precio_base']) || $datos['precio_base'] <= 0) {
            $errores[] = 'El precio base debe ser un número mayor que cero.';
        }

        // Validar estado
        $estados_validos = ['disponible', 'ocupada', 'mantenimiento', 'limpieza'];
        if (empty($datos['estado']) || !in_array($datos['estado'], $estados_validos)) {
            $errores[] = 'El estado de la habitación no es válido.';
        }

        return $errores;
    }

    /**
     * Verifica si existe una habitación con el mismo número
     * 
     * @param string $numero Número de habitación
     * @param int $id_excluir ID de la habitación a excluir (opcional)
     * @return bool True si existe, False en caso contrario
     */
    public function existeNumero($numero, $id_excluir = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tabla} WHERE numero = :numero";

            // Si se proporciona un ID para excluir, agregarlo a la consulta
            if ($id_excluir !== null) {
                $query .= " AND id_habitacion != :id_excluir";
            }

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':numero', $numero, PDO::PARAM_STR);

            if ($id_excluir !== null) {
                $stmt->bindParam(':id_excluir', $id_excluir, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene el ID de la última habitación insertada
     * 
     * @return int ID de la última habitación insertada
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
     * Obtiene estadísticas de habitaciones
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = [
                'total' => 0,
                'disponibles' => 0,
                'ocupadas' => 0,
                'mantenimiento' => 0,
                'limpieza' => 0,
                'por_tipo' => [],
                'por_piso' => []
            ];

            // Total de habitaciones
            $query = "SELECT COUNT(*) as total FROM {$this->tabla}";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Habitaciones por estado
            $query = "SELECT estado, COUNT(*) as cantidad 
                      FROM {$this->tabla} 
                      GROUP BY estado";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($resultados as $resultado) {
                $estado = $resultado['estado'];
                $cantidad = $resultado['cantidad'];

                switch ($estado) {
                    case 'disponible':
                        $estadisticas['disponibles'] = $cantidad;
                        break;
                    case 'ocupada':
                        $estadisticas['ocupadas'] = $cantidad;
                        break;
                    case 'mantenimiento':
                        $estadisticas['mantenimiento'] = $cantidad;
                        break;
                    case 'limpieza':
                        $estadisticas['limpieza'] = $cantidad;
                        break;
                    default:
                        break;
                }
            }

            // Habitaciones por tipo
            $query = "SELECT t.nombre, COUNT(*) as cantidad 
                      FROM {$this->tabla} h
                      JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                      GROUP BY t.nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['por_tipo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Habitaciones por piso
            $query = "SELECT p.nombre, COUNT(*) as cantidad 
                      FROM {$this->tabla} h
                      JOIN pisos p ON h.idpiso = p.idpiso
                      GROUP BY p.nombre";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $estadisticas['por_piso'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $estadisticas;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [
                'total' => 0,
                'disponibles' => 0,
                'ocupadas' => 0,
                'mantenimiento' => 0,
                'limpieza' => 0,
                'por_tipo' => [],
                'por_piso' => []
            ];
        }
    }

    /**
     * Obtiene el historial de ocupación de una habitación
     * 
     * @param int $id ID de la habitación
     * @param int $limite Límite de registros a obtener
     * @return array Historial de ocupación
     */
    public function getHistorialOcupacion($id, $limite = 10)
    {
        try {
            $query = "SELECT r.idrecepcion, r.fechaentrada, r.fechasalida, r.estado,
                             CONCAT(p.nombre, ' ', p.apellidopaterno) as nombre_cliente,
                             r.montototal
                      FROM recepcion r
                      JOIN persona p ON r.idcliente = p.idpersona
                      WHERE r.idhabitacion = :id
                      ORDER BY r.fechaentrada DESC
                      LIMIT :limite";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Obtiene el historial de limpieza de una habitación
     * 
     * @param int $id ID de la habitación
     * @param int $limite Límite de registros a obtener
     * @return array Historial de limpieza
     */
    public function getHistorialLimpieza($id, $limite = 10)
    {
        try {
            $query = "SELECT a.idasignacion, a.fecha, a.hora, a.estado,
                             CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                             a.observaciones
                      FROM asignaciones_limpieza a
                      JOIN usuarios u ON a.idusuario = u.idusuario
                      WHERE a.idhabitacion = :id
                      ORDER BY a.fecha DESC, a.hora DESC
                      LIMIT :limite";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * Filtra habitaciones según criterios específicos
     * 
     * @param string $estado Estado a filtrar
     * @param int $tipo_id ID del tipo de habitación
     * @param int $piso_id ID del piso
     * @param string $orden Campo para ordenar
     * @param string $direccion Dirección del ordenamiento (ASC, DESC)
     * @return array Lista de habitaciones filtradas
     */
    public function filtrar($estado = 'todos', $tipo_id = 0, $piso_id = 0, $orden = 'numero', $direccion = 'ASC')
    {
        try {
            $query = "SELECT h.*, t.nombre as tipo_nombre, p.nombre as piso_nombre
                  FROM {$this->tabla} h
                  LEFT JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
                  LEFT JOIN pisos p ON h.idpiso = p.idpiso
                  WHERE 1=1";

            $params = [];

            // Filtrar por estado
            if ($estado != 'todos') {
                $query .= " AND h.estado = :estado";
                $params[':estado'] = $estado;
            }

            // Filtrar por tipo de habitación
            if ($tipo_id > 0) {
                $query .= " AND h.id_tipo = :tipo_id";
                $params[':tipo_id'] = $tipo_id;
            }

            // Filtrar por piso
            if ($piso_id > 0) {
                $query .= " AND h.idpiso = :piso_id";
                $params[':piso_id'] = $piso_id;
            }

            // Ordenar resultados
            $query .= " ORDER BY h.$orden $direccion";

            $stmt = $this->conexion->prepare($query);

            // Bind params
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
}

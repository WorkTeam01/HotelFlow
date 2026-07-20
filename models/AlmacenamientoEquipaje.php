<?php

/**
 * Modelo AlmacenamientoEquipaje
 * 
 * Gestiona las operaciones relacionadas con el almacenamiento de equipajes en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

class AlmacenamientoEquipaje
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de almacenamiento de equipajes en la base de datos
     * @var string
     */
    private $tabla = 'almacenamiento_equipaje';

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
     * Obtiene todos los registros de almacenamiento de equipaje
     * 
     * @param array $filtros Filtros opcionales para la consulta
     * @return array Lista de registros de almacenamiento de equipaje
     */
    public function getAll($filtros = [])
    {
        try {
            $whereConditions = [];
            $params = [];

            // Aplicar filtros si existen
            if (!empty($filtros['estado'])) {
                $whereConditions[] = "ae.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
                $whereConditions[] = "ae.fechaentrada BETWEEN :fecha_inicio AND :fecha_fin";
                $params[':fecha_inicio'] = $filtros['fecha_inicio'] . ' 00:00:00';
                $params[':fecha_fin'] = $filtros['fecha_fin'] . ' 23:59:59';
            }

            if (!empty($filtros['idcliente'])) {
                $whereConditions[] = "ae.idcliente = :idcliente";
                $params[':idcliente'] = $filtros['idcliente'];
            }

            // Construir cláusula WHERE
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            }

            $query = "SELECT ae.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as nombre_cliente,
                      CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                      pe.tamano as tamano_equipaje,
                      pe.precio as precio_base
                      FROM {$this->tabla} ae
                      LEFT JOIN persona p ON ae.idcliente = p.idpersona
                      LEFT JOIN usuarios u ON ae.idusuario = u.idusuario
                      LEFT JOIN precio_equipaje pe ON ae.idpequipaje = pe.idprecioe
                      $whereClause
                      ORDER BY ae.fechaentrada DESC";

            $stmt = $this->conexion->prepare($query);

            // Vincular parámetros de filtro
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
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
     * Obtiene un registro de almacenamiento de equipaje por su ID
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @return array|bool Datos del registro o false si no existe
     */
    public function getById($id)
    {
        try {
            $query = "SELECT ae.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as nombre_cliente,
                      p.tipodocumento as tipodoc_cliente,
                      p.numdocumento as numdoc_cliente,
                      p.telefono as telefono_cliente,
                      CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                      pe.tamano as tamano_equipaje,
                      pe.precio as precio_base
                      FROM {$this->tabla} ae
                      LEFT JOIN persona p ON ae.idcliente = p.idpersona
                      LEFT JOIN usuarios u ON ae.idusuario = u.idusuario
                      LEFT JOIN precio_equipaje pe ON ae.idpequipaje = pe.idprecioe
                      WHERE ae.idalmacen = :id";

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
     * Crea un nuevo registro de almacenamiento de equipaje
     * 
     * @param array $datos Datos del registro de almacenamiento de equipaje
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crear($datos)
    {
        try {
            $query = "INSERT INTO {$this->tabla} (
                  idcliente, idusuario, descripcion, cantidad_piezas, 
                  codigo_ticket, fechaentrada, idpequipaje, monto, 
                  estado) 
                  VALUES (
                  :idcliente, :idusuario, :descripcion, :cantidad_piezas, 
                  :codigo_ticket, :fechaentrada, :idpequipaje, :monto, 
                  :estado)";

            $stmt = $this->conexion->prepare($query);

            // Parámetros obligatorios
            $stmt->bindParam(':idcliente', $datos['idcliente'], PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':idpequipaje', $datos['idpequipaje'], PDO::PARAM_INT);
            $stmt->bindParam(':monto', $datos['monto'], PDO::PARAM_STR);

            // Descripción puede ser NULL
            if (empty($datos['descripcion'])) {
                $stmt->bindValue(':descripcion', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            }

            // Valores con defaults
            $cantidad_piezas = !empty($datos['cantidad_piezas']) ? $datos['cantidad_piezas'] : 1;
            $stmt->bindParam(':cantidad_piezas', $cantidad_piezas, PDO::PARAM_INT);

            $codigo_ticket = !empty($datos['codigo_ticket']) ? $datos['codigo_ticket'] : $this->generarCodigoTicket();
            $stmt->bindParam(':codigo_ticket', $codigo_ticket, PDO::PARAM_STR);

            $fechaentrada = !empty($datos['fechaentrada']) ? $datos['fechaentrada'] : date('Y-m-d H:i:s');
            $stmt->bindParam(':fechaentrada', $fechaentrada, PDO::PARAM_STR);

            $estado = !empty($datos['estado']) ? $datos['estado'] : 'almacenado';
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);

            $resultado = $stmt->execute();

            if ($resultado) {
                return $this->conexion->lastInsertId();
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza un registro de almacenamiento de equipaje existente
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @param array $datos Datos del registro de almacenamiento de equipaje
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizar($id, $datos)
    {
        try {
            // Crear una lista de campos para actualizar
            $sets = [];
            $params = [':id' => $id];

            // Mapeo de campos permitidos para actualización
            $campos_permitidos = [
                'idcliente' => PDO::PARAM_INT,
                'descripcion' => PDO::PARAM_STR,
                'cantidad_piezas' => PDO::PARAM_INT,
                'codigo_ticket' => PDO::PARAM_STR,
                'idpequipaje' => PDO::PARAM_INT,
                'monto' => PDO::PARAM_STR,
                'estado' => PDO::PARAM_STR
            ];

            // Construir sentencia SET dinámicamente
            foreach ($campos_permitidos as $campo => $tipo) {
                if (array_key_exists($campo, $datos)) {
                    // Manejar NULL en descripción
                    if ($campo === 'descripcion' && empty($datos[$campo])) {
                        $sets[] = "$campo = NULL";
                    } else {
                        $sets[] = "$campo = :$campo";
                        $params[":$campo"] = $datos[$campo];
                    }
                }
            }

            // Si es para entregar el equipaje, establecer la fecha de salida
            if (isset($datos['estado']) && $datos['estado'] === 'retirado') {
                $sets[] = "fechasalida = :fechasalida";
                $params[':fechasalida'] = date('Y-m-d H:i:s');
            }

            // Si no hay campos para actualizar, retornar true (sin cambios)
            if (empty($sets)) {
                return true;
            }

            $query = "UPDATE {$this->tabla} SET " . implode(", ", $sets) . " WHERE idalmacen = :id";

            $stmt = $this->conexion->prepare($query);

            // Vincular todos los parámetros
            foreach ($params as $param => $value) {
                if ($param === ':descripcion' && $value === null) {
                    $stmt->bindValue($param, $value, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cambia el estado de un registro de almacenamiento de equipaje
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @param string $nuevo_estado Nuevo estado ('almacenado', 'retirado', 'perdido', 'dañado')
     * @return bool True si se cambió correctamente, False en caso contrario
     */
    public function cambiarEstado($id, $nuevo_estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado";

            // Si el nuevo estado es 'retirado', establecer la fecha de salida
            if ($nuevo_estado === 'retirado') {
                $query .= ", fechasalida = :fechasalida";
            }

            $query .= " WHERE idalmacen = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($nuevo_estado === 'retirado') {
                $fechaSalida = date('Y-m-d H:i:s');
                $stmt->bindParam(':fechasalida', $fechaSalida, PDO::PARAM_STR);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Genera un código de ticket único consecutivo
     * 
     * @return string Código de ticket con formato EQ-00001, EQ-00002, etc.
     */
    private function generarCodigoTicket()
    {
        try {
            // Obtener el último número utilizado
            $query = "SELECT codigo_ticket FROM {$this->tabla} 
                  WHERE codigo_ticket LIKE 'EQ-%' 
                  ORDER BY idalmacen DESC 
                  LIMIT 1";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $ultimoCodigo = $stmt->fetchColumn();

            if ($ultimoCodigo) {
                // Extraer el número del último código (EQ-00001 -> 00001)
                $ultimoNumero = (int) substr($ultimoCodigo, 3);
                $nuevoNumero = $ultimoNumero + 1;
            } else {
                // Si no hay registros previos, empezar desde 1
                $nuevoNumero = 1;
            }

            // Formatear el nuevo código con 5 dígitos con ceros a la izquierda
            $codigo = "EQ-" . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);

            // Verificar que el código generado sea único (por seguridad)
            $queryVerificar = "SELECT COUNT(*) FROM {$this->tabla} WHERE codigo_ticket = :codigo";
            $stmtVerificar = $this->conexion->prepare($queryVerificar);
            $stmtVerificar->bindParam(':codigo', $codigo, PDO::PARAM_STR);
            $stmtVerificar->execute();

            if ($stmtVerificar->fetchColumn() > 0) {
                // Si por alguna razón ya existe, intentar con el siguiente número
                $nuevoNumero++;
                $codigo = "EQ-" . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
            }

            return $codigo;
        } catch (PDOException $e) {
            // Si hay algún error, usar el método de respaldo con timestamp
            error_log('Error al generar código consecutivo: ' . $e->getMessage());
            return $this->generarCodigoRespaldo();
        }
    }

    /**
     * Genera un código de respaldo en caso de error
     * 
     * @return string Código de ticket de respaldo
     */
    private function generarCodigoRespaldo()
    {
        // Método de respaldo usando timestamp
        $timestamp = time();
        $aleatorio = mt_rand(100, 999);
        return "EQ-{$timestamp}-{$aleatorio}";
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
     * Valida los datos de un registro de almacenamiento de equipaje
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

        // Validar precio de equipaje
        if (empty($datos['idpequipaje'])) {
            $errores[] = 'El tipo/precio de equipaje es obligatorio.';
        }

        // Validar monto
        if (!isset($datos['monto']) || $datos['monto'] <= 0) {
            $errores[] = 'El monto debe ser mayor que cero.';
        }

        // Validar cantidad de piezas si se proporciona
        if (isset($datos['cantidad_piezas']) && $datos['cantidad_piezas'] < 1) {
            $errores[] = 'La cantidad de piezas debe ser al menos 1.';
        }

        // Validar estado si se proporciona
        if (isset($datos['estado']) && !in_array($datos['estado'], ['almacenado', 'retirado', 'perdido', 'dañado'])) {
            $errores[] = 'El estado debe ser almacenado, retirado, perdido o dañado.';
        }

        return $errores;
    }

    /**
     * Obtiene los clientes para mostrar en el selector
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
     * Obtiene los precios de equipaje activos para mostrar en el selector
     * 
     * @return array Lista de precios de equipaje
     */
    public function getPreciosEquipaje()
    {
        try {
            $query = "SELECT idprecioe, tamano, descripcion, precio 
                      FROM precio_equipaje 
                      WHERE estado = 1 
                      ORDER BY tamano";

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
     * Obtiene estadísticas de almacenamiento de equipaje solo para el día actual
     * 
     * @return array Estadísticas del día actual
     */
    public function getEstadisticas()
    {
        try {
            // Total de registros de hoy
            $queryTotalHoy = "SELECT COUNT(*) as total 
                         FROM {$this->tabla}
                         WHERE DATE(fechaentrada) = CURDATE()";
            $stmtTotalHoy = $this->conexion->prepare($queryTotalHoy);
            $stmtTotalHoy->execute();
            $totalHoy = $stmtTotalHoy->fetch(PDO::FETCH_ASSOC)['total'];

            // Registros por estado de hoy
            $queryPorEstadoHoy = "SELECT estado, COUNT(*) as cantidad 
                             FROM {$this->tabla} 
                             WHERE DATE(fechaentrada) = CURDATE()
                             GROUP BY estado";
            $stmtPorEstadoHoy = $this->conexion->prepare($queryPorEstadoHoy);
            $stmtPorEstadoHoy->execute();
            $porEstadoHoy = $stmtPorEstadoHoy->fetchAll(PDO::FETCH_ASSOC);

            // Convertir a un array asociativo
            $cantidadPorEstadoHoy = [
                'almacenado' => 0,
                'retirado' => 0,
                'perdido' => 0,
                'dañado' => 0
            ];

            foreach ($porEstadoHoy as $estado) {
                $cantidadPorEstadoHoy[$estado['estado']] = (int)$estado['cantidad'];
            }

            // Ingresos de hoy
            $queryIngresosHoy = "SELECT SUM(monto) as ingresos_hoy 
                           FROM {$this->tabla}
                           WHERE DATE(fechaentrada) = CURDATE()";
            $stmtIngresosHoy = $this->conexion->prepare($queryIngresosHoy);
            $stmtIngresosHoy->execute();
            $ingresosHoy = $stmtIngresosHoy->fetch(PDO::FETCH_ASSOC)['ingresos_hoy'] ?? 0;

            return [
                'total_hoy' => $totalHoy,
                'almacenados_hoy' => $cantidadPorEstadoHoy['almacenado'],
                'retirados_hoy' => $cantidadPorEstadoHoy['retirado'],
                'perdidos_hoy' => $cantidadPorEstadoHoy['perdido'],
                'danados_hoy' => $cantidadPorEstadoHoy['dañado'],
                'ingresos_hoy' => $ingresosHoy
            ];
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'total_hoy' => 0,
                'almacenados_hoy' => 0,
                'retirados_hoy' => 0,
                'perdidos_hoy' => 0,
                'danados_hoy' => 0,
                'ingresos_hoy' => 0
            ];
        }
    }
    /**
     * Obtiene los datos completos de un equipaje para generar el recibo
     * 
     * @param int $id ID del registro de almacenamiento de equipaje
     * @return array|bool Datos completos del equipaje para el recibo o false si no existe
     */
    public function getDatosParaRecibo($id)
    {
        try {
            $query = "SELECT ae.idalmacen,
                             ae.codigo_ticket,
                             ae.descripcion,
                             ae.cantidad_piezas,
                             ae.fechaentrada,
                             ae.fechasalida,
                             ae.monto,
                             ae.estado,
                             ae.fechacreacion,
                             
                             -- Datos del cliente
                             p.nombre as cliente_nombre,
                             p.apellidopaterno as cliente_apellido_paterno,
                             p.apellidomaterno as cliente_apellido_materno,
                             CONCAT(p.nombre, ' ', p.apellidopaterno, 
                                    CASE WHEN p.apellidomaterno IS NOT NULL 
                                         THEN CONCAT(' ', p.apellidomaterno) 
                                         ELSE '' END) as nombre_cliente_completo,
                             p.tipodocumento as cliente_tipo_documento,
                             p.numdocumento as cliente_num_documento,
                             p.telefono as cliente_telefono,
                             p.email as cliente_email,
                             
                             -- Datos del usuario que registró
                             u.nombre as usuario_nombre,
                             u.apellidop as usuario_apellido_paterno,
                             u.apellidom as usuario_apellido_materno,
                             CONCAT(u.nombre, ' ', u.apellidop,
                                    CASE WHEN u.apellidom IS NOT NULL 
                                         THEN CONCAT(' ', u.apellidom) 
                                         ELSE '' END) as nombre_usuario_completo,
                             u.cargo as usuario_cargo,
                             
                             -- Datos del precio/tamaño
                             pe.tamano as equipaje_tamano,
                             pe.descripcion as equipaje_descripcion,
                             pe.precio as equipaje_precio_base
                             
                      FROM {$this->tabla} ae
                      LEFT JOIN persona p ON ae.idcliente = p.idpersona
                      LEFT JOIN usuarios u ON ae.idusuario = u.idusuario
                      LEFT JOIN precio_equipaje pe ON ae.idpequipaje = pe.idprecioe
                      WHERE ae.idalmacen = :id";

            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return false;
            }

            // Formatear y enriquecer los datos para el recibo
            $datos_recibo = [
                // Datos básicos del equipaje
                'id' => $resultado['idalmacen'],
                'codigo_ticket' => $resultado['codigo_ticket'],
                'descripcion' => $resultado['descripcion'] ?: 'Equipaje personal',
                'cantidad_piezas' => (int)$resultado['cantidad_piezas'],
                'monto' => (float)$resultado['monto'],
                'estado' => $resultado['estado'],

                // Fechas formateadas
                'fecha_entrada' => $resultado['fechaentrada'],
                'fecha_entrada_formateada' => date('d/m/Y H:i', strtotime($resultado['fechaentrada'])),
                'fecha_entrada_solo_fecha' => date('d/m/Y', strtotime($resultado['fechaentrada'])),
                'fecha_entrada_solo_hora' => date('H:i', strtotime($resultado['fechaentrada'])),
                'fecha_salida' => $resultado['fechasalida'],
                'fecha_salida_formateada' => $resultado['fechasalida'] ? date('d/m/Y H:i', strtotime($resultado['fechasalida'])) : null,

                // Datos del cliente
                'cliente' => [
                    'nombre_completo' => $resultado['nombre_cliente_completo'],
                    'nombre' => $resultado['cliente_nombre'],
                    'apellido_paterno' => $resultado['cliente_apellido_paterno'],
                    'apellido_materno' => $resultado['cliente_apellido_materno'],
                    'tipo_documento' => $resultado['cliente_tipo_documento'],
                    'num_documento' => $resultado['cliente_num_documento'],
                    'telefono' => $resultado['cliente_telefono'],
                    'email' => $resultado['cliente_email']
                ],

                // Datos del usuario que registró
                'usuario' => [
                    'nombre_completo' => $resultado['nombre_usuario_completo'],
                    'nombre' => $resultado['usuario_nombre'],
                    'apellido_paterno' => $resultado['usuario_apellido_paterno'],
                    'apellido_materno' => $resultado['usuario_apellido_materno'],
                    'cargo' => $resultado['usuario_cargo']
                ],

                // Datos del equipaje/tamaño
                'equipaje' => [
                    'tamano' => $resultado['equipaje_tamano'],
                    'descripcion_tamano' => $resultado['equipaje_descripcion'],
                    'precio_base' => (float)$resultado['equipaje_precio_base']
                ],

                // Datos calculados para el recibo
                'monto_formateado' => number_format($resultado['monto'], 2, ',', '.'),
                'estado_formateado' => $this->formatearEstado($resultado['estado']),
                'tiempo_almacenado' => $this->calcularTiempoAlmacenado($resultado['fechaentrada'], $resultado['fechasalida'])
            ];

            return $datos_recibo;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            error_log('Error al obtener datos para recibo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Formatea el estado para mostrar en el recibo
     * 
     * @param string $estado Estado del equipaje
     * @return string Estado formateado
     */
    private function formatearEstado($estado)
    {
        $estados = [
            'almacenado' => 'Almacenado',
            'retirado' => 'Retirado',
            'perdido' => 'Perdido',
            'dañado' => 'Dañado'
        ];

        return $estados[$estado] ?? ucfirst($estado);
    }

    /**
     * Calcula el tiempo que lleva almacenado el equipaje
     * 
     * @param string $fecha_entrada Fecha de entrada
     * @param string|null $fecha_salida Fecha de salida (null si aún está almacenado)
     * @return array Información del tiempo almacenado
     */
    private function calcularTiempoAlmacenado($fecha_entrada, $fecha_salida = null)
    {
        $fecha_inicio = new DateTime($fecha_entrada);
        $fecha_fin = $fecha_salida ? new DateTime($fecha_salida) : new DateTime();

        $diferencia = $fecha_inicio->diff($fecha_fin);

        return [
            'dias' => $diferencia->days,
            'horas' => $diferencia->h,
            'minutos' => $diferencia->i,
            'texto' => $this->formatearTiempo($diferencia)
        ];
    }

    /**
     * Formatea el tiempo de diferencia en texto legible
     * 
     * @param DateInterval $diferencia Diferencia de tiempo
     * @return string Tiempo formateado
     */
    private function formatearTiempo($diferencia)
    {
        $partes = [];

        if ($diferencia->days > 0) {
            $partes[] = $diferencia->days . ($diferencia->days == 1 ? ' día' : ' días');
        }

        if ($diferencia->h > 0) {
            $partes[] = $diferencia->h . ($diferencia->h == 1 ? ' hora' : ' horas');
        }

        if ($diferencia->i > 0 && $diferencia->days == 0) {
            $partes[] = $diferencia->i . ($diferencia->i == 1 ? ' minuto' : ' minutos');
        }

        if (empty($partes)) {
            return 'Menos de 1 minuto';
        }

        return implode(', ', $partes);
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
     * Obtiene el último ID insertado
     * 
     * @return string Último ID insertado
     */
    public function getLastInsertId()
    {
        return $this->conexion->lastInsertId();
    }
}

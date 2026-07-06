<?php

/**
 * Modelo Dashboard
 * 
 * Obtiene estadísticas y datos para el dashboard desde diversos módulos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class Dashboard
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        require_once __DIR__ . '/../config/conexion.php';
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Registra en el log un error capturado, con contexto sobre dónde ocurrió
     *
     * @param string $contexto Descripción de la operación que falló
     * @param Exception $e Excepción capturada
     */
    private function logError($contexto, $e)
    {
        error_log($contexto . ': ' . $e->getMessage());
    }

    /**
     * Obtiene estadísticas generales para el dashboard
     * 
     * @return array Estadísticas generales
     */
    public function getEstadisticasGenerales()
    {
        try {
            $stats = [
                'usuarios' => $this->getEstadisticasUsuarios(),
                'servicios_bano' => $this->getEstadisticasServiciosBano(),
                'equipajes' => $this->getEstadisticasEquipajes(),
                'productos' => $this->getEstadisticasProductos(),
                'banos' => $this->getEstadisticasBanos(),
                'personas' => $this->getEstadisticasPersonas()
            ];

            return $stats;
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas generales', $e);
            return [];
        }
    }

    /**
     * Obtiene estadísticas de usuarios
     * 
     * @return array Estadísticas de usuarios
     */
    public function getEstadisticasUsuarios()
    {
        try {
            // Total de usuarios
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM usuarios");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Usuarios activos
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activos FROM usuarios WHERE estado = 1");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Usuarios inactivos
            $inactivos = $total - $activos;

            // Últimos usuarios registrados (5)
            $stmt = $this->conexion->prepare("
                SELECT idusuario, nombre, correo, cargo, imagen, fechacreacion as fecha_registro
                FROM usuarios
                WHERE estado = 1 
                ORDER BY fechacreacion DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'ultimos' => $ultimos
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de usuarios', $e);
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'ultimos' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas de servicios de baño
     * 
     * @return array Estadísticas de servicios de baño
     */
    public function getEstadisticasServiciosBano()
    {
        try {
            // Total de servicios
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM servicio_bano");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Servicios de hoy
            $fecha_hoy = date('Y-m-d');
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as total_hoy 
                FROM servicio_bano 
                WHERE DATE(fecha) = :fecha_hoy
            ");
            $stmt->bindParam(':fecha_hoy', $fecha_hoy);
            $stmt->execute();
            $total_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total_hoy'];

            // Servicios pendientes (iniciados)
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as pendientes 
                FROM servicio_bano 
                WHERE estado = 'iniciado'
            ");
            $stmt->execute();
            $pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];

            // Servicios finalizados
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as finalizados 
                FROM servicio_bano 
                WHERE estado = 'finalizado'
            ");
            $stmt->execute();
            $finalizados = $stmt->fetch(PDO::FETCH_ASSOC)['finalizados'];

            // Servicios cancelados
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cancelados 
                FROM servicio_bano 
                WHERE estado = 'cancelado'
            ");
            $stmt->execute();
            $cancelados = $stmt->fetch(PDO::FETCH_ASSOC)['cancelados'];

            // Últimos servicios (5)
            $stmt = $this->conexion->prepare("
                SELECT sb.idservicio as id, sb.fecha, 
                       b.nombre as numero_bano, 
                       sb.duracion_minutos, sb.estado,
                       sb.total as precio
                FROM servicio_bano sb
                JOIN bano b ON sb.idbano = b.idbano
                ORDER BY sb.fecha DESC
                LIMIT 5
            ");
            $stmt->execute();
            $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear los datos de ultimos servicios
            foreach ($ultimos as &$servicio) {
                // Extraer la hora de la fecha
                if (isset($servicio['fecha'])) {
                    $servicio['hora'] = date('H:i', strtotime($servicio['fecha']));
                }

                // Agregar un tipo de servicio predeterminado (ya que no tenemos esa tabla)
                $servicio['tipo_servicio'] = 'Baño completo';

                // Asegurar que existe el campo id_bano
                if (!isset($servicio['id_bano']) && isset($servicio['numero_bano'])) {
                    // Consultar el ID del baño por su nombre
                    $stmt = $this->conexion->prepare("
                        SELECT idbano FROM bano WHERE nombre = :nombre LIMIT 1
                    ");
                    $stmt->bindParam(':nombre', $servicio['numero_bano']);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $servicio['id_bano'] = $result ? $result['idbano'] : null;
                }
            }

            return [
                'total' => $total,
                'total_hoy' => $total_hoy,
                'pendientes' => $pendientes,
                'finalizados' => $finalizados,
                'cancelados' => $cancelados,
                'ultimos' => $ultimos
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de servicios de baño', $e);
            return [
                'total' => 0,
                'total_hoy' => 0,
                'pendientes' => 0,
                'finalizados' => 0,
                'cancelados' => 0,
                'ultimos' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas de equipajes
     * 
     * @return array Estadísticas de equipajes
     */
    public function getEstadisticasEquipajes()
    {
        try {
            // Total de equipajes
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM almacenamiento_equipaje");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Equipajes activos (almacenados)
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as activos 
                FROM almacenamiento_equipaje 
                WHERE estado = 'almacenado'
            ");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Equipajes por vencer (próximos 3 días)
            $fecha_actual = date('Y-m-d');
            $fecha_limite = date('Y-m-d', strtotime('+3 days'));

            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as por_vencer 
                FROM almacenamiento_equipaje 
                WHERE estado = 'almacenado' 
                AND fechasalida BETWEEN :fecha_actual AND :fecha_limite
            ");
            $stmt->bindParam(':fecha_actual', $fecha_actual);
            $stmt->bindParam(':fecha_limite', $fecha_limite);
            $stmt->execute();
            $por_vencer = $stmt->fetch(PDO::FETCH_ASSOC)['por_vencer'];

            // Equipajes por vencer con detalles
            $stmt = $this->conexion->prepare("
                SELECT e.idalmacen as id, e.descripcion, e.fechaentrada, e.fechasalida as fecha_vencimiento,
                       CONCAT(p.nombre, ' ', p.apellidopaterno) as nombre_cliente,
                       p.apellidopaterno as apellido_cliente,
                       DATEDIFF(e.fechasalida, CURDATE()) as dias_restantes
                FROM almacenamiento_equipaje e
                JOIN persona p ON e.idcliente = p.idpersona
                WHERE e.estado = 'almacenado' 
                AND e.fechasalida BETWEEN :fecha_actual AND :fecha_limite
                ORDER BY e.fechasalida ASC
                LIMIT 5
            ");
            $stmt->bindParam(':fecha_actual', $fecha_actual);
            $stmt->bindParam(':fecha_limite', $fecha_limite);
            $stmt->execute();
            $lista_por_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'activos' => $activos,
                'por_vencer' => $por_vencer,
                'lista_por_vencer' => $lista_por_vencer
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de equipajes', $e);
            return [
                'total' => 0,
                'activos' => 0,
                'por_vencer' => 0,
                'lista_por_vencer' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas de productos
     * 
     * @return array Estadísticas de productos
     */
    public function getEstadisticasProductos()
    {
        try {
            // Total de productos
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM productos");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Productos activos
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activos FROM productos WHERE estado = 1");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Productos con stock bajo
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as stock_bajo 
                FROM productos 
                WHERE stock <= stock_minimo AND estado = 1
            ");
            $stmt->execute();
            $stock_bajo = $stmt->fetch(PDO::FETCH_ASSOC)['stock_bajo'];

            // Productos con stock bajo (detalles)
            $stmt = $this->conexion->prepare("
                SELECT p.idproducto, p.nombre, p.stock, p.stock_minimo,
                       c.nombre as categoria
                FROM productos p
                JOIN categoria c ON p.idcategoria = c.idcategoria
                WHERE p.stock <= p.stock_minimo AND p.estado = 1
                ORDER BY (p.stock_minimo - p.stock) DESC
                LIMIT 5
            ");
            $stmt->execute();
            $lista_stock_bajo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'activos' => $activos,
                'stock_bajo' => $stock_bajo,
                'lista_stock_bajo' => $lista_stock_bajo
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de productos', $e);
            return [
                'total' => 0,
                'activos' => 0,
                'stock_bajo' => 0,
                'lista_stock_bajo' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas de baños
     * 
     * @return array Estadísticas de baños
     */
    public function getEstadisticasBanos()
    {
        try {
            // Total de baños
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM bano");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Baños disponibles
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as disponibles 
                FROM bano 
                WHERE estado = 'disponible'
            ");
            $stmt->execute();
            $disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['disponibles'];

            // Baños ocupados
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as ocupados 
                FROM bano 
                WHERE estado = 'ocupada'
            ");
            $stmt->execute();
            $ocupados = $stmt->fetch(PDO::FETCH_ASSOC)['ocupados'];

            // Baños en limpieza
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as limpieza 
                FROM bano 
                WHERE estado = 'limpieza'
            ");
            $stmt->execute();
            $limpieza = $stmt->fetch(PDO::FETCH_ASSOC)['limpieza'];

            // Baños en mantenimiento
            $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as mantenimiento 
                FROM bano 
                WHERE estado = 'mantenimiento'
            ");
            $stmt->execute();
            $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC)['mantenimiento'];

            // Lista de baños para limpieza
            $stmt = $this->conexion->prepare("
                SELECT idbano, nombre as numero, ubicacion as tipo, 
                       'Estándar' as precio, estado, fechaactualizacion as actualizado
                FROM bano 
                WHERE estado = 'limpieza'
                ORDER BY fechaactualizacion ASC
            ");
            $stmt->execute();
            $lista_limpieza = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lista de baños disponibles
            $stmt = $this->conexion->prepare("
                SELECT idbano, nombre as numero, ubicacion as tipo, 
                       'Estándar' as precio, estado
                FROM bano 
                WHERE estado = 'disponible'
                ORDER BY nombre ASC
            ");
            $stmt->execute();
            $lista_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'disponibles' => $disponibles,
                'ocupados' => $ocupados,
                'limpieza' => $limpieza,
                'mantenimiento' => $mantenimiento,
                'lista_limpieza' => $lista_limpieza,
                'lista_disponibles' => $lista_disponibles
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de baños', $e);
            return [
                'total' => 0,
                'disponibles' => 0,
                'ocupados' => 0,
                'limpieza' => 0,
                'mantenimiento' => 0,
                'lista_limpieza' => [],
                'lista_disponibles' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas de personas (clientes)
     * 
     * @return array Estadísticas de personas
     */
    public function getEstadisticasPersonas()
    {
        try {
            // Total de personas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM persona");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Personas activas
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activos FROM persona WHERE estado = 1");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Últimas personas registradas
            $stmt = $this->conexion->prepare("
                SELECT idpersona, nombre, apellidopaterno as apellido, 
                       numdocumento as ci, telefono, fechacreacion as fecha_registro
                FROM persona
                WHERE estado = 1
                ORDER BY fechacreacion DESC
                LIMIT 5
            ");
            $stmt->execute();
            $ultimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'activos' => $activos,
                'ultimos' => $ultimos
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de personas', $e);
            return [
                'total' => 0,
                'activos' => 0,
                'ultimos' => []
            ];
        }
    }

    /**
     * Obtiene datos para gráficos de actividad reciente
     * 
     * @param int $dias Número de días para los datos
     * @return array Datos para gráficos
     */
    public function getDatosGraficos($dias = 7)
    {
        // Nombres de los días abreviados
        $nombres_dias_cortos = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        // Fecha actual
        $hoy = date('Y-m-d');

        $labels = [];
        $fechas_completas = [];
        $servicios_bano = [];
        $equipajes = [];
        $ocupacion = [];
        $ingresos = [];

        // Generar fechas para los últimos días
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days", strtotime($hoy)));
            $dia_semana = date('w', strtotime($fecha));

            // Guardar etiqueta y fecha completa
            $labels[] = $nombres_dias_cortos[$dia_semana];
            $fechas_completas[] = date('d/m/Y', strtotime($fecha));

            try {
                // Servicios de baño por día
                $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cantidad
                FROM servicio_bano
                WHERE DATE(fecha) = :fecha
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $servicios_bano[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
            } catch (PDOException $e) {
                $this->logError('Error en consulta de servicios de baño', $e);
                $servicios_bano[] = 0;
            }

            try {
                // Equipajes registrados por día
                $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cantidad
                FROM almacenamiento_equipaje
                WHERE DATE(fechaentrada) = :fecha
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $equipajes[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
            } catch (PDOException $e) {
                $this->logError('Error en consulta de equipajes', $e);
                $equipajes[] = 0;
            }

            try {
                // Ocupación de habitaciones por día
                $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cantidad
                FROM recepcion
                WHERE :fecha BETWEEN DATE(fechaentrada) AND DATE(COALESCE(fechasalida, fechasalida_prevista))
                AND estado IN ('en_curso', 'reservado')
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ocupacion[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
            } catch (PDOException $e) {
                $this->logError('Error en consulta de ocupación', $e);
                $ocupacion[] = 0;
            }

            // Cálculo de ingresos totales por día (todas las fuentes)
            $ingreso_dia = 0;

            try {
                // 1. Ingresos de recepción
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(montototal), 0) as total
                FROM recepcion
                WHERE DATE(fechacreacion) = :fecha 
                AND estado IN ('en_curso', 'finalizado')
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_recepcion = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
                $ingreso_dia += $ingreso_recepcion;
            } catch (PDOException $e) {
                $this->logError('Error en consulta de ingresos de recepción', $e);
            }

            try {
                // 2. Ingresos de servicios de baño
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(total), 0) as total
                FROM servicio_bano
                WHERE DATE(fecha) = :fecha 
                AND estado = 'finalizado'
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_bano = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
                $ingreso_dia += $ingreso_bano;
            } catch (PDOException $e) {
                $this->logError('Error en consulta de ingresos de servicios de baño', $e);
            }

            try {
                // 3. Ingresos de almacenamiento de equipaje
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(monto), 0) as total
                FROM almacenamiento_equipaje
                WHERE DATE(fechaentrada) = :fecha
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_equipaje = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
                $ingreso_dia += $ingreso_equipaje;
            } catch (PDOException $e) {
                $this->logError('Error en consulta de ingresos de equipaje', $e);
            }

            try {
                // 4. Ingresos de ventas
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(totalventa), 0) as total
                FROM venta
                WHERE DATE(fechaventa) = :fecha
                AND estado = 1
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_ventas = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
                $ingreso_dia += $ingreso_ventas;
            } catch (PDOException $e) {
                $this->logError('Error en consulta de ingresos de ventas', $e);
            }

            // Añadir el ingreso total del día al array
            $ingresos[] = $ingreso_dia;
        }

        // Verificar si hay al menos algunos datos reales
        $hay_datos = false;
        foreach ([$servicios_bano, $equipajes, $ocupacion, $ingresos] as $dataset) {
            if (array_sum($dataset) > 0) {
                $hay_datos = true;
                break;
            }
        }

        // Si no hay datos reales, usar datos de ejemplo
        if (!$hay_datos) {
            error_log("No se encontraron datos reales. Usando datos de ejemplo.");
            return [
                'labels' => $labels,
                'fechas_completas' => $fechas_completas,
                'servicios_bano' => [5, 7, 3, 8, 6, 9, 5],
                'equipajes' => [2, 3, 1, 5, 2, 6, 3],
                'ocupacion' => [8, 10, 12, 9, 15, 18, 14],
                'ingresos' => [1000, 1500, 800, 1200, 2000, 2500, 1800]
            ];
        }

        return [
            'labels' => $labels,
            'fechas_completas' => $fechas_completas,
            'servicios_bano' => $servicios_bano,
            'equipajes' => $equipajes,
            'ocupacion' => $ocupacion,
            'ingresos' => $ingresos
        ];
    }

    /**
     * Obtiene las asignaciones de limpieza para un usuario específico
     * 
     * @param int $idusuario ID del usuario
     * @return array Asignaciones de limpieza del usuario
     */
    public function getAsignacionesLimpiezaUsuario($idusuario)
    {
        try {
            // Asignaciones pendientes y en progreso del usuario
            $stmt = $this->conexion->prepare("
            SELECT a.idasignacion, a.fecha, a.hora, a.estado, a.observaciones,
                   h.numero as numero_habitacion, h.id_habitacion as idhabitacion,
                   CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                   u.idusuario,
                   t.nombre as tipo_habitacion
            FROM asignaciones_limpieza a
            JOIN habitaciones h ON a.idhabitacion = h.id_habitacion
            JOIN usuarios u ON a.idusuario = u.idusuario
            JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
            WHERE a.idusuario = :idusuario
            AND a.estado IN ('pendiente', 'enprogreso')
            ORDER BY a.fecha ASC, a.hora ASC
        ");
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();
            $asignaciones_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Asignaciones completadas recientemente por el usuario
            $stmt = $this->conexion->prepare("
            SELECT a.idasignacion, a.fecha, a.hora, a.estado, a.observaciones,
                   h.numero as numero_habitacion, h.id_habitacion as idhabitacion,
                   CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                   u.idusuario,
                   t.nombre as tipo_habitacion,
                   a.fechaactualizacion
            FROM asignaciones_limpieza a
            JOIN habitaciones h ON a.idhabitacion = h.id_habitacion
            JOIN usuarios u ON a.idusuario = u.idusuario
            JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
            WHERE a.idusuario = :idusuario
            AND a.estado IN ('completada', 'verificada')
            ORDER BY a.fechaactualizacion DESC
            LIMIT 5
        ");
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();
            $asignaciones_completadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Estadísticas resumidas
            $stmt = $this->conexion->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'enprogreso' THEN 1 ELSE 0 END) as enprogreso,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'verificada' THEN 1 ELSE 0 END) as verificadas,
                SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as hoy
            FROM asignaciones_limpieza
            WHERE idusuario = :idusuario
        ");
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
            $stmt->execute();
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'pendientes' => $asignaciones_pendientes,
                'completadas' => $asignaciones_completadas,
                'estadisticas' => $estadisticas
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener asignaciones de limpieza', $e);
            return [
                'pendientes' => [],
                'completadas' => [],
                'estadisticas' => [
                    'total' => 0,
                    'pendientes' => 0,
                    'enprogreso' => 0,
                    'completadas' => 0,
                    'verificadas' => 0,
                    'hoy' => 0
                ]
            ];
        }
    }

    /**
     * Obtiene todas las asignaciones de limpieza (no filtradas por usuario)
     * 
     * @return array Asignaciones de limpieza
     */
    public function getTodasAsignacionesLimpieza()
    {
        try {
            // Todas las asignaciones ordenadas por estado y fecha (pendientes primero)
            $estados_orden = "'pendiente', 'enprogreso', 'completada', 'verificada'";
            $stmt = $this->conexion->prepare("
            SELECT a.idasignacion, a.fecha, a.hora, a.estado, a.observaciones,
                   h.numero as numero_habitacion, h.id_habitacion as idhabitacion,
                   CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario,
                   u.idusuario,
                   t.nombre as tipo_habitacion
            FROM asignaciones_limpieza a
            JOIN habitaciones h ON a.idhabitacion = h.id_habitacion
            JOIN usuarios u ON a.idusuario = u.idusuario
            JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
            ORDER BY FIELD(a.estado, {$estados_orden}), a.fecha, a.hora
        ");
            $stmt->execute();
            $todas_asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Estadísticas resumidas
            $stmt = $this->conexion->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'enprogreso' THEN 1 ELSE 0 END) as enprogreso,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'verificada' THEN 1 ELSE 0 END) as verificadas,
                SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as hoy
            FROM asignaciones_limpieza
        ");
            $stmt->execute();
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'todas' => $todas_asignaciones,
                'estadisticas' => $estadisticas
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener asignaciones de limpieza', $e);
            return [
                'todas' => [],
                'estadisticas' => [
                    'total' => 0,
                    'pendientes' => 0,
                    'enprogreso' => 0,
                    'completadas' => 0,
                    'verificadas' => 0,
                    'hoy' => 0
                ]
            ];
        }
    }

    /**
     * Obtiene todas las habitaciones con su estado y tipo
     * 
     * @return array Habitaciones con estado
     */
    public function getHabitacionesConEstado()
    {
        try {
            $stmt = $this->conexion->prepare("
            SELECT h.id_habitacion, p.idpiso, h.numero, h.estado,
                   t.nombre as tipo_habitacion,
                   p.nombre as piso_nombre
            FROM habitaciones h
            JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
            JOIN pisos p ON h.idpiso = p.idpiso
            ORDER BY h.numero ASC
        ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('Error al obtener habitaciones con estado', $e);
            return [];
        }
    }

    /**
     * Obtiene estadísticas detalladas de habitaciones
     * 
     * @return array Estadísticas de habitaciones
     */
    public function getEstadisticasHabitaciones()
    {
        try {
            // Total de habitaciones
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM habitaciones");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Habitaciones por estado
            $estados = ['disponible', 'ocupada', 'limpieza', 'mantenimiento'];
            $por_estado = [];

            foreach ($estados as $estado) {
                $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cantidad 
                FROM habitaciones 
                WHERE estado = :estado
            ");
                $stmt->bindParam(':estado', $estado);
                $stmt->execute();
                $por_estado[$estado] = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
            }

            // Ocupación actual
            $ocupacion = [
                'total' => $total,
                'ocupadas' => $por_estado['ocupada'],
                'porcentaje' => ($total > 0) ? round(($por_estado['ocupada'] / $total) * 100) : 0
            ];

            // Habitaciones por piso
            $stmt = $this->conexion->prepare("
            SELECT p.idpiso, p.nombre as nombre_piso, COUNT(h.id_habitacion) as cantidad
            FROM habitaciones h
            JOIN pisos p ON h.idpiso = p.idpiso
            GROUP BY p.idpiso, p.nombre
        ");
            $stmt->execute();
            $por_piso = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Últimas actualizaciones de estado
            $stmt = $this->conexion->prepare("
            SELECT h.id_habitacion, h.numero, h.estado, h.fechaactualizacion, t.nombre as tipo
            FROM habitaciones h
            JOIN tipo_habitacion t ON h.id_tipo = t.id_tipo
            ORDER BY h.fechaactualizacion DESC
            LIMIT 5
        ");
            $stmt->execute();
            $ultimas_actualizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'por_estado' => $por_estado,
                'ocupacion' => $ocupacion,
                'por_piso' => $por_piso,
                'ultimas_actualizaciones' => $ultimas_actualizaciones
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de habitaciones', $e);
            return [
                'total' => 0,
                'por_estado' => ['disponible' => 0, 'ocupada' => 0, 'limpieza' => 0, 'mantenimiento' => 0],
                'ocupacion' => ['total' => 0, 'ocupadas' => 0, 'porcentaje' => 0],
                'por_piso' => [],
                'ultimas_actualizaciones' => []
            ];
        }
    }

    /**
     * Obtiene estadísticas detalladas de baños
     * 
     * @return array Estadísticas de baños
     */
    public function getEstadisticasBanosDetalladas()
    {
        try {
            // Total de baños
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM bano");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Baños por estado
            $estados = ['disponible', 'ocupada', 'mantenimiento', 'fuera_servicio', 'limpieza'];
            $por_estado = [];

            foreach ($estados as $estado) {
                $stmt = $this->conexion->prepare("
                SELECT COUNT(*) as cantidad 
                FROM bano 
                WHERE estado = :estado
            ");
                $stmt->bindParam(':estado', $estado);
                $stmt->execute();
                $por_estado[$estado] = $stmt->fetch(PDO::FETCH_ASSOC)['cantidad'];
            }

            // Todos los baños con su estado
            $stmt = $this->conexion->prepare("
            SELECT idbano, nombre, ubicacion, estado, fechaactualizacion
            FROM bano
            ORDER BY nombre ASC
        ");
            $stmt->execute();
            $todos_banos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ingresos por baños (últimos 30 días)
            $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
            $stmt = $this->conexion->prepare("
            SELECT SUM(total) as ingresos
            FROM servicio_bano
            WHERE fecha >= :fecha_inicio AND estado = 'finalizado'
        ");
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->execute();
            $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0;

            return [
                'total' => $total,
                'disponibles' => $por_estado['disponible'] ?? 0,
                'ocupados' => $por_estado['ocupada'] ?? 0,
                'limpieza' => $por_estado['limpieza'] ?? 0,
                'mantenimiento' => $por_estado['mantenimiento'] ?? 0,
                'fuera_servicio' => $por_estado['fuera_servicio'] ?? 0,
                'todos' => $todos_banos,
                'ingresos_mes' => floatval($ingresos)
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas detalladas de baños', $e);
            return [
                'total' => 0,
                'disponibles' => 0,
                'ocupados' => 0,
                'limpieza' => 0,
                'mantenimiento' => 0,
                'fuera_servicio' => 0,
                'todos' => [],
                'ingresos_mes' => 0
            ];
        }
    }

    /**
     * Obtiene estadísticas detalladas de clientes (personas)
     * 
     * @return array Estadísticas de clientes
     */
    public function getEstadisticasClientesDetalladas()
    {
        try {
            // Total de clientes
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as total FROM persona");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Clientes activos
            $stmt = $this->conexion->prepare("SELECT COUNT(*) as activos FROM persona WHERE estado = 1");
            $stmt->execute();
            $activos = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Clientes por mes (últimos 6 meses)
            $stmt = $this->conexion->prepare("
            SELECT DATE_FORMAT(fechacreacion, '%Y-%m') as mes, COUNT(*) as cantidad
            FROM persona
            WHERE fechacreacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fechacreacion, '%Y-%m')
            ORDER BY mes ASC
        ");
            $stmt->execute();
            $por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convertir a formato para gráfico
            $labels = [];
            $datos = [];

            foreach ($por_mes as $registro) {
                $fecha = new DateTime($registro['mes'] . '-01');
                $labels[] = $fecha->format('M Y');
                $datos[] = intval($registro['cantidad']);
            }

            // Clientes nuevos este mes
            $hoy = date('Y-m-01');
            $stmt = $this->conexion->prepare("
            SELECT COUNT(*) as nuevos
            FROM persona
            WHERE fechacreacion >= :primer_dia_mes
        ");
            $stmt->bindParam(':primer_dia_mes', $hoy);
            $stmt->execute();
            $nuevos_mes = $stmt->fetch(PDO::FETCH_ASSOC)['nuevos'];

            return [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $total - $activos,
                'nuevos_mes' => $nuevos_mes,
                'grafico' => [
                    'labels' => $labels,
                    'datos' => $datos
                ]
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas detalladas de clientes', $e);
            return [
                'total' => 0,
                'activos' => 0,
                'inactivos' => 0,
                'nuevos_mes' => 0,
                'grafico' => [
                    'labels' => [],
                    'datos' => []
                ]
            ];
        }
    }

    /**
     * Obtiene datos para gráficos de ingresos
     * 
     * @param int $dias Número de días para los datos
     * @return array Datos para gráficos de ingresos
     */
    public function getDatosGraficosIngresos($dias = 30)
    {
        try {
            $labels = [];
            $servicios_bano = [];
            $equipajes = [];
            $total = [];

            // Generar fechas para los últimos días
            for ($i = $dias - 1; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d/m', strtotime($fecha));

                // Ingresos de servicios de baño por día
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(total), 0) as ingreso
                FROM servicio_bano
                WHERE DATE(fecha) = :fecha AND estado = 'finalizado'
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_bano = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingreso']);
                $servicios_bano[] = $ingreso_bano;

                // Ingresos de equipajes por día
                $stmt = $this->conexion->prepare("
                SELECT COALESCE(SUM(monto), 0) as ingreso
                FROM almacenamiento_equipaje
                WHERE DATE(fechaentrada) = :fecha
            ");
                $stmt->bindParam(':fecha', $fecha);
                $stmt->execute();
                $ingreso_equipaje = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingreso']);
                $equipajes[] = $ingreso_equipaje;

                // Total de ingresos diarios
                $total[] = $ingreso_bano + $ingreso_equipaje;
            }

            return [
                'labels' => $labels,
                'servicios_bano' => $servicios_bano,
                'equipajes' => $equipajes,
                'total' => $total
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener datos para gráficos de ingresos', $e);
            return [
                'labels' => [],
                'servicios_bano' => [],
                'equipajes' => [],
                'total' => []
            ];
        }
    }

    /**
     * Obtiene los ingresos totales del día actual (recepción + servicios + equipajes + ventas)
     * 
     * @return array Total de ingresos combinados del día y su desglose
     */
    public function getIngresosTotalesHoy()
    {
        try {
            // La zona horaria ya está configurada tanto en PHP como en MariaDB

            // Obtener inicio y fin del día actual
            $fecha_inicio = date('Y-m-d 00:00:00');
            $fecha_fin = date('Y-m-d 23:59:59');

            // Ingresos de recepción
            $stmt = $this->conexion->prepare("
            SELECT COALESCE(SUM(montototal), 0) as ingresos_recepcion
            FROM recepcion
            WHERE fechacreacion BETWEEN :fecha_inicio AND :fecha_fin
            AND estado IN ('en_curso', 'finalizado')
        ");
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $ingresos_recepcion = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingresos_recepcion'] ?? 0);

            // Ingresos de servicios de baño
            $stmt = $this->conexion->prepare("
            SELECT COALESCE(SUM(total), 0) as ingresos_bano
            FROM servicio_bano
            WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin
            AND estado = 'finalizado'
        ");
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $ingresos_bano = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingresos_bano'] ?? 0);

            // Ingresos de almacenamiento de equipaje
            $stmt = $this->conexion->prepare("
            SELECT COALESCE(SUM(monto), 0) as ingresos_equipaje
            FROM almacenamiento_equipaje
            WHERE fechaentrada BETWEEN :fecha_inicio AND :fecha_fin
        ");
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $ingresos_equipaje = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingresos_equipaje'] ?? 0);

            // Ingresos de ventas
            $stmt = $this->conexion->prepare("
            SELECT COALESCE(SUM(totalventa), 0) as ingresos_ventas
            FROM venta
            WHERE fechaventa BETWEEN :fecha_inicio AND :fecha_fin
            AND estado = 1
        ");
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin);
            $stmt->execute();
            $ingresos_ventas = floatval($stmt->fetch(PDO::FETCH_ASSOC)['ingresos_ventas'] ?? 0);

            // Total combinado
            $total_ingresos = $ingresos_recepcion + $ingresos_bano + $ingresos_equipaje + $ingresos_ventas;

            return [
                'total' => $total_ingresos,
                'desglose' => [
                    'recepcion' => $ingresos_recepcion,
                    'bano' => $ingresos_bano,
                    'equipaje' => $ingresos_equipaje,
                    'ventas' => $ingresos_ventas
                ],
                'fecha' => date('Y-m-d')
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener ingresos totales del día', $e);
            return [
                'total' => 0,
                'desglose' => [
                    'recepcion' => 0,
                    'bano' => 0,
                    'equipaje' => 0,
                    'ventas' => 0
                ]
            ];
        }
    }

    /**
     * Obtiene información sobre las asignaciones de limpieza pendientes
     * 
     * @return array Estadísticas de asignaciones de limpieza
     */
    public function getEstadisticasLimpiezaPendientes()
    {
        try {
            // Total de asignaciones pendientes
            $stmt = $this->conexion->prepare("
            SELECT COUNT(*) as total_pendientes
            FROM asignaciones_limpieza
            WHERE estado IN ('pendiente', 'enprogreso')
        ");
            $stmt->execute();
            $total_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total_pendientes'];

            // Asignaciones pendientes por día
            $fecha_hoy = date('Y-m-d');
            $stmt = $this->conexion->prepare("
            SELECT COUNT(*) as pendientes_hoy
            FROM asignaciones_limpieza
            WHERE fecha = :fecha_hoy
            AND estado IN ('pendiente', 'enprogreso')
        ");
            $stmt->bindParam(':fecha_hoy', $fecha_hoy);
            $stmt->execute();
            $pendientes_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes_hoy'];

            // Habitaciones en estado de limpieza
            $stmt = $this->conexion->prepare("
            SELECT COUNT(*) as hab_limpieza
            FROM habitaciones
            WHERE estado = 'limpieza'
        ");
            $stmt->execute();
            $hab_limpieza = $stmt->fetch(PDO::FETCH_ASSOC)['hab_limpieza'];

            // Últimas asignaciones pendientes
            $stmt = $this->conexion->prepare("
            SELECT a.idasignacion, a.fecha, a.hora, a.estado,
                   h.numero as numero_habitacion, h.id_habitacion,
                   CONCAT(u.nombre, ' ', u.apellidop) as nombre_usuario
            FROM asignaciones_limpieza a
            JOIN habitaciones h ON a.idhabitacion = h.id_habitacion
            JOIN usuarios u ON a.idusuario = u.idusuario
            WHERE a.estado IN ('pendiente', 'enprogreso')
            ORDER BY a.fecha ASC, a.hora ASC
            LIMIT 5
        ");
            $stmt->execute();
            $ultimas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_pendientes' => $total_pendientes,
                'pendientes_hoy' => $pendientes_hoy,
                'hab_limpieza' => $hab_limpieza,
                'ultimas_pendientes' => $ultimas_pendientes
            ];
        } catch (PDOException $e) {
            $this->logError('Error al obtener estadísticas de limpieza pendientes', $e);
            return [
                'total_pendientes' => 0,
                'pendientes_hoy' => 0,
                'hab_limpieza' => 0,
                'ultimas_pendientes' => []
            ];
        }
    }
}

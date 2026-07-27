<?php

/**
 * Controlador de Dashboard
 * 
 * Gestiona la obtención de datos para el dashboard
 * 
 */
class DashboardController
{
    /**
     * Modelo de Dashboard
     * @var Dashboard
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Dashboard
        require_once __DIR__ . '/../../models/Dashboard.php';
        $this->modelo = new Dashboard();
    }

    /**
     * Obtiene estadísticas generales para el dashboard
     * 
     * @return array Estadísticas generales
     */
    public function getEstadisticasGenerales()
    {
        return $this->modelo->getEstadisticasGenerales();
    }

    /**
     * Obtiene estadísticas específicas para el dashboard de administrador
     * 
     * @return array Estadísticas para administrador
     */
    public function getEstadisticasAdmin()
    {
        $stats = [];

        // Estadísticas de usuarios
        $stats['usuarios'] = $this->modelo->getEstadisticasUsuarios();

        // Estadísticas de servicios de baño
        $stats['servicios_bano'] = $this->modelo->getEstadisticasServiciosBano();

        // Estadísticas de equipajes
        $stats['equipajes'] = $this->modelo->getEstadisticasEquipajes();

        // Estadísticas de productos
        $stats['productos'] = $this->modelo->getEstadisticasProductos();

        // Estadísticas de baños (detalladas)
        $stats['banos'] = $this->modelo->getEstadisticasBanosDetalladas();

        // Estadísticas de clientes (personas)
        $stats['personas'] = $this->modelo->getEstadisticasClientesDetalladas();

        // Estadísticas de habitaciones
        $stats['habitaciones'] = $this->modelo->getEstadisticasHabitaciones();

        // Datos para gráficos de actividad
        $stats['graficos'] = $this->modelo->getDatosGraficos();

        // Obtener todas las habitaciones con su estado, ordenadas por piso y número
        $stats['lista_habitaciones'] = self::ordenarHabitacionesPorPisoYNumero(
            $this->modelo->getHabitacionesConEstado()
        );
        $stats['habitaciones_por_piso'] = self::agruparHabitacionesPorPiso($stats['lista_habitaciones']);

        // Reemplazamos el método individual por uno que trae todos los ingresos
        $stats['ingresos_totales'] = $this->modelo->getIngresosTotalesHoy();

        // Añadimos estadísticas de limpieza pendientes (para reemplazar el info-box de baños)
        $stats['limpieza_pendientes'] = $this->modelo->getEstadisticasLimpiezaPendientes();

        return $stats;
    }

    /**
     * Obtiene estadísticas específicas para el dashboard de recepcionista
     * 
     * @return array Estadísticas para recepcionista
     */
    public function getEstadisticasRecepcionista()
    {
        $stats = [];

        // Estadísticas de baños
        $stats['banos'] = $this->modelo->getEstadisticasBanos();

        // Estadísticas de servicios de baño
        $stats['servicios_bano'] = $this->modelo->getEstadisticasServiciosBano();

        // Estadísticas de equipajes
        $stats['equipajes'] = $this->modelo->getEstadisticasEquipajes();

        // Estadísticas de personas
        $stats['personas'] = $this->modelo->getEstadisticasPersonas();

        // Asignaciones de limpieza (todas, no solo de un usuario específico)
        $stats['asignaciones'] = $this->modelo->getTodasAsignacionesLimpieza();

        // Obtener todas las habitaciones con su estado
        $stats['habitaciones'] = $this->modelo->getHabitacionesConEstado();

        return $stats;
    }

    /**
     * Obtiene estadísticas específicas para el dashboard de personal de limpieza
     * 
     * @param int $idusuario ID del usuario actual
     * @return array Estadísticas para personal de limpieza
     */
    public function getEstadisticasLimpieza($idusuario)
    {
        $stats = [];

        // Estadísticas de baños
        $stats['banos'] = $this->modelo->getEstadisticasBanos();

        // Estadísticas de servicios de baño
        $stats['servicios_bano'] = $this->modelo->getEstadisticasServiciosBano();

        // Asignaciones de limpieza del usuario actual
        $stats['asignaciones'] = $this->modelo->getAsignacionesLimpiezaUsuario($idusuario);

        return $stats;
    }

    /**
     * Obtiene estadísticas básicas para cualquier rol
     * 
     * @return array Estadísticas básicas
     */
    public function getEstadisticasBasicas()
    {
        $stats = [];

        // Estadísticas de servicios de baño
        $stats['servicios_bano'] = $this->modelo->getEstadisticasServiciosBano();

        // Estadísticas de equipajes
        $stats['equipajes'] = $this->modelo->getEstadisticasEquipajes();

        // Estadísticas de productos
        $stats['productos'] = $this->modelo->getEstadisticasProductos();

        return $stats;
    }

    /**
     * Ordena una lista de habitaciones por piso y, dentro de cada piso, por número
     *
     * @param array $habitaciones Lista de habitaciones (con 'idpiso' y 'numero')
     * @return array Lista ordenada
     */
    public static function ordenarHabitacionesPorPisoYNumero($habitaciones)
    {
        usort($habitaciones, function ($a, $b) {
            if ($a['idpiso'] != $b['idpiso']) {
                return $a['idpiso'] - $b['idpiso'];
            }

            $numA = preg_replace('/[^0-9]/', '', $a['numero']);
            $numB = preg_replace('/[^0-9]/', '', $b['numero']);

            if (is_numeric($numA) && is_numeric($numB)) {
                return intval($numA) - intval($numB);
            }

            return strcmp($a['numero'], $b['numero']);
        });

        return $habitaciones;
    }

    /**
     * Agrupa una lista de habitaciones por piso
     *
     * @param array $habitaciones Lista de habitaciones (con 'idpiso' y 'piso_nombre')
     * @return array Habitaciones agrupadas por idpiso
     */
    public static function agruparHabitacionesPorPiso($habitaciones)
    {
        $habitaciones_por_piso = [];

        foreach ($habitaciones as $habitacion) {
            $piso_id = $habitacion['idpiso'];

            if (!isset($habitaciones_por_piso[$piso_id])) {
                $habitaciones_por_piso[$piso_id] = [
                    'nombre' => $habitacion['piso_nombre'],
                    'habitaciones' => []
                ];
            }

            $habitaciones_por_piso[$piso_id]['habitaciones'][] = $habitacion;
        }

        return $habitaciones_por_piso;
    }
}

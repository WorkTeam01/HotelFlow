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

        // Obtener todas las habitaciones con su estado
        $stats['lista_habitaciones'] = $this->modelo->getHabitacionesConEstado();

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
}

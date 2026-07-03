<?php

/**
 * Controlador de Habitaciones
 * 
 * Gestiona las operaciones relacionadas con las habitaciones
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class HabitacionController
{
    /**
     * Modelo de Habitacion
     * @var Habitacion
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Habitacion
        require_once __DIR__ . '/../../models/Habitacion.php';
        $this->modelo = new Habitacion();
    }

    /**
     * Muestra la lista de habitaciones
     * 
     * @param string $orden Campo por el que ordenar
     * @param string $direccion Dirección de ordenamiento (ASC, DESC)
     * @return array Lista de habitaciones
     */
    public function index($orden = 'numero', $direccion = 'ASC')
    {
        return $this->modelo->getAll($orden, $direccion);
    }

    /**
     * Obtiene habitaciones filtradas por estado
     * 
     * @param string $estado Estado por el que filtrar
     * @return array Lista de habitaciones filtradas
     */
    public function getByEstado($estado)
    {
        return $this->modelo->getByEstado($estado);
    }

    /**
     * Obtiene una habitación por su ID
     * 
     * @param int $id ID de la habitación
     * @return array|bool Datos de la habitación o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Prepara los datos del formulario de habitación
     * 
     * @return array Datos para el formulario
     */
    public function crear()
    {
        // Obtener tipos de habitación y pisos para el formulario
        $tipos_habitacion = $this->modelo->getTiposHabitacion();
        $pisos = $this->modelo->getPisos();

        return [
            'tipos_habitacion' => $tipos_habitacion,
            'pisos' => $pisos
        ];
    }

    /**
     * Prepara los datos para el formulario de edición
     * 
     * @param int $id ID de la habitación a editar
     * @return array|bool Datos para el formulario o false si no existe
     */
    public function editar($id)
    {
        // Obtener la habitación por su ID
        $habitacion = $this->modelo->getById($id);

        if (!$habitacion) {
            return false;
        }

        // Obtener tipos de habitación y pisos para el formulario
        $tipos_habitacion = $this->modelo->getTiposHabitacion();
        $pisos = $this->modelo->getPisos();

        return [
            'habitacion' => $habitacion,
            'tipos_habitacion' => $tipos_habitacion,
            'pisos' => $pisos
        ];
    }

    /**
     * Procesa el formulario para guardar una nueva habitación
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la habitación
        $datos = $this->prepararDatos($_POST);

        // Sanitizar datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Verificar si ya existe una habitación con el mismo número
        if ($this->modelo->existeNumero($datos['numero'])) {
            return ['success' => false, 'message' => 'Ya existe una habitación con este número', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Crear habitación
        if ($this->modelo->crear($datos)) {
            $id = $this->modelo->getLastInsertId();
            return [
                'success' => true,
                'message' => 'Habitación creada correctamente',
                'icon' => 'success',
                'redirect' => 'index.php',
                'id' => $id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la habitación: ' . $this->modelo->getLastError(),
                'icon' => 'error',
                'redirect' => 'create.php'
            ];
        }
    }

    /**
     * Procesa el formulario para actualizar una habitación existente
     * 
     * @return array Resultado de la operación
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID de la habitación
        $id = isset($_POST['id_habitacion']) ? (int)$_POST['id_habitacion'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de habitación no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales de la habitación
        $habitacion_actual = $this->modelo->getById($id);
        if (!$habitacion_actual) {
            return ['success' => false, 'message' => 'Habitación no encontrada para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos de la habitación
        $datos = $this->prepararDatos($_POST);

        // Sanitizar datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Verificar si ya existe otra habitación con el mismo número (excluyendo la habitación actual)
        if ($this->modelo->existeNumero($datos['numero'], $id)) {
            return ['success' => false, 'message' => 'Ya existe otra habitación con este número', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar habitación
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Habitación actualizada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar la habitación: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de una habitación
     * 
     * @param int $id ID de la habitación
     * @param string $estado Nuevo estado
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id = null, $estado = null)
    {
        if ($id === null || $estado === null) {
            return ['success' => false, 'message' => 'Datos no válidos para cambiar el estado de la habitación', 'icon' => 'error'];
        }

        // Validar estado
        $estados_validos = ['disponible', 'ocupada', 'mantenimiento', 'limpieza'];
        if (!in_array($estado, $estados_validos)) {
            return ['success' => false, 'message' => 'Estado no válido', 'icon' => 'error'];
        }

        // Verificar que la habitación existe
        $habitacion = $this->modelo->getById($id);
        if (!$habitacion) {
            return ['success' => false, 'message' => 'Habitación no encontrada', 'icon' => 'error'];
        }

        if ($this->modelo->cambiarEstado($id, $estado)) {
            return [
                'success' => true,
                'message' => "Estado de habitación actualizado correctamente",
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la habitación: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Muestra los detalles de una habitación
     * 
     * @param int $id ID de la habitación
     * @return array|false Detalles de la habitación o false si no existe
     */
    public function mostrar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            return false;
        }

        // Obtener datos de la habitación
        $habitacion = $this->modelo->getById($id);

        if (!$habitacion) {
            return false;
        }

        return $habitacion;
    }

    /**
     * Prepara los datos de la habitación desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatos($post_data)
    {
        $datos = [
            'numero' => isset($post_data['numero']) ? trim($post_data['numero']) : '',
            'id_tipo' => isset($post_data['id_tipo']) ? (int)$post_data['id_tipo'] : 0,
            'idpiso' => isset($post_data['idpiso']) ? (int)$post_data['idpiso'] : 0,
            'capacidad_actual' => isset($post_data['capacidad_actual']) ? (int)$post_data['capacidad_actual'] : 0,
            'precio_base' => isset($post_data['precio_base']) ? floatval($post_data['precio_base']) : 0,
            'estado' => isset($post_data['estado']) ? trim($post_data['estado']) : 'disponible'
        ];

        return $datos;
    }

    /**
     * Obtiene todos los tipos de habitación
     * 
     * @return array Lista de tipos de habitación
     */
    public function getTiposHabitacion()
    {
        return $this->modelo->getTiposHabitacion();
    }

    /**
     * Obtiene todos los pisos
     * 
     * @return array Lista de pisos
     */
    public function getPisos()
    {
        return $this->modelo->getPisos();
    }

    /**
     * Obtiene estadísticas de habitaciones
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
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
        return $this->modelo->getHistorialOcupacion($id, $limite);
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
        return $this->modelo->getHistorialLimpieza($id, $limite);
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
        return $this->modelo->filtrar($estado, $tipo_id, $piso_id, $orden, $direccion);
    }
}

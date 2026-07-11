<?php
/**
 * Controlador de Tipos de Habitación
 * 
 * Gestiona las operaciones relacionadas con los tipos de habitación
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir el servicio de imágenes si es necesario
require_once __DIR__ . '/../../services/ImagenService.php';

class TipoHabitacionController
{
    /**
     * Modelo de TipoHabitacion
     * @var TipoHabitacion
     */
    private $modelo;

    /**
     * Servicio de imágenes (opcional)
     * @var ImagenService
     */
    private $imagenService;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de TipoHabitacion
        require_once __DIR__ . '/../../models/TipoHabitacion.php';
        $this->modelo = new TipoHabitacion();
    }

    /**
     * Muestra la lista de tipos de habitación
     * 
     * @param bool $soloActivos Si es true, solo muestra tipos activos
     * @return array Lista de tipos de habitación
     */
    public function index($soloActivos = false)
    {
        return $this->modelo->getAll($soloActivos);
    }

    /**
     * Muestra el formulario para crear un nuevo tipo de habitación
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/tipohabitacion/create.php';
    }

    /**
     * Prepara los datos del tipo de habitación desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosTipoHabitacion($post_data)
    {
        $datos = [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'descripcion' => isset($post_data['descripcion']) && !empty($post_data['descripcion']) ? trim($post_data['descripcion']) : null,
            'capacidad_maxima' => isset($post_data['capacidad_maxima']) ? (int)$post_data['capacidad_maxima'] : 1,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1,
            // 'imagen' => null // Se puede habilitar si se usan imágenes
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar un nuevo tipo de habitación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del tipo de habitación
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosTipoHabitacion($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

      
        // Guardar tipo de habitación usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Tipo de habitación creado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al crear el tipo de habitación: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar un tipo de habitación
     * 
     * @param int $id ID del tipo de habitación
     * @return array|null Datos del tipo de habitación o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de tipo de habitación no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/tipohabitacion');
            exit;
        }

        // Obtener datos del tipo de habitación
        $tipoHabitacion = $this->modelo->getById($id);

        if (!$tipoHabitacion) {
            global $URL;
            $_SESSION['mensaje'] = 'Tipo de habitación no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/tipohabitacion');
            exit;
        }

        // Devolver los datos del tipo de habitación
        return $tipoHabitacion;
    }

    /**
     * Procesa el formulario para actualizar un tipo de habitación
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del tipo de habitación
        $id = isset($_POST['id_tipo']) ? (int)$_POST['id_tipo'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de tipo de habitación no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del tipo de habitación
        $tipo_actual = $this->modelo->getById($id);
        if (!$tipo_actual) {
            return ['success' => false, 'message' => 'Tipo de habitación no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos del tipo de habitación
        $datos = $this->prepararDatosTipoHabitacion($_POST);

        // Asegurarse de que los campos obligatorios estén presentes
        if (empty($datos['nombre']) && isset($tipo_actual['nombre'])) {
            $datos['nombre'] = $tipo_actual['nombre'];
        }
        if (empty($datos['capacidad_maxima']) && isset($tipo_actual['capacidad_maxima'])) {
            $datos['capacidad_maxima'] = $tipo_actual['capacidad_maxima'];
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);


        // Validar datos en el modelo (excluyendo el tipo actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

       

        // Actualizar tipo de habitación
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Tipo de habitación actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el tipo de habitación: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un tipo de habitación (activa/desactiva)
     * 
     * @param int $id ID del tipo de habitación
     * @param int $estado_actual Estado actual del tipo (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id = null)
    {
        if ($id === null) {
            return ['success' => false, 'message' => 'ID de tipo de habitación no válido', 'icon' => 'error'];
        }

        // Calcular el nuevo estado a partir del estado real en la base de datos,
        // no del valor enviado por el cliente, que puede ser manipulado
        $tipo = $this->modelo->getById($id);
        if (!$tipo) {
            return ['success' => false, 'message' => 'Tipo de habitación no encontrado', 'icon' => 'error'];
        }

        $nuevo_estado = $tipo['estado'] == 1 ? 0 : 1; // Cambia el estado

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return ['success' => true, 'message' => "Tipo de habitación $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado del tipo de habitación: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene los tipos de habitación para usar en selects
     * 
     * @param bool $soloActivos Si es true, solo devuelve tipos activos
     * @return array Lista de tipos de habitación en formato id => nombre
     */
    public function getParaSelect($soloActivos = true)
    {
        return $this->modelo->getParaSelect($soloActivos);
    }
}
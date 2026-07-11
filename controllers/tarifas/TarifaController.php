<?php
/**
 * Controlador de Tarifas
 * 
 * Gestiona las operaciones relacionadas con las tarifas de alojamiento
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

class TarifaController
{
    /**
     * Modelo de Tarifa
     * @var Tarifa
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Tarifa
        require_once __DIR__ . '/../../models/Tarifas.php';
        $this->modelo = new Tarifa();
    }

    /**
     * Muestra la lista de tarifas
     */
    public function index()
    {
        // Obtener todas las tarifas
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear una nueva tarifa
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/tarifas/create.php';
    }

    /**
     * Prepara los datos de la tarifa desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosTarifa($post_data)
    {
        $datos = [
            'id_tipo' => isset($post_data['id_tipo']) ? (int)$post_data['id_tipo'] : 0,
            'tipo_estancia' => isset($post_data['tipo_estancia']) ? trim($post_data['tipo_estancia']) : 'horas',
            'duracion' => isset($post_data['duracion']) ? (int)$post_data['duracion'] : 1,
            'precio' => isset($post_data['precio']) ? (float)$post_data['precio'] : 0,
            'descripcion' => isset($post_data['descripcion']) && !empty($post_data['descripcion']) ? trim($post_data['descripcion']) : null,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva tarifa
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la tarifa
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosTarifa($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Verificar si ya existe una tarifa con los mismos parámetros
        if ($this->modelo->existeTarifa($datos['id_tipo'], $datos['tipo_estancia'], $datos['duracion'])) {
            return ['success' => false, 'message' => 'Ya existe una tarifa con estos parámetros (tipo de habitación, tipo de estancia y duración)', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar tarifa usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Tarifa creada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al crear la tarifa: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar una tarifa
     * 
     * @param int $id ID de la tarifa
     * @return array|null Datos de la tarifa o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de tarifa no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/tarifas');
            exit;
        }

        // Obtener datos de la tarifa
        $tarifa = $this->modelo->getById($id);

        if (!$tarifa) {
            global $URL;
            $_SESSION['mensaje'] = 'Tarifa no encontrada';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/tarifas');
            exit;
        }

        // Devolver los datos de la tarifa
        return $tarifa;
    }

    /**
     * Procesa el formulario para actualizar una tarifa
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID de la tarifa
        $id = isset($_POST['idtarifa']) ? (int)$_POST['idtarifa'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de tarifa no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales de la tarifa
        $tarifa_actual = $this->modelo->getById($id);
        if (!$tarifa_actual) {
            return ['success' => false, 'message' => 'Tarifa no encontrada para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos de la tarifa
        $datos = $this->prepararDatosTarifa($_POST);

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['id_tipo', 'tipo_estancia', 'duracion', 'precio'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) ){
                $datos[$campo] = $tarifa_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo la tarifa actual)
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Verificar si ya existe otra tarifa con los mismos parámetros
        if ($this->modelo->existeTarifa($datos['id_tipo'], $datos['tipo_estancia'], $datos['duracion'], $id)) {
            return ['success' => false, 'message' => 'Ya existe otra tarifa con estos parámetros (tipo de habitación, tipo de estancia y duración)', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar tarifa
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Tarifa actualizada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar la tarifa: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de una tarifa (activa/desactiva)
     * 
     * @param int $id ID de la tarifa
     * @param int $estado_actual Estado actual de la tarifa (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstadoTarifa($id = null)
    {
        if ($id === null) {
            return ['success' => false, 'message' => 'ID de tarifa no válido', 'icon' => 'error'];
        }

        // Calcular el nuevo estado a partir del estado real en la base de datos,
        // no del valor enviado por el cliente, que puede ser manipulado
        $tarifa = $this->modelo->getById($id);
        if (!$tarifa) {
            return ['success' => false, 'message' => 'Tarifa no encontrada', 'icon' => 'error'];
        }

        $nuevo_estado = $tarifa['estado'] == 1 ? 0 : 1; // Cambia el estado

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activada' : 'desactivada';
            return ['success' => true, 'message' => "Tarifa $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado de la tarifa: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene tarifas por tipo de habitación (para menús o filtros)
     * 
     * @param int $id_tipo ID del tipo de habitación
     * @return array Lista de tarifas para el tipo de habitación
     */
    public function obtenerPorTipoHabitacion($id_tipo)
    {
        return $this->modelo->getByTipoHabitacion($id_tipo);
    }

    /**
     * Obtiene tarifas por tipo de estancia (horas/días)
     * 
     * @param int $id_tipo ID del tipo de habitación
     * @param string $tipo_estancia 'horas' o 'dias'
     * @return array Lista de tarifas para el tipo de estancia
     */
    public function obtenerPorTipoEstancia($id_tipo, $tipo_estancia)
    {
        return $this->modelo->getByTipoEstancia($id_tipo, $tipo_estancia);
    }
}
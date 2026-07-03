<?php

/**
 * Controlador de Baños
 * 
 * Gestiona las operaciones relacionadas con los baños
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class BanoController
{
    /**
     * Modelo de Baño
     * @var Bano
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Baño
        require_once __DIR__ . '/../../models/Bano.php';
        $this->modelo = new Bano();
    }

    /**
     * Muestra la lista de baños
     */
    public function index()
    {
        // Obtener todos los baños
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear un nuevo baño
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/banos/create.php';
    }

    /**
     * Prepara los datos del baño desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosBano($post_data)
    {
        $datos = [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'ubicacion' => isset($post_data['ubicacion']) ? trim($post_data['ubicacion']) : '',
            'precio' => isset($post_data['precio']) ? (float)$post_data['precio'] : 0.00,
            'estado' => isset($post_data['estado']) ? trim($post_data['estado']) : 'disponible'
        ];

        return $datos;
    }

    /**
     * Crea un baño vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Preparar datos del baño
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosBano($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Guardar baño usando el modelo
        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Baño creado correctamente',
                'bano' => [
                    'idbano' => $this->modelo->getLastInsertId(),
                    'nombre' => $datos['nombre'],
                    'ubicacion' => $datos['ubicacion'],
                    'precio' => $datos['precio'],
                    'estado' => $datos['estado']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear el baño: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Muestra el formulario para editar un baño
     * 
     * @param int $id ID del baño
     * @return array|null Datos del baño o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de baño no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/banos');
            exit;
        }

        // Obtener datos del baño
        $bano = $this->modelo->getById($id);

        if (!$bano) {
            global $URL;
            $_SESSION['mensaje'] = 'Baño no encontrado';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/banos');
            exit;
        }

        // Devolver los datos del baño
        return $bano;
    }

    /**
     * Actualiza un baño vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Obtener ID del baño
        $id = isset($_POST['idbano']) ? (int)$_POST['idbano'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de baño no válido'];
        }

        // Obtener datos actuales del baño
        $bano_actual = $this->modelo->getById($id);
        if (!$bano_actual) {
            return ['success' => false, 'message' => 'Baño no encontrado para actualizar'];
        }

        // Preparar datos del baño
        $datos = $this->prepararDatosBano($_POST);

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo el baño actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Actualizar baño
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Baño actualizado correctamente',
                'bano' => [
                    'idbano' => $id,
                    'nombre' => $datos['nombre'],
                    'ubicacion' => $datos['ubicacion'],
                    'precio' => $datos['precio'],
                    'estado' => $datos['estado']
                ]
            ];
        } else {
            $error_message = 'Error al actualizar el baño: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Cambia el estado de un baño
     * 
     * @param int $id ID del baño
     * @param string $nuevo_estado Nuevo estado del baño
     * @return array Resultado de la operación
     */
    public function cambiarEstadoBano($id = null, $nuevo_estado = null)
    {
        if ($id === null || $nuevo_estado === null) {
            return ['success' => false, 'message' => 'ID de baño o estado no válido', 'icon' => 'error'];
        }

        $estados = [
            'disponible' => 'disponible',
            'mantenimiento' => 'en mantenimiento',
            'fuera_servicio' => 'fuera de servicio'
        ];

        $resultado = false;

        switch ($nuevo_estado) {
            case 'disponible':
                $resultado = $this->modelo->marcarDisponible($id);
                break;
            case 'mantenimiento':
                $resultado = $this->modelo->marcarMantenimiento($id);
                break;
            case 'fuera_servicio':
                $resultado = $this->modelo->marcarFueraServicio($id);
                break;
        }

        if ($resultado) {
            return [
                'success' => true,
                'message' => "Baño marcado como " . $estados[$nuevo_estado] . " correctamente",
                'icon' => 'success'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del baño: ' . $this->modelo->getLastError(),
                'icon' => 'error'
            ];
        }
    }

    /**
     * Cambia el estado de un baño vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function cambiarEstadoBanoAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';

        if (!$id || !in_array($nuevo_estado, ['disponible', 'mantenimiento', 'fuera_servicio'])) {
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado del baño'];
        }

        $resultado = $this->cambiarEstadoBano($id, $nuevo_estado);
        return $resultado;
    }

    /**
     * Obtiene estadísticas de los baños
     * 
     * @return array Estadísticas de los baños
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}

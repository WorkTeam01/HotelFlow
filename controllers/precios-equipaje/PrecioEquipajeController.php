<?php

/**
 * Controlador de Precios de Equipaje
 * 
 * Gestiona las operaciones relacionadas con los precios de equipaje
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class PrecioEquipajeController
{
    /**
     * Modelo de PrecioEquipaje
     * @var PrecioEquipaje
     */
    public $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de PrecioEquipaje
        require_once __DIR__ . '/../../models/PrecioEquipaje.php';
        $this->modelo = new PrecioEquipaje();
    }

    /**
     * Muestra la lista de precios de equipaje
     * 
     * @return array Lista de precios de equipaje
     */
    public function index()
    {
        // Obtener todos los precios de equipaje
        return $this->modelo->getAll();
    }

    /**
     * Prepara los datos del precio de equipaje desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatos($post_data)
    {
        $datos = [
            'tamano' => isset($post_data['tamano']) ? trim($post_data['tamano']) : '',
            'descripcion' => isset($post_data['descripcion']) ? trim($post_data['descripcion']) : null, // Cambiado de '' a null
            'precio' => isset($post_data['precio']) ? (float)$post_data['precio'] : 0,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];

        return $datos;
    }

    /**
     * Crea un precio de equipaje vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Preparar datos del precio de equipaje
        $datos = $this->modelo->sanitizarDatos($this->prepararDatos($_POST));

        // Si descripción está vacía, establecerla como NULL
        if (empty($datos['descripcion'])) {
            $datos['descripcion'] = null;
        }

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Guardar precio de equipaje usando el modelo
        if ($this->modelo->crear($datos)) {
            return [
                'success' => true,
                'message' => 'Precio de equipaje creado correctamente',
                'precioEquipaje' => [
                    'idprecioe' => $this->modelo->getLastInsertId(),
                    'tamano' => $datos['tamano'],
                    'descripcion' => $datos['descripcion'],
                    'precio' => $datos['precio'],
                    'estado' => $datos['estado']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Error al crear el precio de equipaje: ' . $this->modelo->getLastError()];
        }
    }

    /**
     * Obtiene un precio de equipaje por su ID
     * 
     * @param int $id ID del precio de equipaje
     * @return array|bool Datos del precio de equipaje o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Actualiza un precio de equipaje vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        // Obtener ID del precio de equipaje
        $id = isset($_POST['idprecioe']) ? (int)$_POST['idprecioe'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de precio de equipaje no válido'];
        }

        // Obtener datos actuales del precio de equipaje
        $precio_actual = $this->modelo->getById($id);
        if (!$precio_actual) {
            return ['success' => false, 'message' => 'Precio de equipaje no encontrado para actualizar'];
        }

        // Preparar datos del precio de equipaje
        $datos = $this->prepararDatos($_POST);

        // Si descripción está vacía y no es NULL en la base de datos, mantener el valor original
        if (empty($datos['descripcion']) && $precio_actual['descripcion'] !== null) {
            $datos['descripcion'] = $precio_actual['descripcion'];
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0]];
        }

        // Actualizar precio de equipaje
        if ($this->modelo->actualizar($id, $datos)) {
            return [
                'success' => true,
                'message' => 'Precio de equipaje actualizado correctamente',
                'precioEquipaje' => [
                    'idprecioe' => $id,
                    'tamano' => $datos['tamano'],
                    'descripcion' => $datos['descripcion'],
                    'precio' => $datos['precio'],
                    'estado' => $datos['estado']
                ]
            ];
        } else {
            $error_message = 'Error al actualizar el precio de equipaje: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Cambia el estado de un precio de equipaje vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function cambiarEstadoAjax()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido'];
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $estado_actual = isset($_POST['estado_actual']) ? (int)$_POST['estado_actual'] : null;

        if (!$id || $estado_actual === null) {
            return ['success' => false, 'message' => 'Datos inválidos para cambiar el estado del precio de equipaje'];
        }

        // El nuevo estado es el opuesto al actual
        $nuevo_estado = $estado_actual == 1 ? 0 : 1;

        if ($this->modelo->cambiarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return [
                'success' => true,
                'message' => "Precio de equipaje $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del precio de equipaje: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene estadísticas de precios de equipaje
     * 
     * @return array Estadísticas de precios de equipaje
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}

<?php

/**
 * Controlador de Pisos
 * 
 * Gestiona las operaciones relacionadas con los pisos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class PisoController
{
    /**
     * Modelo de Piso
     * @var Piso
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Piso
        require_once __DIR__ . '/../../models/Piso.php';
        $this->modelo = new Piso();
    }

    /**
     * Muestra la lista de pisos
     * 
     * @return array Lista de pisos
     */
    public function index()
    {
        return $this->modelo->getAll();
    }

    /**
     * Obtiene un piso por su ID
     * 
     * @param int $id ID del piso
     * @return array|bool Datos del piso o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Crea un piso vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function crearAjax()
    {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Método no permitido'
            ];
        }

        // Preparar datos
        $datos = [
            'nombre' => isset($_POST['nombre']) ? trim($_POST['nombre']) : '',
            'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null,
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1
        ];

        // Sanitizar datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos
        $errores = $this->modelo->validarDatos($datos);
        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => $errores[0]
            ];
        }

        // Verificar si ya existe un piso con el mismo nombre
        if ($this->modelo->existeNombre($datos['nombre'])) {
            return [
                'success' => false,
                'message' => 'Ya existe un piso con este nombre'
            ];
        }

        // Crear piso
        if ($this->modelo->crear($datos)) {
            $id = $this->modelo->getLastInsertId();
            $piso = $this->modelo->getById($id);
            return [
                'success' => true,
                'message' => 'Piso creado correctamente',
                'piso' => $piso
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear el piso: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Actualiza un piso vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function actualizarAjax()
    {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Método no permitido'
            ];
        }

        // Obtener ID del piso
        $id = isset($_POST['idpiso']) ? (int)$_POST['idpiso'] : 0;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID de piso no válido'
            ];
        }

        // Verificar que el piso existe
        $piso = $this->modelo->getById($id);
        if (!$piso) {
            return [
                'success' => false,
                'message' => 'El piso no existe'
            ];
        }

        // Preparar datos
        $datos = [
            'nombre' => isset($_POST['nombre']) ? trim($_POST['nombre']) : $piso['nombre'],
            'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : $piso['descripcion'],
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : $piso['estado']
        ];

        // Sanitizar datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos
        $errores = $this->modelo->validarDatos($datos);
        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => $errores[0]
            ];
        }

        // Verificar si ya existe un piso con el mismo nombre (excluyendo el piso actual)
        if ($this->modelo->existeNombre($datos['nombre'], $id)) {
            return [
                'success' => false,
                'message' => 'Ya existe otro piso con este nombre'
            ];
        }

        // Actualizar piso
        if ($this->modelo->actualizar($id, $datos)) {
            $piso = $this->modelo->getById($id);
            return [
                'success' => true,
                'message' => 'Piso actualizado correctamente',
                'piso' => $piso
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar el piso: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Cambia el estado de un piso vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function cambiarEstadoAjax()
    {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Método no permitido'
            ];
        }

        // Obtener datos
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if (!$id) {
            return [
                'success' => false,
                'message' => 'Datos inválidos para cambiar el estado del piso'
            ];
        }

        // Calcular el nuevo estado a partir del estado real en la base de datos,
        // no del valor enviado por el cliente, que puede ser manipulado
        $piso = $this->modelo->getById($id);
        if (!$piso) {
            return [
                'success' => false,
                'message' => 'Piso no encontrado'
            ];
        }

        $nuevo_estado = $piso['estado'] == 1 ? 0 : 1;

        if ($this->modelo->cambiarEstado($id, $nuevo_estado)) {
            $mensaje = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return [
                'success' => true,
                'message' => "Piso $mensaje correctamente",
                'nuevo_estado' => $nuevo_estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del piso: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene estadísticas de pisos
     * 
     * @return array Estadísticas de pisos
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}

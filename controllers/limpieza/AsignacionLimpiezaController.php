<?php

/**
 * Controlador de Asignaciones de Limpieza
 * 
 * Gestiona las operaciones relacionadas con las asignaciones de limpieza
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class AsignacionLimpiezaController
{
    /**
     * Modelo de AsignacionLimpieza
     * @var AsignacionLimpieza
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de AsignacionLimpieza
        require_once __DIR__ . '/../../models/AsignacionLimpieza.php';
        $this->modelo = new AsignacionLimpieza();
    }

    /**
     * Muestra la lista de asignaciones de limpieza
     * 
     * @return array Lista de asignaciones de limpieza
     */
    public function index()
    {
        return $this->modelo->getAll();
    }

    /**
     * Obtiene una asignación de limpieza por su ID
     * 
     * @param int $id ID de la asignación de limpieza
     * @return array|bool Datos de la asignación de limpieza o false si no existe
     */
    public function getById($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Obtiene todas las habitaciones disponibles para asignaciones
     * 
     * @param int|null $id_asignacion ID de asignación actual (para edición)
     * @return array Lista de habitaciones
     */
    public function getHabitaciones($id_asignacion = null)
    {
        // Convertir a entero si no es null
        if ($id_asignacion !== null) {
            $id_asignacion = (int)$id_asignacion;

            // Verificar que exista la asignación
            $asignacion = $this->modelo->getById($id_asignacion);
            if (!$asignacion) {
                return [];
            }
        }

        // Obtener las habitaciones
        return $this->modelo->getHabitaciones($id_asignacion);
    }

    /**
     * Obtiene las habitaciones para el formulario de edición
     * 
     * @param int $id_asignacion ID de la asignación a editar
     * @return array Lista de habitaciones
     */
    public function getHabitacionesParaEdicion($id_asignacion)
    {
        return $this->modelo->getHabitaciones($id_asignacion);
    }

    /**
     * Obtiene todos los usuarios de limpieza
     * 
     * @return array Lista de usuarios de limpieza
     */
    public function getUsuariosLimpieza()
    {
        return $this->modelo->getUsuariosLimpieza();
    }

    /**
     * Crea una asignación de limpieza vía AJAX
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
            'idusuario' => isset($_POST['idusuario']) ? (int)$_POST['idusuario'] : 0,
            'idhabitacion' => isset($_POST['idhabitacion']) ? (int)$_POST['idhabitacion'] : 0,
            'fecha' => isset($_POST['fecha']) ? trim($_POST['fecha']) : '',
            'hora' => isset($_POST['hora']) ? trim($_POST['hora']) : '',
            'estado' => isset($_POST['estado']) ? trim($_POST['estado']) : 'pendiente',
            'observaciones' => isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null
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

        // Crear asignación
        if ($this->modelo->crear($datos)) {
            $id = $this->modelo->getLastInsertId();
            $asignacion = $this->modelo->getById($id);
            return [
                'success' => true,
                'message' => 'Asignación de limpieza creada correctamente',
                'asignacion' => $asignacion
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la asignación de limpieza: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Actualiza una asignación de limpieza vía AJAX
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

        // Obtener ID de la asignación
        $id = isset($_POST['idasignacion']) ? (int)$_POST['idasignacion'] : 0;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID de asignación no válido'
            ];
        }

        // Verificar que la asignación existe
        $asignacion = $this->modelo->getById($id);
        if (!$asignacion) {
            return [
                'success' => false,
                'message' => 'La asignación de limpieza no existe'
            ];
        }

        // Preparar datos
        $datos = [
            'idusuario' => isset($_POST['idusuario']) ? (int)$_POST['idusuario'] : $asignacion['idusuario'],
            'idhabitacion' => isset($_POST['idhabitacion']) ? (int)$_POST['idhabitacion'] : $asignacion['idhabitacion'],
            'fecha' => isset($_POST['fecha']) ? trim($_POST['fecha']) : $asignacion['fecha'],
            'hora' => isset($_POST['hora']) ? trim($_POST['hora']) : $asignacion['hora'],
            'estado' => isset($_POST['estado']) ? trim($_POST['estado']) : $asignacion['estado'],
            'observaciones' => isset($_POST['observaciones']) ? trim($_POST['observaciones']) : $asignacion['observaciones']
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

        // Actualizar asignación
        if ($this->modelo->actualizar($id, $datos)) {
            $asignacion = $this->modelo->getById($id);
            return [
                'success' => true,
                'message' => 'Asignación de limpieza actualizada correctamente',
                'asignacion' => $asignacion
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la asignación de limpieza: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Cambia el estado de una asignación de limpieza vía AJAX
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
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

        if (!$id || !$estado) {
            return [
                'success' => false,
                'message' => 'Datos incompletos'
            ];
        }

        // Validar estado
        if (!in_array($estado, ['pendiente', 'enprogreso', 'completada', 'verificada'])) {
            return [
                'success' => false,
                'message' => 'Estado no válido'
            ];
        }

        // Actualizar estado
        if ($this->modelo->actualizarEstado($id, $estado)) {
            // Texto para mensaje
            $mensajes = [
                'pendiente' => 'pendiente',
                'enprogreso' => 'en progreso',
                'completada' => 'completada',
                'verificada' => 'verificada'
            ];

            return [
                'success' => true,
                'message' => 'Asignación de limpieza marcada como ' . $mensajes[$estado],
                'nuevo_estado' => $estado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la asignación: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Elimina una asignación de limpieza vía AJAX
     * 
     * @return array Respuesta JSON con el resultado de la operación
     */
    public function eliminarAjax()
    {
        // Verificar método de solicitud
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Método no permitido'
            ];
        }

        // Obtener ID
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$id) {
            return [
                'success' => false,
                'message' => 'ID de asignación no válido'
            ];
        }

        // Eliminar asignación
        if ($this->modelo->eliminar($id)) {
            return [
                'success' => true,
                'message' => 'Asignación de limpieza eliminada correctamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar la asignación de limpieza: ' . $this->modelo->getLastError()
            ];
        }
    }

    /**
     * Obtiene estadísticas de asignaciones de limpieza
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas()
    {
        return $this->modelo->getEstadisticas();
    }
}

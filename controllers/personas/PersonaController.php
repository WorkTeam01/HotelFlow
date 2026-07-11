<?php
/**
 * Controlador de Personas
 * 
 * Gestiona las operaciones relacionadas con las personas (clientes)
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';

class PersonaController
{
    /**
     * Modelo de Persona
     * @var Persona
     */
    private $modelo;

    /**
     * Servicio de imágenes
     * @var ImagenService
     */
    private $imagenService;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Persona
        require_once __DIR__ . '/../../models/Persona.php';
        $this->modelo = new Persona();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/personas/');
    }

    /**
     * Muestra la lista de personas
     */
    public function index()
    {
        // Obtener todas las personas
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear una nueva persona
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/personas/create.php';
    }

    /**
     * Prepara los datos de la persona desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosPersona($post_data)
    {
        $datos = [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'apellidopaterno' => isset($post_data['apellidopaterno']) ? trim($post_data['apellidopaterno']) : '',
            'apellidomaterno' => isset($post_data['apellidomaterno']) ? trim($post_data['apellidomaterno']) : null,
            'tipodocumento' => isset($post_data['tipodocumento']) ? trim($post_data['tipodocumento']) : '',
            'numdocumento' => isset($post_data['numdocumento']) ? trim($post_data['numdocumento']) : '',
            'direccion' => isset($post_data['direccion']) && !empty($post_data['direccion']) ? trim($post_data['direccion']) : null,
            'telefono' => isset($post_data['telefono']) && !empty($post_data['telefono']) ? trim($post_data['telefono']) : null,
            'email' => isset($post_data['email']) && !empty($post_data['email']) ? trim($post_data['email']) : null,
            'fechanacimiento' => isset($post_data['fechanacimiento']) && !empty($post_data['fechanacimiento']) ? trim($post_data['fechanacimiento']) : null,
            'genero' => isset($post_data['genero']) && !empty($post_data['genero']) ? trim($post_data['genero']) : null,
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1
        ];

        return $datos;
    }

    /**
     * Procesa el formulario para guardar una nueva persona
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la persona
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosPersona($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar persona usando el modelo
        if ($this->modelo->crear($datos)) {
            return ['success' => true, 'message' => 'Persona registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar la persona: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar una persona
     * 
     * @param int $id ID de la persona
     * @return array|null Datos de la persona o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de persona no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/personas');
            exit;
        }

        // Obtener datos de la persona
        $persona = $this->modelo->getById($id);

        if (!$persona) {
            global $URL;
            $_SESSION['mensaje'] = 'Persona no encontrada';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/personas');
            exit;
        }

        // Devolver los datos de la persona
        return $persona;
    }

    /**
     * Procesa el formulario para actualizar una persona
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID de la persona
        $id = isset($_POST['idpersona']) ? (int)$_POST['idpersona'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de persona no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales de la persona
        $persona_actual = $this->modelo->getById($id);
        if (!$persona_actual) {
            return ['success' => false, 'message' => 'Persona no encontrada para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Preparar datos de la persona
        $datos = $this->prepararDatosPersona($_POST);

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['nombre', 'apellidopaterno', 'tipodocumento', 'numdocumento'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) && isset($persona_actual[$campo])) {
                $datos[$campo] = $persona_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Validar datos en el modelo (excluyendo la persona actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Actualizar persona
        if ($this->modelo->actualizar($id, $datos)) {
            return ['success' => true, 'message' => 'Persona actualizada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar la persona: ' . $this->modelo->getLastError();
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de una persona (activa/desactiva)
     *
     * @param int $id ID de la persona
     * @return array Resultado de la operación
     */
    public function cambiarEstado($id = null)
    {
        if ($id === null) {
            return ['success' => false, 'message' => 'ID de persona no válido', 'icon' => 'error'];
        }

        // Calcular el nuevo estado a partir del estado real en la base de datos,
        // no del valor enviado por el cliente, que puede ser manipulado
        $persona = $this->modelo->getById($id);
        if (!$persona) {
            return ['success' => false, 'message' => 'Persona no encontrada', 'icon' => 'error'];
        }

        $nuevo_estado = $persona['estado'] == 1 ? 0 : 1; // Cambia el estado

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activada' : 'desactivada';
            return ['success' => true, 'message' => "Persona $accion correctamente", 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado de la persona: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Busca personas según criterios
     * 
     * @param array $criterios Criterios de búsqueda
     * @return array Resultados de la búsqueda
     */
    public function buscar($criterios)
    {
        return $this->modelo->buscar($criterios);
    }
}
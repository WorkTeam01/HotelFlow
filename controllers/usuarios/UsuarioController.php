<?php

/**
 * Controlador de Usuarios
 * 
 * Gestiona las operaciones relacionadas con los usuarios
 * 
 */

// Incluir el servicio de imágenes
require_once __DIR__ . '/../../services/ImagenService.php';

class UsuarioController
{
    private const CARGOS_VALIDOS = ['Administrador', 'Recepcionista', 'Limpieza'];

    /**
     * Modelo de Usuario
     * @var Usuario
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
        // Incluir el modelo de Usuario
        require_once __DIR__ . '/../../models/Usuario.php';
        $this->modelo = new Usuario();

        // Inicializar el servicio de imágenes
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/usuarios/');
    }

    /**
     * Muestra la lista de usuarios
     */
    public function index()
    {
        // Obtener todos los usuarios
        return $this->modelo->getAll();
    }

    /**
     * Muestra el formulario para crear un nuevo usuario
     */
    public function crear()
    {
        // Incluir la vista del formulario
        require_once __DIR__ . '/../../views/usuarios/create.php';
    }

    /**
     * Prepara los datos del usuario desde $_POST
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosUsuario($post_data)
    {
        $datos = [
            'nombre' => isset($post_data['nombre']) ? trim($post_data['nombre']) : '',
            'apellidop' => isset($post_data['apellidop']) ? trim($post_data['apellidop']) : '',
            'apellidom' => isset($post_data['apellidom']) ? trim($post_data['apellidom']) : '',
            'tipodocumento' => isset($post_data['tipodocumento']) ? trim($post_data['tipodocumento']) : '',
            'numdocumento' => isset($post_data['numdocumento']) ? trim($post_data['numdocumento']) : '',
            'direccion' => isset($post_data['direccion']) && !empty($post_data['direccion']) ? trim($post_data['direccion']) : null,
            'telefono' => isset($post_data['telefono']) && !empty($post_data['telefono']) ? trim($post_data['telefono']) : null,
            'correo' => isset($post_data['correo']) && !empty($post_data['correo']) ? trim($post_data['correo']) : '',
            'cargo' => isset($post_data['cargo']) && !empty($post_data['cargo']) ? trim($post_data['cargo']) : '',
            'clave' => isset($post_data['clave']) ? trim($post_data['clave']) : '',
            'estado' => isset($post_data['estado']) ? (int)$post_data['estado'] : 1,
            'imagen' => null // Se establece más tarde
        ];

        return $datos;
    }

    /**
     * Valida que el usuario en sesión pueda crear/modificar al objetivo con esos datos.
     * Evita la escalada de privilegios: solo un administrador gestiona administradores,
     * y nadie edita su propio cargo, estado ni permisos.
     *
     * @param array|null $objetivo Usuario existente (null al crear)
     * @param array $datos Datos ya preparados (cargo/estado)
     * @return string|null Mensaje de error o null si es válido
     */
    private function validarPrivilegios($objetivo, array $datos)
    {
        require_once __DIR__ . '/../../services/AuthorizationService.php';
        $auth = new AuthorizationService();
        $idSesion = (int)($_SESSION['usuario_id'] ?? 0);
        $actorEsAdmin = $auth->esAdministrador($idSesion);

        if (!in_array($datos['cargo'], self::CARGOS_VALIDOS, true)) {
            return 'Cargo no válido.';
        }

        if ($actorEsAdmin) {
            if ($objetivo && (int)$objetivo['idusuario'] === $idSesion
                && ($datos['cargo'] !== $objetivo['cargo'] || (int)$datos['estado'] !== (int)$objetivo['estado'])) {
                return 'No puede cambiar su propio cargo ni su estado.';
            }
            return null;
        }

        if ($datos['cargo'] === 'Administrador') {
            return 'No tiene permisos para asignar el cargo de Administrador.';
        }
        if ($objetivo) {
            if ($objetivo['cargo'] === 'Administrador') {
                return 'No tiene permisos para modificar a un Administrador.';
            }
            if ((int)$objetivo['idusuario'] === $idSesion) {
                if ($datos['cargo'] !== $objetivo['cargo'] || (int)$datos['estado'] !== (int)$objetivo['estado']) {
                    return 'No puede cambiar su propio cargo ni su estado.';
                }
            }
        }
        return null;
    }

    /**
     * Procesa el formulario para guardar un nuevo usuario
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos del usuario
        $datosCrudos = $this->prepararDatosUsuario($_POST);
        $datos = $this->modelo->sanitizarDatos($datosCrudos);
        // La contraseña no se escapa: se compara y hashea tal como la escribió el usuario
        $datos['clave'] = $datosCrudos['clave'];

        $errorPrivilegio = $this->validarPrivilegios(null, $datos);
        if ($errorPrivilegio) {
            return ['success' => false, 'message' => $errorPrivilegio, 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Verificar que las contraseñas coincidan
        $confirmar_clave = isset($_POST['confirmar_clave']) ? trim($_POST['confirmar_clave']) : '';
        if ($datos['clave'] !== $confirmar_clave) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden', 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Procesar imagen usando el servicio
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $imagen_path = $this->imagenService->procesarImagen($_FILES['imagen']);
            if ($imagen_path) {
                $datos['imagen'] = $imagen_path;
            } else {
                return ['success' => false, 'message' => 'Error al procesar la imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => 'create.php'];
            }
        }

        // Guardar usuario usando el modelo
        if ($this->modelo->crear($datos)) {
            // Obtener el ID del usuario recién creado
            $idusuario = $this->modelo->getLastInsertId();

            // Procesar permisos seleccionados
            $this->procesarPermisos($idusuario, $_POST);

            return ['success' => true, 'message' => 'Usuario creado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => 'Error al crear el usuario: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra el formulario para editar un usuario
     * 
     * @param int $id ID del usuario
     * @return array|null Datos del usuario o redirige en caso de error
     */
    public function editar($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            return false;
        }

        // Obtener datos del usuario
        $usuario = $this->modelo->getById($id);

        if (!$usuario) {
            return false;
        }

        // Devolver los datos del usuario
        return $usuario;
    }

    /**
     * Procesa el formulario para actualizar un usuario
     */
    public function actualizar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Obtener ID del usuario
        $id = isset($_POST['idusuario']) ? (int)$_POST['idusuario'] : 0;

        if (!$id) {
            return ['success' => false, 'message' => 'ID de usuario no válido', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Obtener datos actuales del usuario
        $usuario_actual = $this->modelo->getById($id);
        if (!$usuario_actual) {
            return ['success' => false, 'message' => 'Usuario no encontrado para actualizar', 'icon' => 'error', 'redirect' => 'index.php'];
        }

        // Guardar imagen actual para posible eliminación posterior
        $imagen_antigua = $usuario_actual['imagen'];

        // Preparar datos del usuario
        $datos = $this->prepararDatosUsuario($_POST);

        // Asegurarse de que los campos obligatorios estén presentes incluso si no se modificaron
        $campos_obligatorios = ['nombre', 'apellidop', 'tipodocumento', 'numdocumento', 'correo', 'cargo'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo]) && isset($usuario_actual[$campo])) {
                $datos[$campo] = $usuario_actual[$campo];
            }
        }

        // Sanitizar los datos
        $datos = $this->modelo->sanitizarDatos($datos);

        // Establecer la imagen anterior por defecto
        $datos['imagen'] = $imagen_antigua;

        $errorPrivilegio = $this->validarPrivilegios($usuario_actual, $datos);
        if ($errorPrivilegio) {
            return ['success' => false, 'message' => $errorPrivilegio, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Validar datos en el modelo (excluyendo el usuario actual)
        $errores = $this->modelo->validarDatos($datos, $id);

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }

        // Procesar nueva imagen si se subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $nueva_imagen_path = $this->imagenService->procesarImagen($_FILES['imagen']);
            if ($nueva_imagen_path) {
                $datos['imagen'] = $nueva_imagen_path;
                $eliminar_imagen_antigua = $imagen_antigua && $imagen_antigua !== 'user_default.jpg';
            } else {
                return ['success' => false, 'message' => 'Error al procesar la nueva imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }
        }

        // Actualizar usuario
        $actualizado = $this->modelo->actualizar($id, $datos);

        // La imagen anterior se borra solo si el UPDATE tuvo éxito
        if ($actualizado && !empty($eliminar_imagen_antigua)) {
            $this->imagenService->eliminarImagen($imagen_antigua);
        }

        // Procesar cambio de contraseña si se proporcionó
        $clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
        $confirmar_clave = isset($_POST['confirmar_clave']) ? trim($_POST['confirmar_clave']) : '';

        $clave_actualizada = true; // Por defecto, asumimos que no hay cambio de clave

        if (!empty($clave) || !empty($confirmar_clave)) {
            // Validar que ambos campos estén completos
            if (empty($clave) || empty($confirmar_clave)) {
                return ['success' => false, 'message' => 'Para cambiar la contraseña, debe completar ambos campos', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }

            // Validar que las contraseñas coincidan
            if ($clave !== $confirmar_clave) {
                return ['success' => false, 'message' => 'Las contraseñas no coinciden', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }

            // Validar longitud mínima
            if (strlen($clave) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres', 'icon' => 'error', 'redirect' => "update.php?id=$id"];
            }

            // Actualizar la contraseña
            $clave_actualizada = $this->modelo->actualizarClave($id, $clave);
        }

        if ($actualizado && $clave_actualizada) {
            // Nadie edita sus propios permisos
            if ($id !== (int)($_SESSION['usuario_id'] ?? 0)) {
                $this->procesarPermisos($id, $_POST);
            }

            return ['success' => true, 'message' => 'Usuario actualizado correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            $error_message = 'Error al actualizar el usuario.';
            if (!$actualizado) {
                $db_error = $this->modelo->getLastError();
                $error_message .= ' Problema con datos del usuario: ' . ($db_error ? $db_error : 'Error desconocido.');
            }
            if (!$clave_actualizada) {
                $error_message .= ' Problema al actualizar contraseña.';
            }
            return ['success' => false, 'message' => $error_message, 'icon' => 'error', 'redirect' => "update.php?id=$id"];
        }
    }

    /**
     * Cambia el estado de un usuario (activa/desactiva)
     * 
     * @param int $id ID del usuario
     * @param int $estado_actual Estado actual del usuario (1 para activo, 0 para inactivo)
     * @return array Resultado de la operación
     */
    public function cambiarEstadoUsuario($id = null, $estado_actual = null)
    {
        if ($id === null) {
            return ['success' => false, 'message' => 'ID de usuario no válido', 'icon' => 'error'];
        }

        // Calcular el nuevo estado a partir del estado real en la base de datos,
        // no del valor enviado por el cliente, que puede ser manipulado
        $usuario = $this->modelo->getById($id);
        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuario no encontrado', 'icon' => 'error'];
        }

        $nuevo_estado = $usuario['estado'] == 1 ? 0 : 1; // Cambia el estado

        $errorPrivilegio = $this->validarPrivilegios($usuario, ['cargo' => $usuario['cargo'], 'estado' => $nuevo_estado]);
        if ($errorPrivilegio) {
            return ['success' => false, 'message' => $errorPrivilegio, 'icon' => 'error'];
        }

        if ($this->modelo->actualizarEstado($id, $nuevo_estado)) {
            $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
            return ['success' => true, 'message' => "Usuario $accion correctamente", 'icon' => 'success', 'nuevo_estado' => $nuevo_estado];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar el estado del usuario: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }

    /**
     * Procesa los permisos seleccionados para un usuario
     * 
     * @param int $idusuario ID del usuario
     * @param array $post_data Datos del formulario
     */
    private function procesarPermisos($idusuario, $post_data)
    {
        // Incluir el servicio de autorización
        require_once __DIR__ . '/../../services/AuthorizationService.php';
        $authService = new AuthorizationService();

        // Obtener todos los permisos disponibles
        $todos_permisos = $authService->obtenerTodosLosPermisos();

        // Permisos seleccionados en el formulario
        $permisos_seleccionados = isset($post_data['permisos']) ? $post_data['permisos'] : [];

        // Procesar cada permiso disponible
        foreach ($todos_permisos as $permiso) {
            $idpermiso = $permiso['idpermiso'];

            // Si el permiso está seleccionado, asignarlo
            if (in_array($idpermiso, $permisos_seleccionados)) {
                $authService->asignarPermiso($idusuario, $idpermiso);
            } else {
                // Si no está seleccionado, revocarlo
                $authService->revocarPermiso($idusuario, $idpermiso);
            }
        }
    }
}

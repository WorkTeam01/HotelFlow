<?php

/**
 * Controlador de Autenticación
 * 
 * Gestiona las operaciones relacionadas con la autenticación de usuarios
 * 
 * @author Sistema de Alojamiento
 * @version 1.2
 */
class AuthController
{
    /**
     * Modelo de Usuario
     * @var Usuario
     */
    private $modelo;

    /**
     * Modelo de IntentoLogin
     * @var IntentoLogin
     */
    private $intentoLogin;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Usuario
        require_once __DIR__ . '/../../models/Usuario.php';
        $this->modelo = new Usuario();

        // Incluir el modelo de IntentoLogin (rate-limiting de login)
        require_once __DIR__ . '/../../models/IntentoLogin.php';
        $this->intentoLogin = new IntentoLogin();

        // Incluir funciones globales de sesión y CSRF (generateCSRFToken/verifyCSRFToken/regenerateCSRFToken)
        require_once __DIR__ . '/../../views/layouts/session.php';
    }

    /**
     * Muestra la página de login
     */
    public function showLoginForm()
    {
        // Incluir la vista del formulario de login
        require_once __DIR__ . '/../../views/login/login.php';
    }

    /**
     * Procesa el formulario de login
     */
    public function login()
    {
        // Purga probabilística de intentos antiguos (evita necesidad de cron)
        if (random_int(1, 100) === 1) {
            $this->intentoLogin->limpiarAntiguos();
        }

        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Verificar token CSRF (obligatorio)
            $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
            if (!verifyCSRFToken($csrf_token)) {
                $_SESSION['mensaje'] = 'Error de seguridad. Por favor, intente nuevamente.';
                $_SESSION['icono'] = 'error';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../views/login/index.php'));
                exit;
            }

            // Validar datos
            $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
            $clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
            $errors = [];

            // Validaciones básicas
            if (empty($identifier)) {
                $errors[] = 'Debe ingresar un correo o número de documento';
            }

            if (empty($clave)) {
                $errors[] = 'Debe ingresar una contraseña';
            }

            // Si hay errores básicos, mostrar el primero y redirigir
            if (!empty($errors)) {
                $_SESSION['mensaje'] = $errors[0];
                $_SESSION['icono'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            // Rate-limiting: verificar bloqueo por intentos fallidos (identificador y/o IP)
            // antes de revelar si el identificador existe en el sistema.
            if ($this->intentoLogin->estaBloqueado($identifier, $ip)) {
                $_SESSION['mensaje'] = 'Demasiados intentos fallidos. Intente nuevamente en unos minutos.';
                $_SESSION['icono'] = 'warning';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Determinar si el identificador es un correo o un número de documento
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            // Verificar credenciales completas (identificador y contraseña). No se comprueba
            // la existencia del usuario por separado antes de esto: hacerlo permitiría a un
            // atacante distinguir "usuario no existe" de "contraseña incorrecta" por el tiempo
            // de respuesta (se saltaría password_verify() para identificadores inexistentes).
            // loginPorCorreo/loginPorNumDocumento siempre invocan password_verify() contra un
            // hash (real o dummy) para que el tiempo de respuesta no filtre esa información.
            if ($isEmail) {
                $usuario = $this->modelo->loginPorCorreo($identifier, $clave);
            } else {
                $usuario = $this->modelo->loginPorNumDocumento($identifier, $clave);
            }

            if ($usuario) {
                // Registrar intento exitoso (corta la racha de fallos)
                $this->intentoLogin->registrar($identifier, $ip, true);

                // Iniciar sesión
                $this->iniciarSesion($usuario);

                // Redirigir al dashboard
                $_SESSION['mensaje'] = 'Bienvenido al sistema ' . $_SESSION['usuario_nombre'];
                $_SESSION['icono'] = 'success';
                header('Location: ../../');
            } else {
                // Credenciales incorrectas (la contraseña es incorrecta)
                $this->intentoLogin->registrar($identifier, $ip, false);
                $_SESSION['mensaje'] = 'Credenciales incorrectas';
                $_SESSION['icono'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            exit;
        }

        // Si no se envió el formulario, redirigir al login
        header('Location: ../../views/login/login.php');
    }

    /**
     * Inicia la sesión del usuario
     * 
     * @param array $usuario Datos del usuario
     */
    private function iniciarSesion($usuario)
    {
        // Regenerar ID de sesión para evitar session fixation
        session_regenerate_id(true);

        // Guardar datos del usuario en la sesión
        $_SESSION['usuario_id'] = $usuario['idusuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_cargo'] = $usuario['cargo'];
        $_SESSION['usuario_imagen'] = $usuario['imagen'] ?? 'user_default.jpg';
        $_SESSION['autenticado'] = true;

        // Registrar último acceso
        $_SESSION['ultimo_acceso'] = time();

        // Registrar IP y User Agent para seguridad adicional
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Eliminar todas las variables de sesión
        $_SESSION = array();

        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al login
        header('Location: ../../views/login/login.php');
        exit;
    }

    /**
     * Verifica si la sesión es válida (para uso en middleware)
     * 
     * @return bool True si la sesión es válida, False en caso contrario
     */
    public function verificarSesion()
    {
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
            return false;
        }

        // Verificar tiempo de inactividad
        $timeout = 3600; // 60 minutos en segundos
        if (!isset($_SESSION['ultimo_acceso']) || (time() - $_SESSION['ultimo_acceso']) > $timeout) {
            $this->logout();
            return false;
        }

        // Verificar posible session hijacking comparando IP y User Agent
        if (isset($_SESSION['ip']) && isset($_SESSION['user_agent'])) {
            if (
                $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
                $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
            ) {
                $this->logout();
                return false;
            }
        }

        // Actualizar tiempo de último acceso
        $_SESSION['ultimo_acceso'] = time();

        return true;
    }
}

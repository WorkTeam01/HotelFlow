<?php

/**
 * Gestión de Sesiones
 * 
 * Este archivo verifica si el usuario está autenticado y gestiona la sesión
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Cargar configuración desde .env
try {
    // Cargar variables de entorno desde .env
    $env_file = __DIR__ . '/../../.env';

    if (!file_exists($env_file)) {
        die("Error: Archivo .env no encontrado. Por favor, configure el archivo .env con APP_URL y otras variables necesarias.");
    }

    // Misma lectura del .env que el resto de la app (config/env.php)
    require_once __DIR__ . '/../../config/env.php';
    loadEnv($env_file);

    // Obtener URL de la aplicación desde .env
    $app_url = $_ENV['APP_URL'] ?? getenv('APP_URL');

    if (empty($app_url)) {
        die("Error: APP_URL no está configurada en el archivo .env. Por favor, añada APP_URL=su_dominio_aqui/ en el archivo .env");
    }

    // Asegurar que termine con barra
    if (substr($app_url, -1) !== '/') {
        $app_url .= '/';
    }

    // Definir URL base global
    $GLOBALS['URL'] = $app_url;
    $URL = $GLOBALS['URL'];

    // Obtener nombre de la aplicación desde .env
    $app_name = $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?: 'HotelFlow';
    $GLOBALS['APP_NAME'] = $app_name;
    $APP_NAME = $GLOBALS['APP_NAME'];

    // Obtener versión de la aplicación desde .env
    $app_version = $_ENV['APP_VERSION'] ?? getenv('APP_VERSION') ?: '1.0.4';
    $GLOBALS['APP_VERSION'] = $app_version;
    $APP_VERSION = $GLOBALS['APP_VERSION'];
} catch (Exception $e) {
    die("Error al cargar la configuración: " . $e->getMessage() . ". Verifique que el archivo .env esté configurado correctamente.");
}

/**
 * Verificar si el usuario está autenticado
 * 
 * @return bool True si está autenticado, False en caso contrario
 */
function isAuthenticated()
{
    return isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;
}

/**
 * Verificar tiempo de inactividad
 * 
 * @param int $timeout Tiempo de inactividad en segundos (por defecto 86400 = 1 día)
 * @return bool True si la sesión sigue activa, False si ha expirado
 */
function checkSessionTimeout($timeout = 86400)
{
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactivo = time() - $_SESSION['ultimo_acceso'];

        if ($inactivo >= $timeout) {
            // Sesión expirada, destruir sesión
            session_unset();
            session_destroy();
            return false;
        }
    }

    // Actualizar tiempo de último acceso
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

/**
 * Verificar posible secuestro de sesión
 * 
 * @return bool True si la sesión es segura, False si se detectó posible secuestro
 */
function checkSessionSecurity()
{
    if (isset($_SESSION['ip']) && isset($_SESSION['user_agent'])) {
        if (
            $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
        ) {
            // Posible session hijacking, destruir sesión
            session_unset();
            session_destroy();
            return false;
        }
    }
    return true;
}

/**
 * Verifica contra la BD que el usuario de la sesión siga activo. Si fue desactivado
 * mientras tenía sesión abierta, la destruye para que no conserve acceso.
 *
 * @return bool
 */
function usuarioSigueActivo()
{
    if (!isset($_SESSION['usuario_id'])) {
        return true; // lo resuelve isAuthenticated()
    }
    try {
        require_once __DIR__ . '/../../config/conexion.php';
        $stmt = Conexion::getInstance()->getConnection()->prepare('SELECT estado FROM usuarios WHERE idusuario = :id');
        $stmt->bindValue(':id', (int)$_SESSION['usuario_id'], PDO::PARAM_INT);
        $stmt->execute();
        $estado = $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[session] No se pudo verificar el estado del usuario: ' . $e->getMessage());
        return true; // fail-open ante fallo de infraestructura, como el rate-limit de login
    }
    if ((int)$estado !== 1) {
        session_unset();
        session_destroy();
        return false;
    }
    return true;
}

/**
 * Requerir inicio de sesión para acceder a una página
 * 
 * @param string $redirect_url URL a la que redirigir si no hay sesión
 */
function requireLogin($redirect_url = null)
{
    global $URL;

    if (!$redirect_url) {
        $redirect_url = $URL . 'views/login/login.php';
    }

    if (!isAuthenticated() || !checkSessionTimeout() || !checkSessionSecurity() || !usuarioSigueActivo()) {
        // Guardar mensaje de sesión expirada
        if (isset($_SESSION)) {
            if (!isAuthenticated()) {
                $_SESSION['mensaje'] = 'Debe iniciar sesión para acceder a esta página.';
            } else {
                $_SESSION['mensaje'] = 'Sesión expirada por inactividad. Por favor inicie sesión nuevamente.';
            }
            $_SESSION['icono'] = 'warning';
        }

        // Redirigir al login
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Comprueba que el usuario en sesión sea Administrador o tenga acceso al módulo.
 * Única implementación de la guarda `esAdministrador || puedeAccederModulo`; los
 * endpoints la invocan vía requireModuloAjax()/requireModuloVista().
 *
 * @param string $modulo Nombre del permiso (tabla `permiso`)
 * @return bool
 */
function usuarioPuedeAccederModulo($modulo)
{
    require_once __DIR__ . '/../../services/AuthorizationService.php';

    $idusuario = $_SESSION['usuario_id'] ?? 0;
    $auth = new AuthorizationService();

    return $auth->esAdministrador($idusuario) || $auth->puedeAccederModulo($idusuario, $modulo);
}

/**
 * Guarda de permisos para endpoints que responden JSON. Sin permiso responde
 * `{success:false, message}` y termina. Llamar después de requireLogin().
 *
 * @param string $modulo Nombre del permiso
 * @param string $mensaje Mensaje para el usuario
 * @param bool $http403 Si true, además envía el código HTTP 403
 */
function requireModuloAjax($modulo, $mensaje = 'No tiene permisos para realizar esta acción', $http403 = false)
{
    if (usuarioPuedeAccederModulo($modulo)) {
        return;
    }

    if ($http403) {
        http_response_code(403);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

/**
 * Guarda de permisos para vistas y acciones con redirección. Sin permiso deja el
 * mensaje en sesión y redirige. Llamar después de requireLogin() y ANTES de incluir
 * header.php (el redirect no funciona con salida ya emitida).
 *
 * @param string $modulo Nombre del permiso
 * @param string $destino Ruta relativa a $URL a la que redirigir
 * @param string $mensaje Mensaje para el usuario
 */
function requireModuloVista($modulo, $destino = 'index.php', $mensaje = 'No tiene permisos para acceder a esta sección.')
{
    global $URL;

    if (usuarioPuedeAccederModulo($modulo)) {
        return;
    }

    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . $destino);
    exit;
}

/**
 * Obtener datos del usuario actual
 * 
 * @return array|null Datos del usuario o null si no hay sesión
 */
function getCurrentUser()
{
    if (isAuthenticated()) {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['usuario_nombre'] ?? null,
            'correo' => $_SESSION['usuario_correo'] ?? null,
            'cargo' => $_SESSION['usuario_cargo'] ?? null,
            'imagen' => $_SESSION['usuario_imagen'] ?? 'public/img/user_default.jpg'
        ];
    }
    return null;
}

/**
 * Generar un token CSRF para proteger formularios
 * 
 * @return string Token CSRF
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar si un token CSRF es válido
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido, False en caso contrario
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerar el token CSRF (útil después de usarlo)
 */
function regenerateCSRFToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

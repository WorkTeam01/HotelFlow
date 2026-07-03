<?php

/**
 * Controlador de Perfil de Usuario
 * 
 * Gestiona las operaciones relacionadas con el perfil del usuario logueado
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir el servicio de imágenes y el modelo de usuario
require_once __DIR__ . '/../../services/ImagenService.php';
require_once __DIR__ . '/../../models/Usuario.php';

class PerfilController
{
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
        $this->modelo = new Usuario();
        $this->imagenService = new ImagenService(__DIR__ . '/../../public/uploads/usuarios/');
    }

    /**
     * Obtiene los datos del perfil del usuario logueado
     * 
     * @return array|false Datos del usuario o false si no existe
     */
    public function obtenerPerfil()
    {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        $id = $_SESSION['usuario_id'];
        return $this->modelo->getById($id);
    }

    /**
     * Actualiza la imagen de perfil del usuario
     * 
     * @return array Resultado de la operación
     */
    public function actualizarImagen()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido', 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }

        if (!isset($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'Usuario no autenticado', 'icon' => 'error', 'redirect' => 'views/login/login.php'];
        }

        $id = $_SESSION['usuario_id'];

        // Obtener datos actuales del usuario
        $usuario_actual = $this->modelo->getById($id);
        if (!$usuario_actual) {
            return ['success' => false, 'message' => 'Usuario no encontrado', 'icon' => 'error', 'redirect' => 'views/login/login.php'];
        }

        // Guardar imagen actual
        $imagen_antigua = $usuario_actual['imagen'];

        // Verificar si se subió una nueva imagen
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] != 0) {
            return ['success' => false, 'message' => 'No se ha seleccionado ninguna imagen', 'icon' => 'warning', 'redirect' => 'views/usuarios/perfil.php'];
        }

        // Procesar la nueva imagen
        $nueva_imagen_path = $this->imagenService->procesarImagen($_FILES['imagen']);
        if (!$nueva_imagen_path) {
            return ['success' => false, 'message' => 'Error al procesar la imagen. Verifique el formato y tamaño.', 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }

        // Actualizar SOLO la imagen en la base de datos usando el método específico
        if ($this->modelo->actualizarImagen($id, $nueva_imagen_path)) {
            // Eliminar imagen anterior si no es la predeterminada
            if ($imagen_antigua && $imagen_antigua !== 'user_default.jpg' && $imagen_antigua !== $nueva_imagen_path) {
                $this->imagenService->eliminarImagen($imagen_antigua);
            }

            // Actualizar la imagen en la sesión
            $_SESSION['usuario_imagen'] = $nueva_imagen_path;

            return ['success' => true, 'message' => 'Imagen actualizada correctamente', 'icon' => 'success', 'redirect' => 'views/usuarios/perfil.php'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la imagen: ' . $this->modelo->getLastError(), 'icon' => 'error', 'redirect' => 'views/usuarios/perfil.php'];
        }
    }

    /**
     * Cambia la contraseña del usuario actual
     * 
     * @return array Resultado de la operación
     */
    public function cambiarContrasena()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Método no permitido', 'icon' => 'error'];
        }

        if (!isset($_SESSION['usuario_id'])) {
            return ['success' => false, 'message' => 'Usuario no autenticado', 'icon' => 'error'];
        }

        $id = $_SESSION['usuario_id'];

        // Validar datos
        $clave_actual = isset($_POST['clave_actual']) ? trim($_POST['clave_actual']) : '';
        $nueva_clave = isset($_POST['nueva_clave']) ? trim($_POST['nueva_clave']) : '';
        $confirmar_nueva_clave = isset($_POST['confirmar_nueva_clave']) ? trim($_POST['confirmar_nueva_clave']) : '';

        if (empty($clave_actual) || empty($nueva_clave) || empty($confirmar_nueva_clave)) {
            return ['success' => false, 'message' => 'Todos los campos de contraseña son obligatorios', 'icon' => 'error'];
        }

        // Verificar contraseña actual usando el método específico
        if (!$this->modelo->verificarContrasenaActual($id, $clave_actual)) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta', 'icon' => 'error'];
        }

        if ($nueva_clave !== $confirmar_nueva_clave) {
            return ['success' => false, 'message' => 'Las nuevas contraseñas no coinciden', 'icon' => 'error'];
        }

        if (strlen($nueva_clave) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres', 'icon' => 'error'];
        }

        // Usar el método específico para actualizar solo la contraseña
        if ($this->modelo->actualizarClave($id, $nueva_clave)) {
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente. Se cerrará la sesión.', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $this->modelo->getLastError(), 'icon' => 'error'];
        }
    }
}

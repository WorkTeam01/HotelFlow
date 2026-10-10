<?php

/**
 * Servicio para manejo de imágenes
 * 
 * Gestiona la carga, procesamiento y eliminación de imágenes
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */
class ImagenService
{
    /**
     * Tamaño máximo de archivo permitido en bytes (5MB)
     */
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    /**
     * Directorio de carga de imágenes
     * @var string
     */
    private $upload_dir;

    /**
     * Tipos de imágenes permitidos
     * @var array
     */
    private $allowed_types;

    /**
     * Tamaño máximo de archivo permitido (en bytes)
     * @var int
     */
    private $max_size = self::MAX_SIZE_BYTES;

    /**
     * Constructor de la clase
     * 
     * @param string $upload_dir Directorio de carga de imágenes
     */
    public function __construct($upload_dir)
    {
        $this->upload_dir = $upload_dir;
        $this->allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];

        // Crear directorio si no existe
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    /**
     * Procesa una imagen subida
     * 
     * @param array $imagen Datos de la imagen subida
     * @return string|bool Nombre del archivo o false si hubo un error
     */
    public function procesarImagen($imagen)
    {
        // Si no hay imagen o hay error, retornar false
        if (!$imagen || $imagen['error'] != 0) {
            return false;
        }

        // Verificar tamaño de archivo
        if ($imagen['size'] > $this->max_size) {
            return false;
        }

        // Verificar tipo real del archivo inspeccionando su contenido (no confiar en el MIME enviado por el cliente)
        $tipo_real = mime_content_type($imagen['tmp_name']);
        if (!in_array($tipo_real, $this->allowed_types)) {
            return false;
        }

        // Verificar que el archivo sea una imagen válida y obtener su tipo real
        $info_imagen = getimagesize($imagen['tmp_name']);
        if ($info_imagen === false) {
            return false;
        }

        $extensiones_permitidas = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];

        if (!isset($extensiones_permitidas[$info_imagen[2]])) {
            return false;
        }

        // Usar la extensión derivada del contenido real, no la del nombre enviado por el cliente
        $extension = $extensiones_permitidas[$info_imagen[2]];
        $nombre_sin_extension = pathinfo($imagen['name'], PATHINFO_FILENAME);

        // Sanitizar nombre del archivo
        $nombre_sin_extension = preg_replace('/[^a-zA-Z0-9_-]/', '', $nombre_sin_extension);

        // Generar nombre único
        $nombre_archivo = uniqid() . '_' . $nombre_sin_extension . '.' . $extension;

        // Ruta completa
        $ruta_destino = $this->upload_dir . $nombre_archivo;

        // Mover archivo
        if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
            return $nombre_archivo; // Retornar solo el nombre para guardar en BD
        }

        return false;
    }

    /**
     * Elimina una imagen
     * 
     * @param string $nombre_archivo Nombre del archivo a eliminar
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminarImagen($nombre_archivo)
    {
        // No eliminar imagen predeterminada
        if (!$nombre_archivo || $nombre_archivo == 'user_default.jpg') {
            return true;
        }

        $ruta_completa = $this->upload_dir . $nombre_archivo;
        if (file_exists($ruta_completa)) {
            return unlink($ruta_completa);
        }

        return true; // Si no existe, consideramos que ya está eliminada
    }
}

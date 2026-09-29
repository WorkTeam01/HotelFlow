<?php

/**
 * Imagen de un producto con fallback al placeholder por defecto.
 *
 * Variables que el include-r debe definir ANTES de incluir este partial:
 *   $URL       - base de la aplicación (la define layouts/session.php)
 *   $producto  - fila de producto; la clave 'imagen' puede ser null o vacía
 *   $clase_img - clases del <img>, incluyendo la de tamaño (ver productos.css)
 *   $alt       - texto alternativo de la imagen
 */

$src_imagen = !empty($producto['imagen'])
    ? $URL . 'public/uploads/productos/' . $producto['imagen']
    : $URL . 'public/uploads/productos/producto_default.png';
?>
<img src="<?= $src_imagen; ?>" alt="<?= htmlspecialchars($alt); ?>" class="<?= $clase_img; ?>" loading="lazy">
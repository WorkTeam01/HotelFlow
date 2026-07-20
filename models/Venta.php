<?php

/**
 * Modelo Venta
 * 
 * Gestiona las operaciones relacionadas con las ventas en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Venta
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de ventas en la base de datos
     * @var string
     */
    private $tabla = 'venta';

    /**
     * Tabla de detalles de venta
     * @var string
     */
    private $tablaDetalle = 'detalleventa';

    /**
     * Último error ocurrido
     * @var string
     */
    private $lastError = '';

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    /**
     * Obtiene el último error ocurrido
     * 
     * @return string Mensaje de error
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Sanitiza los datos de entrada para prevenir inyección SQL y XSS
     * 
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitizarDatos($datos)
    {
        $sanitized = [];
        foreach ($datos as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todas las ventas con información del cliente y usuario
     * 
     * @return array Lista de ventas
     */
    public function getAll()
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      ORDER BY v.idventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene una venta por su ID con detalles
     * 
     * @param int $id ID de la venta
     * @return array|bool Datos de la venta con detalles o false si no existe
     */
    public function getById($id)
    {
        try {
            // Obtener información básica de la venta
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.idventa = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                return false;
            }

            // Obtener detalles de la venta
            $queryDetalle = "SELECT dv.*, pr.nombre as producto_nombre, pr.codigo as producto_codigo
                             FROM {$this->tablaDetalle} dv
                             JOIN productos pr ON dv.idproducto = pr.idproducto
                             WHERE dv.idventa = :id";
            $stmtDetalle = $this->conexion->prepare($queryDetalle);
            $stmtDetalle->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();
            $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            $venta['detalles'] = $detalles;

            return $venta;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Crea una nueva venta con sus detalles
     * 
     * @param array $datos Datos de la venta y sus detalles
     * @return int|bool ID de la nueva venta o false en caso de error
     */
    public function crear($datos)
    {
        try {
            $this->conexion->beginTransaction();

            // Insertar la cabecera de la venta
            $query = "INSERT INTO {$this->tabla} 
                     (idcliente, idusuario, totalventa, fechaventa, metodopago, pagorecibido, cambio, estado) 
                     VALUES 
                     (:idcliente, :idusuario, :totalventa, :fechaventa, :metodopago, :pagorecibido, :cambio, :estado)";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':idcliente', $datos['idcliente'], PDO::PARAM_INT);
            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':totalventa', $datos['totalventa'], PDO::PARAM_STR);
            $stmt->bindParam(':fechaventa', $datos['fechaventa'], PDO::PARAM_STR);
            $stmt->bindParam(':metodopago', $datos['metodopago'], PDO::PARAM_STR);
            $stmt->bindParam(':pagorecibido', $datos['pagorecibido'], PDO::PARAM_STR);
            $stmt->bindParam(':cambio', $datos['cambio'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new PDOException("Error al crear la venta");
            }

            $idVenta = $this->conexion->lastInsertId();

            // Insertar los detalles de la venta
            $totalCalculado = 0;
            foreach ($datos['detalles'] as $detalle) {
                // Bloquear la fila del producto, verificar stock y tomar el precio real de la BD
                // (no confiar en el precio enviado por el cliente)
                $stmtStock = $this->conexion->prepare("SELECT stock, precioventa FROM productos WHERE idproducto = :id FOR UPDATE");
                $stmtStock->bindParam(':id', $detalle['idproducto'], PDO::PARAM_INT);
                $stmtStock->execute();
                $productoActual = $stmtStock->fetch(PDO::FETCH_ASSOC);

                if ($productoActual === false || $productoActual['stock'] < $detalle['cantidad']) {
                    throw new PDOException("Stock insuficiente para el producto ID {$detalle['idproducto']}");
                }

                $precioReal = (float)$productoActual['precioventa'];
                $descuento = (float)($detalle['descuento'] ?? 0.00);
                $totalCalculado += ($precioReal * $detalle['cantidad']) - $descuento;

                $queryDetalle = "INSERT INTO {$this->tablaDetalle}
                                (idventa, idproducto, cantidad, precioventa, descuento)
                                VALUES
                                (:idventa, :idproducto, :cantidad, :precioventa, :descuento)";

                $stmtDetalle = $this->conexion->prepare($queryDetalle);

                $stmtDetalle->bindParam(':idventa', $idVenta, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idproducto', $detalle['idproducto'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $detalle['cantidad'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':precioventa', $precioReal, PDO::PARAM_STR);
                $this->bindOptionalParam($stmtDetalle, ':descuento', $descuento, PDO::PARAM_STR);

                if (!$stmtDetalle->execute()) {
                    throw new PDOException("Error al crear el detalle de venta");
                }

                // Actualizar el stock del producto (reducir)
                $this->actualizarStockProducto($detalle['idproducto'], -$detalle['cantidad']);
            }

            // Recalcular y persistir el total real de la venta a partir de los precios de la BD,
            // no del total enviado por el cliente
            $stmtTotal = $this->conexion->prepare("UPDATE {$this->tabla} SET totalventa = :total WHERE idventa = :id");
            $stmtTotal->bindParam(':total', $totalCalculado, PDO::PARAM_STR);
            $stmtTotal->bindParam(':id', $idVenta, PDO::PARAM_INT);
            $stmtTotal->execute();

            $this->conexion->commit();
            return $idVenta;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza el estado de una venta
     * 
     * @param int $id ID de la venta
     * @param string $estado Nuevo estado (activo, inactivo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado WHERE idventa = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Anula una venta y revierte el stock de productos
     * 
     * @param int $id ID de la venta
     * @return bool True si se anuló correctamente, False en caso contrario
     */
    public function anular($id)
    {
        try {
            $this->conexion->beginTransaction();

            // Bloquear la fila de la venta para evitar anulaciones concurrentes duplicadas
            $stmtLock = $this->conexion->prepare("SELECT estado FROM {$this->tabla} WHERE idventa = :id FOR UPDATE");
            $stmtLock->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtLock->execute();
            $estadoActual = $stmtLock->fetchColumn();

            if ($estadoActual === false || $estadoActual == 0) {
                throw new PDOException("La venta no existe o ya está anulada");
            }

            // Obtener los detalles de la venta
            $venta = $this->getById($id);

            // Revertir el stock de cada producto
            foreach ($venta['detalles'] as $detalle) {
                $this->actualizarStockProducto($detalle['idproducto'], $detalle['cantidad']);
            }

            // Actualizar el estado de la venta
            if (!$this->actualizarEstado($id, 0)) {
                throw new PDOException("Error al actualizar el estado de la venta");
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza el stock de un producto
     * 
     * @param int $idProducto ID del producto
     * @param int $cantidad Cantidad a sumar (positivo) o restar (negativo)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    private function actualizarStockProducto($idProducto, $cantidad)
    {
        try {
            $query = "UPDATE productos SET stock = stock + :cantidad WHERE idproducto = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("Error al actualizar el stock del producto: " . $e->getMessage());
        }
    }

    /**
     * Obtiene las ventas por rango de fechas
     * 
     * @param string $fechaInicio Fecha de inicio (formato YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (formato YYYY-MM-DD)
     * @return array Lista de ventas en el rango de fechas
     */
    public function getPorRangoFechas($fechaInicio, $fechaFin)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.fechaventa BETWEEN :fechaInicio AND :fechaFin
                      ORDER BY v.fechaventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene las ventas por estado
     * 
     * @param int $estado Estado de las ventas a buscar (1: Activo, 0: Inactivo)
     * @return array Lista de ventas con el estado especificado
     */
    public function getPorEstado($estado)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.estado = :estado
                      ORDER BY v.fechaventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene las ventas de un usuario específico
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de ventas del usuario
     */
    public function getPorUsuario($idUsuario)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.idusuario = :idUsuario
                      ORDER BY v.fechaventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Obtiene las ventas de un cliente específico
     * 
     * @param int $idCliente ID del cliente
     * @return array Lista de ventas del cliente
     */
    public function getPorCliente($idCliente)
    {
        try {
            $query = "SELECT v.*, 
                      CONCAT(p.nombre, ' ', p.apellidopaterno) as cliente_nombre,
                      u.nombre as usuario_nombre 
                      FROM {$this->tabla} v
                      LEFT JOIN persona p ON v.idcliente = p.idpersona
                      JOIN usuarios u ON v.idusuario = u.idusuario
                      WHERE v.idcliente = :idCliente
                      ORDER BY v.fechaventa DESC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Valida los datos de la venta antes de crear
     * 
     * @param array $datos Datos de la venta
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar campos obligatorios
        $campos_obligatorios = ['idusuario', 'totalventa', 'fechaventa', 'metodopago', 'pagorecibido'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar detalles de la venta
        if (empty($datos['detalles']) || !is_array($datos['detalles']) || count($datos['detalles']) == 0) {
            $errores[] = "La venta debe tener al menos un producto";
        } else {
            foreach ($datos['detalles'] as $detalle) {
                if (empty($detalle['idproducto'])) {
                    $errores[] = "Todos los productos deben ser válidos";
                }
                if (empty($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    $errores[] = "La cantidad debe ser mayor que cero";
                }
                if (empty($detalle['precioventa'])) {
                    $errores[] = "Todos los productos deben tener un precio de venta";
                }
            }
        }

        // Validar método de pago
        $metodosPermitidos = ['Efectivo', 'QR', 'Otros'];
        if (!in_array($datos['metodopago'], $metodosPermitidos)) {
            $errores[] = "El método de pago no es válido";
        }

        // Validar que el pago recibido cubra el total
        if ($datos['pagorecibido'] < ($datos['totalventa'] - ($datos['cambio'] ?? 0))) {
            $errores[] = "El pago recibido no cubre el total de la venta";
        }

        return $errores;
    }

    /**
     * Método auxiliar para manejar parámetros opcionales en consultas preparadas
     * 
     * @param PDOStatement $stmt Declaración preparada
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor del parámetro
     * @param int $type Tipo de dato (PDO::PARAM_*)
     */
    private function bindOptionalParam(&$stmt, $param, $value, $type = PDO::PARAM_STR)
    {
        if ($value === null) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam($param, $value, $type);
        }
    }
    /**
     * Obtiene estadísticas de ventas del día
     * 
     * @return array Estadísticas de ventas (ventas hoy, cliente que más compró, producto más vendido)
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = [
                'ventas_hoy' => 0,
                'total_hoy' => 0,
                'cliente_mas_compro' => null,
                'producto_mas_vendido' => null
            ];

            // Obtener fecha actual
            $hoy = date('Y-m-d');

            // Ventas hoy (cantidad y monto total)
            $query = "SELECT COUNT(*) as cantidad, SUM(totalventa) as total 
                  FROM {$this->tabla} 
                  WHERE DATE(fechaventa) = :hoy AND estado = 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $estadisticas['ventas_hoy'] = $result['cantidad'] ?? 0;
            $estadisticas['total_hoy'] = $result['total'] ?? 0;

            // Cliente que más compró hoy
            $query = "SELECT p.idpersona, CONCAT(p.nombre, ' ', p.apellidopaterno) as nombre_cliente, 
                  COUNT(v.idventa) as compras, SUM(v.totalventa) as total_gastado
                  FROM {$this->tabla} v
                  LEFT JOIN persona p ON v.idcliente = p.idpersona
                  WHERE DATE(v.fechaventa) = :hoy AND v.estado = 1
                  GROUP BY v.idcliente
                  ORDER BY total_gastado DESC
                  LIMIT 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['cliente_mas_compro'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Producto más vendido hoy
            $query = "SELECT pr.idproducto, pr.nombre as producto_nombre, 
                  SUM(dv.cantidad) as cantidad_vendida, 
                  SUM(dv.cantidad * dv.precioventa) as total_vendido
                  FROM {$this->tablaDetalle} dv
                  JOIN {$this->tabla} v ON dv.idventa = v.idventa
                  JOIN productos pr ON dv.idproducto = pr.idproducto
                  WHERE DATE(v.fechaventa) = :hoy AND v.estado = 1
                  GROUP BY dv.idproducto
                  ORDER BY cantidad_vendida DESC
                  LIMIT 1";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->execute();
            $estadisticas['producto_mas_vendido'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $estadisticas;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [
                'ventas_hoy' => 0,
                'total_hoy' => 0,
                'cliente_mas_compro' => null,
                'producto_mas_vendido' => null
            ];
        }
    }
}

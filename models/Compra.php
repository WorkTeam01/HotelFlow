<?php

/**
 * Modelo Compra
 * 
 * Gestiona las operaciones relacionadas con las compras en la base de datos
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/../config/conexion.php';

class Compra
{
    /**
     * Cambios de estado permitidos. Fuente única que leen el controlador (acciones
     * ofrecidas) y el endpoint de cambio de estado.
     */
    public const TRANSICIONES = [
        'pendiente'  => ['completada', 'cancelada'],
        'completada' => ['cancelada'],
        'cancelada'  => [],
    ];

    /**
     * @param string $actual Estado leído de la compra
     * @param string $nuevo  Estado al que se quiere pasar
     */
    public static function transicionPermitida(string $actual, string $nuevo): bool
    {
        return in_array($nuevo, self::TRANSICIONES[$actual] ?? [], true);
    }

    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de compras en la base de datos
     * @var string
     */
    private $tabla = 'compra';

    /**
     * Tabla de detalles de compra
     * @var string
     */
    private $tablaDetalle = 'detallecompra';

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
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene todas las compras con información del usuario
     * 
     * @return array Lista de compras
     */
    public function getAll()
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      ORDER BY c.fechacompra DESC, c.idcompra DESC";
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
     * Obtiene una compra por su ID con detalles
     * 
     * @param int $id ID de la compra
     * @return array|bool Datos de la compra con detalles o false si no existe
     */
    public function getById($id)
    {
        try {
            // Obtener información básica de la compra
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.idcompra = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                return false;
            }

            // Obtener detalles de la compra
            $queryDetalle = "SELECT dc.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                             FROM {$this->tablaDetalle} dc
                             JOIN productos p ON dc.idproducto = p.idproducto
                             WHERE dc.idcompra = :id";
            $stmtDetalle = $this->conexion->prepare($queryDetalle);
            $stmtDetalle->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();
            $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            $compra['detalles'] = $detalles;

            return $compra;
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Crea una nueva compra con sus detalles
     * 
     * @param array $datos Datos de la compra y sus detalles
     * @return int|bool ID de la nueva compra o false en caso de error
     */
    public function crear($datos)
    {
        try {
            $this->conexion->beginTransaction();

            // Insertar la cabecera de la compra
            $query = "INSERT INTO {$this->tabla} 
                     (idusuario, totalcompra, fechacompra, estado, observaciones) 
                     VALUES 
                     (:idusuario, :totalcompra, :fechacompra, :estado, :observaciones)";

            $stmt = $this->conexion->prepare($query);

            $stmt->bindParam(':idusuario', $datos['idusuario'], PDO::PARAM_INT);
            $stmt->bindParam(':totalcompra', $datos['totalcompra'], PDO::PARAM_STR);
            $stmt->bindParam(':fechacompra', $datos['fechacompra'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);
            $this->bindOptionalParam($stmt, ':observaciones', $datos['observaciones'] ?? null);

            if (!$stmt->execute()) {
                throw new PDOException("Error al crear la compra");
            }

            $idCompra = $this->conexion->lastInsertId();

            // Los productos se recorren por id ascendente: mismo orden de bloqueo que
            // completar()/cancelar(), para no deadlockear con una compra concurrente
            $detalles = $datos['detalles'];
            usort($detalles, function ($a, $b) {
                return (int) $a['idproducto'] <=> (int) $b['idproducto'];
            });

            $stmtProducto = $this->conexion->prepare("SELECT estado FROM productos WHERE idproducto = :id FOR UPDATE");
            $stmtDetalle = $this->conexion->prepare(
                "INSERT INTO {$this->tablaDetalle} (idcompra, idproducto, cantidad, preciocompra)
                 VALUES (:idcompra, :idproducto, :cantidad, :preciocompra)"
            );

            // Total en centavos enteros: sumar decimales en coma flotante deriva (0.1 + 0.2)
            $totalCentavos = 0;
            foreach ($detalles as $detalle) {
                $stmtProducto->bindValue(':id', (int) $detalle['idproducto'], PDO::PARAM_INT);
                $stmtProducto->execute();
                $estadoProducto = $stmtProducto->fetchColumn();
                if ($estadoProducto === false || (int) $estadoProducto !== 1) {
                    $this->conexion->rollBack();
                    $this->lastError = 'Hay productos inactivos o inexistentes en la compra.';
                    return false;
                }

                $stmtDetalle->bindValue(':idcompra', $idCompra, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':idproducto', (int) $detalle['idproducto'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', (int) $detalle['cantidad'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':preciocompra', number_format((float) $detalle['preciocompra'], 2, '.', ''), PDO::PARAM_STR);

                if (!$stmtDetalle->execute()) {
                    throw new PDOException("Error al crear el detalle de compra");
                }

                $totalCentavos += (int) round((float) $detalle['preciocompra'] * 100) * (int) $detalle['cantidad'];

                // La mercadería entra al inventario al recibirla (completada), no al emitir la orden
                if ($datos['estado'] === 'completada') {
                    $this->actualizarStockProducto($detalle['idproducto'], $detalle['cantidad']);
                }
            }

            // Persistir el total real calculado desde los detalles, no el enviado por el cliente
            $totalCalculado = number_format($totalCentavos / 100, 2, '.', '');
            $stmtTotal = $this->conexion->prepare("UPDATE {$this->tabla} SET totalcompra = :total WHERE idcompra = :id");
            $stmtTotal->bindParam(':total', $totalCalculado, PDO::PARAM_STR);
            $stmtTotal->bindParam(':id', $idCompra, PDO::PARAM_INT);
            $stmtTotal->execute();

            $this->conexion->commit();
            return $idCompra;
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Actualiza el estado de una compra
     * 
     * @param int $id ID de la compra
     * @param string $estado Nuevo estado (pendiente, completada, cancelada)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstado($id, $estado)
    {
        try {
            $query = "UPDATE {$this->tabla} SET estado = :estado WHERE idcompra = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Líneas de una compra ordenadas por producto. Bloquear los productos siempre en el
     * mismo orden evita el deadlock entre dos compras concurrentes que comparten productos.
     *
     * @param int $id ID de la compra
     * @return array
     */
    private function detallesOrdenados($id)
    {
        $stmt = $this->conexion->prepare(
            "SELECT idproducto, cantidad FROM {$this->tablaDetalle} WHERE idcompra = :id ORDER BY idproducto ASC"
        );
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca una compra como completada, solo si sigue pendiente (bloquea la fila para
     * que una cancelación concurrente no sea sobrescrita). Orden de bloqueo:
     * compra → productos (por id ascendente).
     *
     * @param int $id ID de la compra
     * @return bool
     */
    public function completar($id)
    {
        try {
            $this->conexion->beginTransaction();

            $stmtLock = $this->conexion->prepare("SELECT estado FROM {$this->tabla} WHERE idcompra = :id FOR UPDATE");
            $stmtLock->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtLock->execute();
            $estadoActual = $stmtLock->fetchColumn();

            if ($estadoActual === false || !self::transicionPermitida($estadoActual, 'completada')) {
                $this->conexion->rollBack();
                $this->lastError = 'Solo se puede completar una compra pendiente.';
                return false;
            }

            // Recepción de la mercadería: aquí entra al inventario
            $stmtProducto = $this->conexion->prepare("SELECT idproducto FROM productos WHERE idproducto = :id FOR UPDATE");
            foreach ($this->detallesOrdenados($id) as $detalle) {
                $stmtProducto->bindValue(':id', (int) $detalle['idproducto'], PDO::PARAM_INT);
                $stmtProducto->execute();
                if ($stmtProducto->fetchColumn() === false) {
                    $this->conexion->rollBack();
                    $this->lastError = 'No se puede completar: uno de los productos de la compra ya no existe.';
                    return false;
                }
                $this->actualizarStockProducto($detalle['idproducto'], $detalle['cantidad']);
            }

            if (!$this->actualizarEstado($id, 'completada')) {
                throw new PDOException("Error al actualizar el estado de la compra");
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cancela una compra; si estaba completada, revierte el stock de productos.
     * Orden de bloqueo: compra → productos (por id ascendente).
     *
     * @param int $id ID de la compra
     * @return bool True si se canceló correctamente, False en caso contrario
     */
    public function cancelar($id)
    {
        try {
            $this->conexion->beginTransaction();

            // Bloquear la fila de la compra para evitar cancelaciones concurrentes duplicadas
            $stmtLock = $this->conexion->prepare("SELECT estado FROM {$this->tabla} WHERE idcompra = :id FOR UPDATE");
            $stmtLock->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtLock->execute();
            $estadoActual = $stmtLock->fetchColumn();

            if ($estadoActual === false || !self::transicionPermitida($estadoActual, 'cancelada')) {
                $this->conexion->rollBack();
                $this->lastError = 'La compra no existe o ya está cancelada.';
                return false;
            }

            // Solo una compra completada movió el inventario; cancelar una pendiente no lo toca.
            // Se bloquea la fila del producto para no dejarlo negativo si esas unidades ya se vendieron
            $stmtStock = $this->conexion->prepare("SELECT nombre, stock FROM productos WHERE idproducto = :id FOR UPDATE");
            $detallesRevertir = $estadoActual === 'completada' ? $this->detallesOrdenados($id) : [];
            foreach ($detallesRevertir as $detalle) {
                $stmtStock->bindValue(':id', (int)$detalle['idproducto'], PDO::PARAM_INT);
                $stmtStock->execute();
                $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);
                if ($producto && (int)$producto['stock'] < (int)$detalle['cantidad']) {
                    $this->conexion->rollBack();
                    $this->lastError = 'No se puede cancelar: el stock actual de "' . $producto['nombre'] . '" es menor a la cantidad comprada (ya se vendieron unidades).';
                    return false;
                }
                $this->actualizarStockProducto($detalle['idproducto'], -$detalle['cantidad']);
            }

            // Actualizar el estado de la compra
            if (!$this->actualizarEstado($id, 'cancelada')) {
                throw new PDOException("Error al actualizar el estado de la compra");
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
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
            error_log('[' . static::class . '] ' . $e->getMessage());
            throw new PDOException("Error al actualizar el stock del producto");
        }
    }

    /**
     * Indicadores del listado: trabajo pendiente y compras del mes en curso. Un
     * no-administrador solo cuenta las suyas. Todo sale de compra/detallecompra/productos.
     *
     * @param int|null $idUsuario Dueño de las compras, o null para todas
     * @return array ['pendientes','monto_pendiente','invertido_mes','compras_mes','producto_top']
     */
    public function getEstadisticas(?int $idUsuario = null): array
    {
        $vacio = [
            'pendientes' => 0,
            'monto_pendiente' => 0.0,
            'invertido_mes' => 0.0,
            'compras_mes' => 0,
            'producto_top' => null,
        ];

        try {
            $filtro = $idUsuario === null ? '' : ' AND c.idusuario = :idusuario';
            // Rango en PHP, no MONTH(): así un índice sobre fechacompra seguiría sirviendo
            $desde = date('Y-m-01');
            $hasta = date('Y-m-t');

            $ejecutar = function (string $sql, array $params = []) use ($idUsuario) {
                $stmt = $this->conexion->prepare($sql);
                foreach ($params as $nombre => $valor) {
                    $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
                }
                if ($idUsuario !== null) {
                    $stmt->bindValue(':idusuario', $idUsuario, PDO::PARAM_INT);
                }
                $stmt->execute();
                return $stmt;
            };

            $pend = $ejecutar(
                "SELECT COUNT(*) AS n, COALESCE(SUM(c.totalcompra), 0) AS monto
                 FROM {$this->tabla} c WHERE c.estado = 'pendiente'{$filtro}"
            )->fetch(PDO::FETCH_ASSOC);

            $mes = $ejecutar(
                "SELECT COALESCE(SUM(CASE WHEN c.estado = 'completada' THEN c.totalcompra END), 0) AS invertido,
                        COALESCE(SUM(CASE WHEN c.estado <> 'cancelada' THEN 1 ELSE 0 END), 0) AS cantidad
                 FROM {$this->tabla} c
                 WHERE c.fechacompra BETWEEN :desde AND :hasta{$filtro}",
                [':desde' => $desde, ':hasta' => $hasta]
            )->fetch(PDO::FETCH_ASSOC);

            $top = $ejecutar(
                "SELECT p.nombre, SUM(dc.cantidad) AS unidades
                 FROM {$this->tabla} c
                 JOIN {$this->tablaDetalle} dc ON dc.idcompra = c.idcompra
                 JOIN productos p ON p.idproducto = dc.idproducto
                 WHERE c.estado = 'completada' AND c.fechacompra BETWEEN :desde AND :hasta{$filtro}
                 GROUP BY p.idproducto, p.nombre
                 ORDER BY unidades DESC, p.nombre ASC
                 LIMIT 1",
                [':desde' => $desde, ':hasta' => $hasta]
            )->fetch(PDO::FETCH_ASSOC);

            return [
                'pendientes' => (int) $pend['n'],
                'monto_pendiente' => (float) $pend['monto'],
                'invertido_mes' => (float) $mes['invertido'],
                'compras_mes' => (int) $mes['cantidad'],
                'producto_top' => $top ?: null,
            ];
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return $vacio;
        }
    }

    /**
     * Obtiene las compras de un usuario específico
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de compras del usuario
     */
    public function getPorUsuario($idUsuario)
    {
        try {
            $query = "SELECT c.*, u.nombre as usuario_nombre 
                      FROM {$this->tabla} c
                      JOIN usuarios u ON c.idusuario = u.idusuario
                      WHERE c.idusuario = :idUsuario
                      ORDER BY c.fechacompra DESC, c.idcompra DESC";
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
     * Valida los datos de la compra antes de crear
     * 
     * @param array $datos Datos de la compra
     * @return array Lista de errores encontrados
     */
    public function validarDatos($datos)
    {
        $errores = [];

        // Validar campos obligatorios
        // totalcompra no se exige: se recalcula en crear() desde los detalles
        $campos_obligatorios = ['idusuario', 'fechacompra', 'estado'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es obligatorio";
            }
        }

        // Validar detalles de la compra
        if (empty($datos['detalles']) || !is_array($datos['detalles']) || count($datos['detalles']) == 0) {
            $errores[] = "La compra debe tener al menos un producto";
        } else {
            $vistos = [];
            foreach ($datos['detalles'] as $detalle) {
                if (isset($vistos[$detalle['idproducto'] ?? 0])) {
                    $errores[] = "Un producto no puede repetirse en el detalle de la compra";
                }
                $vistos[$detalle['idproducto'] ?? 0] = true;
                if (empty($detalle['idproducto'])) {
                    $errores[] = "Todos los productos deben ser válidos";
                }
                if (empty($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    $errores[] = "La cantidad debe ser mayor que cero";
                }
                if (empty($detalle['preciocompra'])) {
                    $errores[] = "Todos los productos deben tener un precio de compra";
                } elseif ($detalle['preciocompra'] < 0) {
                    $errores[] = "El precio de compra no puede ser negativo";
                }
            }
        }

        // Validar estado
        $estadosPermitidos = ['pendiente', 'completada', 'cancelada'];
        if (!in_array($datos['estado'], $estadosPermitidos)) {
            $errores[] = "El estado de la compra no es válido";
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
}

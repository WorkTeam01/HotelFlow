<?php

/**
 * Modelo Pago
 *
 * Folio de huésped: cada fila de `pagos` es una línea de cargo, pago o reverso
 * contra una recepción. Es append-only (nunca se hace UPDATE sobre un monto ya
 * escrito); anular una línea se hace insertando un reverso que la referencia.
 *
 * `recepcion.montototal`/`montopagado` son un cache calculado a partir de esta
 * tabla (ver Recepcion::recalcularFolioDesde()) y solo deben escribirse desde
 * aquí, nunca directamente.
 */

require_once __DIR__ . '/../config/conexion.php';

class Pago
{
    private $conexion;
    private $tabla = 'pagos';
    private $lastError = '';

    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Líneas del folio de una recepción, en orden cronológico.
     *
     * @param int $idrecepcion
     * @return array
     */
    public function getByRecepcion($idrecepcion)
    {
        try {
            $query = "SELECT p.*, u.nombre as nombre_usuario
                      FROM {$this->tabla} p
                      LEFT JOIN usuarios u ON p.idusuario = u.idusuario
                      WHERE p.idrecepcion = :idrecepcion
                      ORDER BY p.fechacreacion ASC, p.id_pago ASC";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }

    /**
     * Calcula cargos/pagos/saldo reales a partir de las líneas del folio.
     * Los reversos restan del tipo de línea que reversan (join contra sí misma).
     *
     * @param int $idrecepcion
     * @param bool $paraUpdate Si true, bloquea las filas (usar dentro de una transacción abierta)
     * @return array ['total_cargos' => float, 'total_pagado' => float, 'saldo' => float]
     */
    public function calcularSaldo($idrecepcion, $paraUpdate = false)
    {
        $query = "SELECT
                COALESCE(SUM(CASE
                    WHEN p.tipo = 'cargo' THEN p.montototal
                    WHEN p.tipo = 'reverso' AND orig.tipo = 'cargo' THEN -p.montototal
                    ELSE 0
                END), 0) AS total_cargos,
                COALESCE(SUM(CASE
                    WHEN p.tipo = 'pago' THEN p.montototal
                    WHEN p.tipo = 'reverso' AND orig.tipo = 'pago' THEN -p.montototal
                    ELSE 0
                END), 0) AS total_pagado
              FROM {$this->tabla} p
              LEFT JOIN {$this->tabla} orig ON orig.id_pago = p.id_pago_reversado
              WHERE p.idrecepcion = :idrecepcion";

        if ($paraUpdate) {
            $query .= " FOR UPDATE";
        }

        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_cargos' => 0, 'total_pagado' => 0];

        $totalCargos = (float)$fila['total_cargos'];
        $totalPagado = (float)$fila['total_pagado'];

        return [
            'total_cargos' => $totalCargos,
            'total_pagado' => $totalPagado,
            'saldo' => $totalCargos - $totalPagado,
        ];
    }

    /**
     * Registra una línea de folio (cargo, pago o reverso) y recalcula el cache
     * en `recepcion` dentro de la misma transacción. Único punto de escritura
     * de `pagos` — no insertar directamente a la tabla desde otro lugar.
     *
     * @param int $idrecepcion
     * @param string $tipo 'cargo'|'pago'|'reverso'
     * @param string $concepto
     * @param float $monto Siempre positivo; el signo lo determina $tipo
     * @param string $metodopago 'Efectivo'|'QR'|'OTROS'
     * @param int $idusuario Usuario que registra la línea
     * @param int|null $idPagoReversado Requerido si $tipo === 'reverso'
     * @return bool|int ID de la línea creada o false si falló
     */
    public function registrarLinea($idrecepcion, $tipo, $concepto, $monto, $metodopago, $idusuario, $idPagoReversado = null)
    {
        if (!in_array($tipo, ['cargo', 'pago', 'reverso'], true)) {
            $this->lastError = 'Tipo de línea de folio no válido.';
            return false;
        }

        if ($monto <= 0) {
            $this->lastError = 'El monto debe ser mayor que cero.';
            return false;
        }

        try {
            $this->conexion->beginTransaction();

            // Bloquear la recepción para evitar folios inconsistentes por escrituras concurrentes
            $stmtRecepcion = $this->conexion->prepare(
                "SELECT idrecepcion, estado FROM recepcion WHERE idrecepcion = :id FOR UPDATE"
            );
            $stmtRecepcion->bindParam(':id', $idrecepcion, PDO::PARAM_INT);
            $stmtRecepcion->execute();
            $recepcion = $stmtRecepcion->fetch(PDO::FETCH_ASSOC);

            if (!$recepcion) {
                $this->conexion->rollBack();
                $this->lastError = 'La recepción indicada no existe.';
                return false;
            }

            if ($recepcion['estado'] === 'cancelado') {
                $this->conexion->rollBack();
                $this->lastError = 'No se pueden registrar movimientos sobre una recepción cancelada.';
                return false;
            }

            $saldoPrevio = $this->calcularSaldo($idrecepcion, true);

            if ($tipo === 'pago' && $monto > $saldoPrevio['saldo'] + 0.01) {
                $this->conexion->rollBack();
                $this->lastError = 'El pago no puede ser mayor al saldo pendiente.';
                return false;
            }

            if ($tipo === 'reverso' && empty($idPagoReversado)) {
                $this->conexion->rollBack();
                $this->lastError = 'Debe indicarse la línea que se está reversando.';
                return false;
            }

            $query = "INSERT INTO {$this->tabla}
                      (idrecepcion, tipo, concepto, montototal, metodopago, idusuario, id_pago_reversado)
                      VALUES (:idrecepcion, :tipo, :concepto, :monto, :metodopago, :idusuario, :idreversado)";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':idrecepcion', $idrecepcion, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':concepto', $concepto, PDO::PARAM_STR);
            $stmt->bindParam(':monto', $monto, PDO::PARAM_STR);
            $stmt->bindParam(':metodopago', $metodopago, PDO::PARAM_STR);
            $stmt->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);

            if ($idPagoReversado) {
                $stmt->bindParam(':idreversado', $idPagoReversado, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':idreversado', null, PDO::PARAM_NULL);
            }

            if (!$stmt->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmt->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $idLinea = $this->conexion->lastInsertId();

            $saldoActual = $this->calcularSaldo($idrecepcion, true);

            $stmtCache = $this->conexion->prepare(
                "UPDATE recepcion SET montototal = :montototal, montopagado = :montopagado WHERE idrecepcion = :id"
            );
            $stmtCache->bindValue(':montototal', $saldoActual['total_cargos'], PDO::PARAM_STR);
            $stmtCache->bindValue(':montopagado', $saldoActual['total_pagado'], PDO::PARAM_STR);
            $stmtCache->bindParam(':id', $idrecepcion, PDO::PARAM_INT);

            if (!$stmtCache->execute()) {
                $this->conexion->rollBack();
                error_log('[' . static::class . '] ' . implode(' ', $stmtCache->errorInfo()));
                $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
                return false;
            }

            $this->conexion->commit();
            return $idLinea;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * @return bool|int
     */
    public function registrarPago($idrecepcion, $monto, $metodopago, $idusuario, $concepto = 'Abono')
    {
        return $this->registrarLinea($idrecepcion, 'pago', $concepto, $monto, $metodopago, $idusuario);
    }

    /**
     * Un cargo no implica un método de pago propio; se guarda 'OTROS' como
     * placeholder porque la columna es NOT NULL y es compartida con las líneas de pago.
     *
     * @return bool|int
     */
    public function registrarCargo($idrecepcion, $monto, $concepto, $idusuario)
    {
        return $this->registrarLinea($idrecepcion, 'cargo', $concepto, $monto, 'OTROS', $idusuario);
    }

    /**
     * Recepciones donde el cache de `recepcion` diverge de la suma real del folio.
     * Uso: verificación manual/administrativa, no se llama en el flujo normal.
     *
     * @return array
     */
    public function verificarConsistencia()
    {
        try {
            $query = "SELECT r.idrecepcion, r.montototal AS cache_montototal, r.montopagado AS cache_montopagado,
                        COALESCE(SUM(CASE
                            WHEN p.tipo = 'cargo' THEN p.montototal
                            WHEN p.tipo = 'reverso' AND orig.tipo = 'cargo' THEN -p.montototal
                            ELSE 0
                        END), 0) AS real_montototal,
                        COALESCE(SUM(CASE
                            WHEN p.tipo = 'pago' THEN p.montototal
                            WHEN p.tipo = 'reverso' AND orig.tipo = 'pago' THEN -p.montototal
                            ELSE 0
                        END), 0) AS real_montopagado
                      FROM recepcion r
                      LEFT JOIN {$this->tabla} p ON p.idrecepcion = r.idrecepcion
                      LEFT JOIN {$this->tabla} orig ON orig.id_pago = p.id_pago_reversado
                      GROUP BY r.idrecepcion, r.montototal, r.montopagado
                      HAVING ABS(r.montototal - real_montototal) > 0.01
                          OR ABS(r.montopagado - real_montopagado) > 0.01";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return [];
        }
    }
}

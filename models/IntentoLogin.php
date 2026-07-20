<?php

/**
 * Modelo IntentoLogin
 *
 * Registra y consulta intentos de login (exitosos y fallidos) para
 * implementar rate-limiting por identificador (correo/documento) e IP.
 *
 * @author Sistema de Alojamiento
 * @version 1.0
 */

// Incluir la clase Conexion
require_once __DIR__ . '/../config/conexion.php';

/**
 * Nota de concurrencia: el conteo de fallos es append-only (solo INSERT + SELECT COUNT,
 * nunca UPDATE de un contador in-place), por lo que no existe condición de lost-update
 * ante inserts concurrentes y no se requiere SELECT ... FOR UPDATE. Dos requests fallidas
 * simultáneas simplemente insertan dos filas independientes; el bloqueo converge en el
 * siguiente conteo de cualquiera de las dos.
 */
class IntentoLogin
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private $conexion;

    /**
     * Tabla de intentos de login en la base de datos
     * @var string
     */
    private $tabla = 'intentos_login';

    /**
     * Último error ocurrido
     * @var string
     */
    private $lastError = '';

    /**
     * Ventana de conteo en minutos
     * @var int
     */
    private $ventanaMinutos;

    /**
     * Máximo de fallos permitidos por identificador dentro de la ventana
     * @var int
     */
    private $maxFallosIdentificador;

    /**
     * Máximo de fallos permitidos por IP dentro de la ventana
     * @var int
     */
    private $maxFallosIp;

    /**
     * Antigüedad (en horas) a partir de la cual un intento se purga
     * @var int
     */
    private $purgaHoras;

    /**
     * Constructor de la clase
     *
     * Los umbrales son configurables vía .env (con los mismos valores
     * por defecto documentados en CLAUDE.md) para poder ajustarlos sin
     * tocar código entre entornos. config/config.php es la única fuente
     * de verdad (mismo patrón que 'app.timezone' en Conexion), no se lee
     * env() directamente aquí.
     */
    public function __construct()
    {
        $this->conexion = Conexion::getInstance()->getConnection();

        $config = require __DIR__ . '/../config/config.php';
        $rateLimit = $config['login_rate_limit'] ?? [];

        $this->ventanaMinutos = (int) ($rateLimit['ventana_minutos'] ?? 15);
        $this->maxFallosIdentificador = (int) ($rateLimit['max_fallos_identificador'] ?? 5);
        $this->maxFallosIp = (int) ($rateLimit['max_fallos_ip'] ?? 20);
        $this->purgaHoras = (int) ($rateLimit['purga_horas'] ?? 24);
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
     * Registra un intento de login
     *
     * @param string $identificador Correo o número de documento ingresado (normalizado)
     * @param string $ip Dirección IP del cliente
     * @param bool $exitoso Si el intento fue exitoso
     * @return bool
     */
    public function registrar($identificador, $ip, $exitoso)
    {
        try {
            $sql = "INSERT INTO {$this->tabla} (identificador, ip, exitoso) VALUES (:identificador, :ip, :exitoso)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':identificador', $this->normalizarIdentificador($identificador));
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':exitoso', $exitoso ? 1 : 0, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Cuenta los fallos recientes de un identificador dentro de la ventana de tiempo
     *
     * @param string $identificador
     * @return int
     */
    public function contarFallosPorIdentificador($identificador)
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tabla}
                    WHERE identificador = :identificador
                    AND exitoso = 0
                    AND intento_en >= (NOW() - INTERVAL :minutos MINUTE)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':identificador', $this->normalizarIdentificador($identificador));
            $stmt->bindValue(':minutos', $this->ventanaMinutos, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return -1;
        }
    }

    /**
     * Cuenta los fallos recientes de una IP dentro de la ventana de tiempo
     *
     * @param string $ip
     * @return int
     */
    public function contarFallosPorIp($ip)
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tabla}
                    WHERE ip = :ip
                    AND exitoso = 0
                    AND intento_en >= (NOW() - INTERVAL :minutos MINUTE)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':minutos', $this->ventanaMinutos, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return -1;
        }
    }

    /**
     * Determina si el identificador o la IP están actualmente bloqueados
     * por exceso de intentos fallidos.
     *
     * Política fail-open: si ocurre un error de BD al consultar, no se
     * bloquea el login (evita DoS por fallo de infraestructura), pero
     * el error ya queda registrado vía error_log() en los métodos de conteo.
     *
     * @param string $identificador
     * @param string $ip
     * @return bool
     */
    public function estaBloqueado($identificador, $ip)
    {
        $fallosIdentificador = $this->contarFallosPorIdentificador($identificador);
        $fallosIp = $this->contarFallosPorIp($ip);

        if ($fallosIdentificador === -1 && $fallosIp === -1) {
            // Fail-open: ambas consultas fallaron, no bloquear.
            return false;
        }

        if ($fallosIdentificador >= $this->maxFallosIdentificador) {
            return true;
        }

        if ($fallosIp >= $this->maxFallosIp) {
            return true;
        }

        return false;
    }

    /**
     * Elimina intentos con más de 24 horas de antigüedad.
     * Pensado para invocarse probabilísticamente (evita necesidad de cron).
     *
     * @return bool
     */
    public function limpiarAntiguos()
    {
        try {
            $sql = "DELETE FROM {$this->tabla} WHERE intento_en < (NOW() - INTERVAL :horas HOUR)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':horas', $this->purgaHoras, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('[' . static::class . '] ' . $e->getMessage());
            $this->lastError = 'Ocurrió un error inesperado. Intente nuevamente.';
            return false;
        }
    }

    /**
     * Normaliza un identificador (trim + lowercase) para conteo consistente
     *
     * @param string $identificador
     * @return string
     */
    private function normalizarIdentificador($identificador)
    {
        return mb_strtolower(trim($identificador));
    }
}

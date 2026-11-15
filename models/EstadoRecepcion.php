<?php

declare(strict_types=1);

/**
 * Ciclo de vida de una recepción (`recepcion.estado`). Única fuente de las
 * transiciones válidas y de qué estados cierran la estancia; los helpers de
 * presentación (`RecepcionController::estadoRecepcion()`) siguen resolviendo
 * etiqueta, color e icono.
 *
 * Los estados derivados (`no_show`, `salida_vencida`) no existen en BD y no están aquí.
 */
enum EstadoRecepcion: string
{
    case Reservado = 'reservado';
    case EnCurso = 'en_curso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    /**
     * Estados a los que se puede pasar desde este. Finalizado y cancelado son finales.
     *
     * @return list<self>
     */
    public function transiciones(): array
    {
        return match ($this) {
            self::Reservado => [self::EnCurso, self::Cancelado],
            self::EnCurso => [self::Finalizado, self::Cancelado],
            self::Finalizado, self::Cancelado => [],
        };
    }

    public function puedePasarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->transiciones(), true);
    }

    /**
     * Una estancia cerrada no se edita ni admite cargos o pagos nuevos (solo reversos).
     */
    public function estaCerrada(): bool
    {
        return $this === self::Finalizado || $this === self::Cancelado;
    }

    /**
     * Destinos que se piden a mano (check-in o cancelación). `finalizado` solo se alcanza
     * por el check-out, que además cobra el saldo.
     */
    public function esDestinoManual(): bool
    {
        return $this === self::EnCurso || $this === self::Cancelado;
    }

    /**
     * Transición válida entre dos valores crudos; un estado desconocido nunca es válida.
     */
    public static function transicionPermitida(?string $actual, ?string $nuevo): bool
    {
        $desde = self::tryFrom((string) $actual);
        $hasta = self::tryFrom((string) $nuevo);

        return $desde !== null && $hasta !== null && $desde->puedePasarA($hasta);
    }

    /**
     * Si el valor crudo corresponde a una estancia cerrada; un valor desconocido no lo es.
     */
    public static function cerrada(?string $estado): bool
    {
        return self::tryFrom((string) $estado)?->estaCerrada() ?? false;
    }

    /**
     * Si el valor crudo es un destino que se puede pedir a mano.
     */
    public static function destinoManual(?string $estado): bool
    {
        return self::tryFrom((string) $estado)?->esDestinoManual() ?? false;
    }

    /**
     * Valores válidos de la columna, para validar entradas y armar listas.
     *
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(static fn(self $e): string => $e->value, self::cases());
    }
}

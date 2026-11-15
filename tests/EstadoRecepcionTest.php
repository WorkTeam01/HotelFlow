<?php

/** Enum EstadoRecepcion: matriz de transiciones y estados cerrados. */

require __DIR__ . '/bootstrap.php';

verificar('valores coinciden con el enum de la columna', ['reservado', 'en_curso', 'finalizado', 'cancelado'], EstadoRecepcion::valores());

$permitidas = [
    'reservado>en_curso', 'reservado>cancelado',
    'en_curso>finalizado', 'en_curso>cancelado',
];
foreach (EstadoRecepcion::valores() as $desde) {
    foreach (EstadoRecepcion::valores() as $hasta) {
        verificar(
            "transición $desde>$hasta",
            in_array("$desde>$hasta", $permitidas, true),
            EstadoRecepcion::transicionPermitida($desde, $hasta)
        );
    }
}
verificar('estado actual desconocido no transiciona', false, EstadoRecepcion::transicionPermitida('no_show', 'en_curso'));
verificar('estado nuevo desconocido no transiciona', false, EstadoRecepcion::transicionPermitida('reservado', 'otro'));
verificar('null no transiciona', false, EstadoRecepcion::transicionPermitida(null, 'en_curso'));

verificar('finalizado está cerrada', true, EstadoRecepcion::cerrada('finalizado'));
verificar('cancelado está cerrada', true, EstadoRecepcion::cerrada('cancelado'));
verificar('en_curso no está cerrada', false, EstadoRecepcion::cerrada('en_curso'));
verificar('reservado no está cerrada', false, EstadoRecepcion::cerrada('reservado'));
verificar('desconocido no está cerrada', false, EstadoRecepcion::cerrada('no_show'));

verificar('check-in es destino manual', true, EstadoRecepcion::destinoManual('en_curso'));
verificar('cancelar es destino manual', true, EstadoRecepcion::destinoManual('cancelado'));
verificar('finalizado no es destino manual (va por check-out)', false, EstadoRecepcion::destinoManual('finalizado'));
verificar('reservado no es destino manual', false, EstadoRecepcion::destinoManual('reservado'));

// Los estados finales no tienen salida
foreach ([EstadoRecepcion::Finalizado, EstadoRecepcion::Cancelado] as $final) {
    verificar("{$final->value} no tiene transiciones", [], $final->transiciones());
}

// El modelo respeta la misma matriz: una validación con estado inválido lo rechaza
$errores = (new Recepcion())->validarDatos(['estado' => 'archivado']);
verificar('validarDatos rechaza un estado fuera del enum', true, in_array('El estado debe ser válido (reservado, en_curso, finalizado, cancelado).', $errores, true));

terminar();

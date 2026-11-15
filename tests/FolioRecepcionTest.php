<?php

/** Folio de recepción: `pagos` como libro mayor y `recepcion.montototal/montopagado` como cache. */

require __DIR__ . '/bootstrap.php';

$pago = new Pago();
$e = escenario();
$id = checkIn($e);

verificar('check-in crea la estancia en_curso', 'en_curso', valor('SELECT estado FROM recepcion WHERE idrecepcion = ?', [$id]));
verificar('check-in ocupa la habitación', 'ocupada', valor('SELECT estado FROM habitaciones WHERE id_habitacion = ?', [$e['hab1']]));
verificar('total sale de la tarifa en BD', 150.0, (float) valor('SELECT montototal FROM recepcion WHERE idrecepcion = ?', [$id]));
verificar('saldo inicial = tarifa', 150.0, $pago->calcularSaldo($id)['saldo']);

$cargo = $pago->registrarCargo($id, 20, 'Minibar', $e['idusuario']);
verificar('registrarCargo devuelve id', true, (bool) $cargo);
verificar('cache montototal recalculado con el cargo', 170.0, (float) valor('SELECT montototal FROM recepcion WHERE idrecepcion = ?', [$id]));

$abono = $pago->registrarPago($id, 100, 'Efectivo', $e['idusuario']);
verificar('registrarPago devuelve id', true, (bool) $abono);
verificar('cache montopagado recalculado', 100.0, (float) valor('SELECT montopagado FROM recepcion WHERE idrecepcion = ?', [$id]));
verificar('saldo = cargos - pagos', 70.0, $pago->calcularSaldo($id)['saldo']);

verificar('pago mayor al saldo se rechaza', false, $pago->registrarPago($id, 70.02, 'Efectivo', $e['idusuario']));
verificar('mensaje del rechazo', 'El pago no puede ser mayor al saldo pendiente.', $pago->getLastError());
verificar('monto 0 se rechaza', false, $pago->registrarPago($id, 0, 'Efectivo', $e['idusuario']));
verificar('reverso sin línea reversada se rechaza', false, $pago->registrarLinea($id, 'reverso', 'x', 10, 'Efectivo', $e['idusuario']));
verificar('tipo inválido se rechaza', false, $pago->registrarLinea($id, 'otro', 'x', 10, 'Efectivo', $e['idusuario']));
verificar('recepción inexistente se rechaza', false, $pago->registrarPago(999999, 10, 'Efectivo', $e['idusuario']));

// Anular = insertar un reverso; el pago original nunca se modifica
$reverso = $pago->registrarLinea($id, 'reverso', 'Anula abono', 100, 'Efectivo', $e['idusuario'], $abono);
verificar('reverso de pago se registra', true, (bool) $reverso);
verificar('el pago original no cambia', '100.00', valor('SELECT montototal FROM pagos WHERE id_pago = ?', [$abono]));
verificar('reverso de pago devuelve el saldo', 170.0, $pago->calcularSaldo($id)['saldo']);
verificar('cache montopagado vuelve a 0', 0.0, (float) valor('SELECT montopagado FROM recepcion WHERE idrecepcion = ?', [$id]));

// Un pago solo se reversa una vez (UNIQUE uq_pagos_reversado)
verificar('doble reverso de la misma línea falla', false, $pago->registrarLinea($id, 'reverso', 'otra vez', 100, 'Efectivo', $e['idusuario'], $abono));

$revCargo = $pago->registrarLinea($id, 'reverso', 'Anula minibar', 20, 'OTROS', $e['idusuario'], $cargo);
verificar('reverso de cargo baja el total', 150.0, $pago->calcularSaldo($id)['total_cargos']);
verificar('cache montototal tras reverso de cargo', 150.0, (float) valor('SELECT montototal FROM recepcion WHERE idrecepcion = ?', [$id]));

verificar('verificarConsistencia no reporta divergencias', 0, count($pago->verificarConsistencia()));

// Una estancia cerrada solo admite reversos
$pago->registrarPago($id, 150, 'QR', $e['idusuario']);
verificar('check-out con saldo cubierto', true, (new Recepcion())->checkout($id, $e['idusuario'])['success']);
verificar('cargo en estancia finalizada se rechaza', false, $pago->registrarCargo($id, 5, 'Tarde', $e['idusuario']));
verificar('pago en estancia finalizada se rechaza', false, $pago->registrarPago($id, 5, 'Efectivo', $e['idusuario']));

terminar();

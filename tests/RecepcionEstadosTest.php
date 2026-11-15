<?php

/** Check-out, transiciones de estado y cambio de habitación de `Recepcion`. */

require __DIR__ . '/bootstrap.php';

$rec = new Recepcion();
$pago = new Pago();
$estadoHab = fn($id) => valor('SELECT estado FROM habitaciones WHERE id_habitacion = ?', [$id]);
$estadoRec = fn($id) => valor('SELECT estado FROM recepcion WHERE idrecepcion = ?', [$id]);

// --- Check-out ---
$e = escenario();
$id = checkIn($e);

$r = $rec->checkout($id, $e['idusuario']);
verificar('check-out con saldo y sin pago final exige pago', true, $r['requiere_pago'] ?? false);
verificar('el saldo informado es el de la tarifa', 150.0, $r['saldo']);
verificar('la estancia sigue en_curso tras el rechazo', 'en_curso', $estadoRec($id));

$r = $rec->checkout($id, $e['idusuario'], ['monto' => 100, 'metodopago' => 'Efectivo']);
verificar('pago final menor al saldo se rechaza', false, $r['success']);
verificar('un rechazo no deja línea de pago', 0, (int) valor("SELECT COUNT(*) FROM pagos WHERE idrecepcion = ? AND tipo = 'pago'", [$id]));

$r = $rec->checkout($id, $e['idusuario'], ['monto' => 150, 'metodopago' => 'Tarjeta']);
verificar('método de pago fuera de la lista blanca se rechaza', false, $r['success']);

$r = $rec->checkout($id, $e['idusuario'], ['monto' => 150, 'metodopago' => 'QR']);
verificar('check-out con pago final', true, $r['success']);
verificar('queda finalizada', 'finalizado', $estadoRec($id));
verificar('la habitación pasa a limpieza', 'limpieza', $estadoHab($e['hab1']));
verificar('el pago final cubre el saldo', 0.0, $pago->calcularSaldo($id)['saldo']);
verificar('registra la fecha de salida', true, valor('SELECT fechasalida FROM recepcion WHERE idrecepcion = ?', [$id]) !== null);
verificar('el cache refleja lo pagado', 150.0, (float) valor('SELECT montopagado FROM recepcion WHERE idrecepcion = ?', [$id]));
verificar('segundo check-out se rechaza', false, $rec->checkout($id, $e['idusuario'])['success']);

// Con el folio ya saldado no hace falta pago final
$e2 = escenario();
$id2 = checkIn($e2);
$pago->registrarPago($id2, 150, 'Efectivo', $e2['idusuario']);
verificar('check-out sin saldo no pide pago', true, $rec->checkout($id2, $e2['idusuario'])['success']);

// --- Transiciones ---
$e3 = escenario();
$id3 = checkIn($e3);
verificar('en_curso no retrocede a reservado', false, $rec->cambiarEstado($id3, 'reservado'));
verificar('mensaje de transición no permitida', 'Transición de estado no permitida.', $rec->getLastError());
verificar('cancelar una en_curso libera la habitación', true, $rec->cambiarEstado($id3, 'cancelado'));
verificar('habitación disponible tras cancelar', 'disponible', $estadoHab($e3['hab1']));
verificar('una cancelada no se reactiva', false, $rec->cambiarEstado($id3, 'en_curso'));
verificar('estado inexistente se rechaza', false, $rec->cambiarEstado(999999, 'finalizado'));

// Reserva futura: no ocupa la habitación hasta el check-in
$e4 = escenario();
$entrada = date('Y-m-d H:i:s', strtotime('+3 days'));
$salida = date('Y-m-d H:i:s', strtotime('+4 days'));
$res = $rec->crear([
    'idcliente' => $e4['idcliente'],
    'idhabitacion' => $e4['hab1'],
    'idtarifa' => $e4['idtarifa'],
    'idusuario' => $e4['idusuario'],
    'estado' => 'reservado',
    'fechaentrada' => $entrada,
    'fechasalida_prevista' => $salida,
]);
verificar('reserva futura creada', true, (bool) $res);
verificar('la reserva no ocupa la habitación', 'disponible', $estadoHab($e4['hab1']));
verificar('reserva solapada se rechaza', false, $rec->crear([
    'idcliente' => $e4['idcliente'],
    'idhabitacion' => $e4['hab1'],
    'idtarifa' => $e4['idtarifa'],
    'idusuario' => $e4['idusuario'],
    'estado' => 'reservado',
    'fechaentrada' => $entrada,
    'fechasalida_prevista' => $salida,
]));
verificar('check-in de la reserva', true, $rec->cambiarEstado($res, 'en_curso'));
verificar('el check-in ocupa la habitación', 'ocupada', $estadoHab($e4['hab1']));

// Tarifa de otro tipo de habitación
$otro = escenario();
verificar('tarifa de otro tipo se rechaza', false, $rec->crear([
    'idcliente' => $e4['idcliente'],
    'idhabitacion' => $e4['hab2'],
    'idtarifa' => $otro['idtarifa'],
    'idusuario' => $e4['idusuario'],
    'estado' => 'en_curso',
    'fechaentrada' => date('Y-m-d H:i:s'),
    'fechasalida_prevista' => date('Y-m-d H:i:s', strtotime('+1 day')),
]));

// El monto del cliente se ignora: manda la tarifa
$e5 = escenario();
$id5 = checkIn($e5, null, ['montototal' => 1, 'montopagado' => 999, 'metodopago' => 'QR']);
verificar('montototal enviado por el cliente se ignora', 150.0, (float) valor('SELECT montototal FROM recepcion WHERE idrecepcion = ?', [$id5]));
verificar('cobro en QR registra el total como pagado', 150.0, (float) valor('SELECT montopagado FROM recepcion WHERE idrecepcion = ?', [$id5]));

// --- Cambio de habitación ---
$e6 = escenario();
$id6 = checkIn($e6);
verificar('cambio a la misma habitación se rechaza', false, $rec->cambiarHabitacion($id6, $e6['hab1'], $e6['idusuario']));
verificar('cambio a una más cara', true, $rec->cambiarHabitacion($id6, $e6['hab2'], $e6['idusuario'], 'Pidió más espacio'));
verificar('la estancia apunta a la nueva habitación', $e6['hab2'], (int) valor('SELECT idhabitacion FROM recepcion WHERE idrecepcion = ?', [$id6]));
verificar('origen pasa a limpieza', 'limpieza', $estadoHab($e6['hab1']));
verificar('destino queda ocupada', 'ocupada', $estadoHab($e6['hab2']));
verificar('deja movimiento', 1, (int) valor("SELECT COUNT(*) FROM recepcion_movimientos WHERE idrecepcion = ? AND tipo = 'cambio_habitacion'", [$id6]));
verificar('carga la diferencia de precio_base (180-100)', 230.0, $pago->calcularSaldo($id6)['total_cargos']);
verificar('el cache de montototal refleja el cargo', 230.0, (float) valor('SELECT montototal FROM recepcion WHERE idrecepcion = ?', [$id6]));

// Volver a la barata no carga nada y destino ocupado se rechaza
verificar('destino no disponible (origen en limpieza) se rechaza', false, $rec->cambiarHabitacion($id6, $e6['hab1'], $e6['idusuario']));
$e7 = escenario();
$id7 = checkIn($e7, $e7['hab2']);
verificar('cambio a una más barata', true, $rec->cambiarHabitacion($id7, $e7['hab1'], $e7['idusuario']));
verificar('sin cargo extra al abaratar', 150.0, $pago->calcularSaldo($id7)['total_cargos']);
verificar('solo una estancia en_curso cambia de habitación', false, $rec->cambiarHabitacion($id3, $e3['hab2'], $e3['idusuario']));

terminar();

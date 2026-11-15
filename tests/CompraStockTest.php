<?php

/** Compras: el stock entra al completar (no al crear) y el total se calcula en el servidor. */

require __DIR__ . '/bootstrap.php';

$compra = new Compra();
$stock = fn($id) => (int) valor('SELECT stock FROM productos WHERE idproducto = ?', [$id]);
$estado = fn($id) => valor('SELECT estado FROM compra WHERE idcompra = ?', [$id]);

$e = escenario();
$p = $e['idproducto']; // stock inicial 10
$datos = fn(string $estadoInicial, array $detalles, $total = 0) => [
    'idusuario' => $e['idusuario'],
    'totalcompra' => $total,
    'fechacompra' => date('Y-m-d'),
    'estado' => $estadoInicial,
    'detalles' => $detalles,
];

// Pendiente: no toca el inventario; el total enviado por el cliente se ignora
$id = $compra->crear($datos('pendiente', [['idproducto' => $p, 'cantidad' => 5, 'preciocompra' => '2.50']], 1));
verificar('compra pendiente creada', true, (bool) $id);
verificar('el total sale de las líneas, no del cliente', 12.5, (float) valor('SELECT totalcompra FROM compra WHERE idcompra = ?', [$id]));
verificar('pendiente no suma stock', 10, $stock($p));

verificar('completar suma el stock', true, $compra->completar($id));
verificar('stock tras completar', 15, $stock($p));
verificar('estado completada', 'completada', $estado($id));
verificar('no se completa dos veces', false, $compra->completar($id));
verificar('el stock no se duplica', 15, $stock($p));

verificar('cancelar una completada revierte el stock', true, $compra->cancelar($id));
verificar('stock tras cancelar', 10, $stock($p));
verificar('estado cancelada', 'cancelada', $estado($id));
verificar('no se cancela dos veces', false, $compra->cancelar($id));
verificar('una cancelada no se completa', false, $compra->completar($id));
verificar('el stock sigue igual', 10, $stock($p));

// Cancelar una pendiente no toca el inventario
$id2 = $compra->crear($datos('pendiente', [['idproducto' => $p, 'cantidad' => 3, 'preciocompra' => '1.00']]));
verificar('cancelar pendiente', true, $compra->cancelar($id2));
verificar('cancelar pendiente no cambia el stock', 10, $stock($p));

// Creada ya completada: entra al inventario de inmediato
$id3 = $compra->crear($datos('completada', [['idproducto' => $p, 'cantidad' => 4, 'preciocompra' => '3.00']]));
verificar('compra completada al crear suma stock', 14, $stock($p));

// Si ya se vendieron unidades, cancelar dejaría el stock negativo
$pdo->prepare('UPDATE productos SET stock = 2 WHERE idproducto = ?')->execute([$p]);
verificar('cancelar con stock insuficiente se rechaza', false, $compra->cancelar($id3));
verificar('no cambia el stock al rechazar', 2, $stock($p));
verificar('sigue completada', 'completada', $estado($id3));
verificar('el rechazo no deja una transacción abierta', false, $pdo->inTransaction());

// Validaciones de creación
$pdo->prepare('UPDATE productos SET stock = 10 WHERE idproducto = ?')->execute([$p]);
$antes = (int) valor('SELECT COUNT(*) FROM compra');
verificar('producto inexistente se rechaza', false, $compra->crear($datos('pendiente', [['idproducto' => 999999, 'cantidad' => 1, 'preciocompra' => '1']])));
verificar('no queda cabecera huérfana', $antes, (int) valor('SELECT COUNT(*) FROM compra'));
$pdo->prepare('UPDATE productos SET estado = 0 WHERE idproducto = ?')->execute([$p]);
verificar('producto inactivo se rechaza', false, $compra->crear($datos('pendiente', [['idproducto' => $p, 'cantidad' => 1, 'preciocompra' => '1']])));
verificar('mensaje del rechazo', 'Hay productos inactivos o inexistentes en la compra.', $compra->getLastError());

// El rechazo de productos repetidos vive en validarDatos(), que el controlador llama antes de crear()
$repetido = $datos('pendiente', [
    ['idproducto' => $p, 'cantidad' => 1, 'preciocompra' => '1'],
    ['idproducto' => $p, 'cantidad' => 1, 'preciocompra' => '1'],
]);
verificar('validarDatos rechaza productos repetidos', true, in_array('Un producto no puede repetirse en el detalle de la compra', $compra->validarDatos($repetido), true));

// Total en centavos enteros: 0.1 + 0.2 no deriva
$pdo->prepare('UPDATE productos SET estado = 1 WHERE idproducto = ?')->execute([$p]);
$otro = escenario()['idproducto'];
$id4 = $compra->crear($datos('pendiente', [
    ['idproducto' => $p, 'cantidad' => 1, 'preciocompra' => '0.10'],
    ['idproducto' => $otro, 'cantidad' => 2, 'preciocompra' => '0.10'],
]));
verificar('total sin deriva de coma flotante', 0.3, (float) valor('SELECT totalcompra FROM compra WHERE idcompra = ?', [$id4]));

terminar();

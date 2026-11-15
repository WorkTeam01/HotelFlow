<?php

/**
 * Arranque común de los tests de caracterización: apunta la app a `db_hotel_flow_test`
 * (nunca a la BD real) y expone fixtures y un verificador mínimo, sin dependencias.
 * Prepara la BD con `php tests/preparar-bd.php`.
 */

const BD_TEST = 'db_hotel_flow_test';

require_once __DIR__ . '/../config/env.php';

// env() lee getenv() en cada llamada, así que estos cambios llegan a Conexion.
putenv('DB_NAME=' . BD_TEST);
if (getenv('TEST_DB_HOST') !== false) {
    putenv('DB_HOST=' . getenv('TEST_DB_HOST'));
}
if (getenv('TEST_DB_USER') !== false) {
    putenv('DB_USER=' . getenv('TEST_DB_USER'));
}
if (getenv('TEST_DB_PASS') !== false) {
    putenv('DB_PASS=' . getenv('TEST_DB_PASS'));
}

require_once __DIR__ . '/../config/conexion.php';
foreach (['Pago', 'Recepcion', 'Compra'] as $modelo) {
    require_once __DIR__ . "/../models/$modelo.php";
}

$pdo = Conexion::getInstance()->getConnection();
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== BD_TEST) {
    fwrite(STDERR, 'La conexión no apunta a ' . BD_TEST . "; se aborta.\n");
    exit(2);
}
if (!$pdo->query("SHOW TABLES LIKE 'pagos'")->fetchColumn()) {
    fwrite(STDERR, "La BD de pruebas no tiene esquema; ejecuta: php tests/preparar-bd.php\n");
    exit(2);
}

$GLOBALS['fallos_test'] = [];

/** Sin tipos a propósito: compara strings, enteros, floats y arreglos. */
function verificar(string $nombre, $esperado, $real): void
{
    $ok = (is_float($esperado) || is_float($real))
        ? abs((float) $esperado - (float) $real) < 0.0001
        : $esperado === $real;
    $fmt = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE);
    echo ($ok ? 'OK    ' : 'FALLA ') . $nombre . ($ok ? '' : ': esperado ' . $fmt($esperado) . ', real ' . $fmt($real)) . "\n";
    if (!$ok) {
        $GLOBALS['fallos_test'][] = $nombre;
    }
}

function terminar(): void
{
    $n = count($GLOBALS['fallos_test']);
    echo $n === 0 ? "Todo OK\n" : "$n fallo(s)\n";
    exit($n === 0 ? 0 : 1);
}

/** Inserta una fila y devuelve su id. */
function insertar(string $tabla, array $fila): int
{
    global $pdo;
    $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($fila)));
    $marcas = implode(',', array_fill(0, count($fila), '?'));
    $pdo->prepare("INSERT INTO `$tabla` ($cols) VALUES ($marcas)")->execute(array_values($fila));
    return (int) $pdo->lastInsertId();
}

function valor(string $sql, array $params = [])
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

/**
 * Escenario mínimo: usuario, huésped, piso, tipo con tarifa de Bs 150, dos habitaciones
 * (precio_base 100 y 180) y un producto activo con stock 10. Los datos llevan un sufijo
 * único para que varios tests convivan en la misma BD sin chocar con claves únicas.
 */
function escenario(): array
{
    $u = bin2hex(random_bytes(4)); // 8 caracteres: `habitaciones.numero` admite 10
    $idusuario = insertar('usuarios', [
        'nombre' => "T$u",
        'apellidop' => 'Test',
        'tipodocumento' => 'CI',
        'numdocumento' => "U$u",
        'correo' => "t$u@test.local",
        'cargo' => 'Administrador',
        'clave' => 'x',
    ]);
    $idcliente = insertar('persona', [
        'nombre' => "C$u",
        'apellidopaterno' => 'Test',
        'tipodocumento' => 'CI',
        'numdocumento' => "P$u",
    ]);
    $idpiso = insertar('pisos', ['nombre' => "P$u"]);
    $idtipo = insertar('tipo_habitacion', ['nombre' => "T$u"]);
    $idtarifa = insertar('tarifas', ['id_tipo' => $idtipo, 'tipo_estancia' => 'dias', 'duracion' => 1, 'precio' => '150.00']);
    $hab1 = insertar('habitaciones', ['numero' => "A$u", 'id_tipo' => $idtipo, 'idpiso' => $idpiso, 'precio_base' => '100.00', 'estado' => 'disponible']);
    $hab2 = insertar('habitaciones', ['numero' => "B$u", 'id_tipo' => $idtipo, 'idpiso' => $idpiso, 'precio_base' => '180.00', 'estado' => 'disponible']);
    $idcat = insertar('categoria', ['nombre' => "K$u"]);
    $idproducto = insertar('productos', [
        'idcategoria' => $idcat,
        'codigo' => "S$u",
        'nombre' => "Prod$u",
        'stock' => 10,
        'precioventa' => '5.00',
        'preciocompra' => '3.00',
    ]);

    return compact('idusuario', 'idcliente', 'idtipo', 'idtarifa', 'hab1', 'hab2', 'idproducto');
}

/** Crea un check-in inmediato (en_curso) por el modelo, con la tarifa de Bs 150 y sin cobro inicial. */
function checkIn(array $e, ?int $hab = null, array $extra = []): int
{
    $id = (new Recepcion())->crear($extra + [
        'idcliente' => $e['idcliente'],
        'idhabitacion' => $hab ?? $e['hab1'],
        'idtarifa' => $e['idtarifa'],
        'idusuario' => $e['idusuario'],
        'estado' => 'en_curso',
        'fechaentrada' => date('Y-m-d H:i:s'),
        'fechasalida_prevista' => date('Y-m-d H:i:s', strtotime('+1 day')),
    ]);
    return (int) $id;
}

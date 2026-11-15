<?php

/**
 * Recrea `db_hotel_flow_test` desde database/db_hotel_flow.sql (con sus triggers).
 * Nunca toca la BD real: el nombre está fijo aquí.
 *
 * Uso: php tests/preparar-bd.php
 * Credenciales: TEST_DB_HOST / TEST_DB_USER / TEST_DB_PASS, o las de `.env` si no se definen.
 */

const BD_TEST = 'db_hotel_flow_test';

require_once __DIR__ . '/../config/env.php';

$host = getenv('TEST_DB_HOST') ?: env('DB_HOST');
$user = getenv('TEST_DB_USER') ?: env('DB_USER');
$pass = getenv('TEST_DB_PASS');
if ($pass === false) {
    $pass = (string) env('DB_PASS', '');
}

$pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('DROP DATABASE IF EXISTS `' . BD_TEST . '`');
$pdo->exec('CREATE DATABASE `' . BD_TEST . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
unset($pdo);

// El dump usa DELIMITER (triggers): solo el cliente mysql lo entiende, PDO no.
$cmd = sprintf(
    'mysql -h %s -u %s %s < %s 2>&1',
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg(BD_TEST),
    escapeshellarg(__DIR__ . '/../database/db_hotel_flow.sql')
);
$salida = [];
$codigo = 0;
putenv('MYSQL_PWD=' . $pass);
exec($cmd, $salida, $codigo);
if ($codigo !== 0) {
    fwrite(STDERR, implode("\n", $salida) . "\n");
    exit(1);
}
echo 'BD ' . BD_TEST . " lista.\n";

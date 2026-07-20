<?php

/**
 * Archivo de configuración principal
 * 
 * Contiene la configuración global de la aplicación, incluyendo
 * parámetros de conexión a la base de datos y configuración general.
 * 
 * @author Sistema de Alojamiento
 * @version 1.0
 */

require_once __DIR__ . '/env.php';

// Configurar zona horaria con valor predeterminado si no está definida
// Única fuente de verdad para la zona horaria de la app: config/conexion.php la reutiliza desde aquí.
$timezone = env('TIMEZONE', 'America/La_Paz');
date_default_timezone_set($timezone);

return [
    'database' => [
        'host' => env('DB_HOST'),
        'name' => env('DB_NAME'),
        'user' => env('DB_USER'),
        'pass' => env('DB_PASS'),
        'charset' => env('DB_CHARSET')
    ],
    'app' => [
        'timezone' => $timezone,
        'name' => 'Sistema de Alojamiento',
        'url' => env('APP_URL'),
        'debug' => env('DEBUG')
    ],
    'login_rate_limit' => [
        'ventana_minutos' => (int) env('LOGIN_RATE_LIMIT_VENTANA_MINUTOS', 15),
        'max_fallos_identificador' => (int) env('LOGIN_RATE_LIMIT_MAX_FALLOS_IDENTIFICADOR', 5),
        'max_fallos_ip' => (int) env('LOGIN_RATE_LIMIT_MAX_FALLOS_IP', 20),
        'purga_horas' => (int) env('LOGIN_RATE_LIMIT_PURGA_HORAS', 24)
    ]
];

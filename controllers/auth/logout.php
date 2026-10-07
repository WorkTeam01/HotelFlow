<?php

/**
 * Procesador de Logout
 * 
 * Este archivo procesa el cierre de sesión
 * 
 * @author Sistema de Alojamiento
 * @version 1.5
 */

// La sesión la inicia session.php (vía AuthController) con cookie HttpOnly/SameSite
// Incluir el controlador de autenticación
require_once __DIR__ . '/AuthController.php';

// Crear instancia del controlador
$authController = new AuthController();

// Procesar logout
$authController->logout();

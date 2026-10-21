<?php
/**
 * App Médica — Configuración general
 * Ajusta estos valores a tu servidor local (XAMPP / Laragon / WAMP).
 */

// Conexión a MySQL / MariaDB
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'app_medica_citas');
define('DB_USER', 'root');
define('DB_PASS', '');            // En XAMPP la contraseña de root viene vacía

// Zona horaria usada para "hoy", horas de cita y notificaciones
define('APP_TIMEZONE', 'America/Mexico_City');

// Nombre visible de la aplicación
define('APP_NOMBRE', 'App Médica');

// Existencias a partir de las cuales un medicamento se marca como "Stock bajo"
// (la tabla medicamentos no tiene columna de stock mínimo)
define('STOCK_MINIMO', 20);

// Cantidad que se pide al proveedor con el botón "Reordenar"
define('CANTIDAD_REORDEN', 50);

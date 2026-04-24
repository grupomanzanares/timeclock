<?php
declare(strict_types=1);

define('APP_NAME',    'TimeClock');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
define('APP_DEBUG',   APP_ENV !== 'production');
define('APP_TIMEZONE','America/Bogota');

// URL base dinámica
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base     = rtrim($script === '/' ? '' : $script, '/');
// Sube dos niveles si estamos dentro de una subcarpeta api/ o views/
$depth = substr_count(trim($base, '/'), '/');
if ($depth > 0) {
    $parts = explode('/', trim($base, '/'));
    array_pop($parts);
    $base = '/' . implode('/', $parts);
}
define('BASE_URL', $protocol . '://' . $host . $base);

// Roles disponibles
define('ROLES', ['admin', 'supervisor', 'empleado']);

// Días semana (Colombia, semana Dom-Sab)
define('DIAS_SEMANA', [
    0 => 'Domingo',
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
]);

<?php
declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('APP_START', microtime(true));

// Zona horaria Colombia
date_default_timezone_set('America/Bogota');

// Carga automática de clases del core
spl_autoload_register(function (string $class): void {
    $file = ROOT_PATH . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

// Sesión segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Headers de seguridad
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

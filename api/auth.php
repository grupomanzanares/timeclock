<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Método no permitido', 405);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $cedula   = trim($body['cedula'] ?? '');
        $password = $body['password'] ?? '';

        if (!$cedula || !$password) {
            Response::error('Cédula y contraseña requeridas');
        }

        // Rate limiting básico por IP
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = 'login_attempts_' . md5($ip);
        if (!isset($_SESSION[$key])) $_SESSION[$key] = ['count' => 0, 'ts' => time()];
        if (time() - $_SESSION[$key]['ts'] > 300) {
            $_SESSION[$key] = ['count' => 0, 'ts' => time()];
        }
        if ($_SESSION[$key]['count'] >= 5) {
            Response::error('Demasiados intentos. Espere 5 minutos.', 429);
        }

        if (!Auth::login($cedula, $password)) {
            $_SESSION[$key]['count']++;
            Response::error('Credenciales inválidas', 401);
        }

        $_SESSION[$key] = ['count' => 0, 'ts' => time()];

        // Cierre automático de turnos pendientes
        $cerradas = Turno::cerrarPendientes(Auth::id());

        Response::success([
            'user'           => Auth::user(),
            'dias_cerrados'  => $cerradas,
            'redirect'       => BASE_URL . '/index.php',
        ], 'Bienvenido');
        break;

    case 'logout':
        Auth::logout();
        Response::success(null, 'Sesión cerrada');
        break;

    case 'check':
        if (Auth::check()) {
            Response::success(['user' => Auth::user()]);
        } else {
            Response::error('No autenticado', 401);
        }
        break;

    default:
        Response::error('Acción no válida', 404);
}

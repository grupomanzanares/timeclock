<?php
declare(strict_types=1);

class Auth
{
    public static function login(string $cedula, string $password): bool
    {
        $user = DB::fetchOne(
            'SELECT u.*, c.nombre AS cargo_nombre, s.nombre AS sede_nombre
             FROM usuarios u
             LEFT JOIN cargos c ON c.id = u.cargo_id
             LEFT JOIN sedes s ON s.id = u.sede_id
             WHERE u.cedula = ? AND u.activo = 1',
            [$cedula]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::warning("Intento de login fallido cedula: $cedula");
            return false;
        }

        // Regenerar sesión para prevenir fijación de sesión
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'              => $user['id'],
            'nombre'          => $user['nombre'] . ' ' . $user['apellido'],
            'email'           => $user['email'],
            'rol'             => $user['rol'],
            'cargo_id'        => $user['cargo_id'],
            'cargo_nombre'    => $user['cargo_nombre'],
            'sede_id'         => $user['sede_id'],
            'sede_nombre'     => $user['sede_nombre'],
            'equipo_permitido'=> $user['equipo_permitido'],
            'login_at'        => time(),
        ];

        Logger::info("Login exitoso: {$user['email']} (ID {$user['id']})");
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    public static function rol(): string
    {
        return $_SESSION['user']['rol'] ?? '';
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (self::isAjax()) {
                Response::error('No autenticado', 401);
                exit;
            }
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            if (self::isAjax()) {
                Response::error('Sin permiso', 403);
                exit;
            }
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }

    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

<?php
declare(strict_types=1);

class Helpers
{
    /** Sanitiza un string (XSS básico) */
    public static function clean(string $str): string
    {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    /** Obtiene el nombre del equipo (hostname) del cliente */
    public static function getClientHostname(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$ip) return '';
        $host = @gethostbyaddr($ip);
        return $host && $host !== $ip ? strtolower($host) : strtolower($ip);
    }

    /** Verifica si el equipo del cliente está autorizado */
    public static function equipoAutorizado(int $cargoId, int $sedeId): bool
    {
        $hostname = self::getClientHostname();

        // Buscar restricciones para el cargo
        $rows = DB::fetchAll(
            'SELECT nombre_equipo FROM equipos_autorizados
             WHERE cargo_id = ? AND activo = 1 AND (sede_id IS NULL OR sede_id = ?)',
            [$cargoId, $sedeId]
        );

        // Si no hay restricciones registradas => acceso libre
        if (empty($rows)) return true;

        foreach ($rows as $row) {
            if (strtolower($row['nombre_equipo']) === $hostname) return true;
        }
        return false;
    }

    /** Diferencia en minutos entre dos objetos DateTime */
    public static function diffMinutos(\DateTime $desde, \DateTime $hasta): int
    {
        return (int)(($hasta->getTimestamp() - $desde->getTimestamp()) / 60);
    }

    /** Nombre del día de la semana en español */
    public static function diaSemana(string $fecha): string
    {
        $dow = (int)date('w', strtotime($fecha));
        return DIAS_SEMANA[$dow];
    }

    /** Devuelve TRUE si la fecha es festivo en Colombia */
    public static function esFestivo(string $fecha): bool
    {
        $row = DB::fetchOne('SELECT id FROM festivos WHERE fecha = ?', [$fecha]);
        return $row !== null;
    }

    /** Genera token CSRF y lo guarda en sesión */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Valida token CSRF */
    public static function validateCsrf(string $token): bool
    {
        return !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Devuelve el parámetro global o por cargo desde la tabla parametros */
    public static function getParam(string $clave, ?int $cargoId = null): ?string
    {
        // Primero busca específico del cargo
        if ($cargoId) {
            $row = DB::fetchOne(
                'SELECT valor FROM parametros WHERE clave = ? AND cargo_id = ?',
                [$clave, $cargoId]
            );
            if ($row) return $row['valor'];
        }
        // Global (cargo_id IS NULL)
        $row = DB::fetchOne(
            'SELECT valor FROM parametros WHERE clave = ? AND cargo_id IS NULL',
            [$clave]
        );
        return $row ? $row['valor'] : null;
    }
}

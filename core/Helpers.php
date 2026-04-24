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
        $hostname  = self::getClientHostname();
        // Nombre corto: "pc001.oficina.local" → "pc001"
        $shortName = strtolower(explode('.', $hostname)[0]);

        // Buscar restricciones para el cargo (sede específica o todas las sedes)
        $rows = DB::fetchAll(
            'SELECT nombre_equipo FROM equipos_autorizados
             WHERE cargo_id = ? AND activo = 1 AND (sede_id IS NULL OR sede_id = ?)',
            [$cargoId, $sedeId]
        );

        // Si no hay restricciones registradas => acceso libre
        if (empty($rows)) return true;

        foreach ($rows as $row) {
            $cfg = strtolower(trim($row['nombre_equipo']));
            // Comparación exacta, por nombre corto o por IP directa
            if ($cfg === $hostname || $cfg === $shortName) return true;
            // Configurado como "pc001" y detectado "pc001.dominio.local"
            if (str_starts_with($hostname, $cfg . '.')) return true;
        }
        return false;
    }

    /** Devuelve el hostname detectado del cliente (útil para diagnóstico) */
    public static function getClientHostnameDetectado(): string
    {
        return self::getClientHostname();
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
        // Primero: festivos oficiales calculados algorítmicamente (sin BD)
        if (FestivosCol::esFestivo($fecha)) {
            return true;
        }
        // Segundo: fechas especiales adicionales guardadas por el admin en BD
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

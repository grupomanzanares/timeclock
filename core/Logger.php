<?php
declare(strict_types=1);

class Logger
{
    private static string $logDir = '';

    private static function dir(): string
    {
        if (!self::$logDir) {
            self::$logDir = ROOT_PATH . '/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0750, true);
            }
        }
        return self::$logDir;
    }

    public static function write(string $level, string $message): void
    {
        $line = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );
        $file = self::dir() . '/' . date('Y-m') . '.log';
        error_log($line, 3, $file);
    }

    public static function info(string $msg): void    { self::write('info', $msg); }
    public static function warning(string $msg): void { self::write('warning', $msg); }
    public static function error(string $msg): void   { self::write('error', $msg); }
    public static function debug(string $msg): void   { if (APP_DEBUG) self::write('debug', $msg); }
}

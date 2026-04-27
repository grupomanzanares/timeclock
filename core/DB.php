<?php
declare(strict_types=1);

class DB
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='-05:00'",
                ]);
            } catch (PDOException $e) {
                Response::error('Error de conexión a base de datos', 503);
                exit;
            }
        }
        return self::$instance;
    }

    /** Ejecuta y devuelve todos los registros */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Ejecuta y devuelve el primer registro */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Ejecuta una sentencia (INSERT / UPDATE / DELETE) y devuelve filas afectadas */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT y retorna el último ID insertado */
    public static function insert(string $sql, array $params = []): string
    {
        self::execute($sql, $params);
        return self::get()->lastInsertId();
    }

    public static function beginTransaction(): void { self::get()->beginTransaction(); }
    public static function commit(): void           { self::get()->commit(); }
    public static function rollback(): void         { self::get()->rollBack(); }
}

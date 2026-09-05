<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $name = getenv('DB_NAME') ?: 'skillmap';
            $user = getenv('DB_USER') ?: 'skillmap';
            $pass = getenv('DB_PASS') ?: '';

            self::$pdo = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // プリペアドステートメントを DB 側で処理させる
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return self::$pdo;
    }
}

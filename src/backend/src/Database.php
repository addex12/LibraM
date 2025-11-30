<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $driver = getenv('DB_CONNECTION') ?: 'sqlite';
            switch ($driver) {
                case 'sqlite':
                    $path = getenv('DB_DATABASE') ?: 'storage/library.db';
                    $absolute = self::resolvePath($path);
                    self::ensureDirectory(dirname($absolute));
                    $dsn = 'sqlite:' . $absolute;
                    self::$connection = new PDO($dsn);
                    break;
                case 'mysql':
                    $host = getenv('DB_HOST') ?: '127.0.0.1';
                    $db = getenv('DB_DATABASE') ?: 'library';
                    $user = getenv('DB_USERNAME') ?: 'root';
                    $pass = getenv('DB_PASSWORD') ?: '';
                    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
                    self::$connection = new PDO($dsn, $user, $pass);
                    break;
                default:
                    throw new RuntimeException('Unsupported database driver: ' . $driver);
            }
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$connection;
    }

    private static function resolvePath(string $path): string
    {
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . $path;
    }

    private static function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Unable to create directory: ' . $directory);
            }
        }
    }
}

<?php
declare(strict_types=1);

namespace Config;

require_once __DIR__ . '/Environment.php';

Environment::load();

/**
 * Simple PDO singleton. Reads connection settings from environment
 * variables (falls back to sensible local-dev defaults) so the same
 * code works on localhost, XAMPP, or a shared host.
 */
class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: 'jbtravels';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASSWORD') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $opts = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$pdo = new \PDO($dsn, $user, $pass, $opts);
            } catch (\PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
        }

        return self::$pdo;
    }
}
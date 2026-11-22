<?php
declare(strict_types=1);

namespace Config;

require_once __DIR__ . '/Environment.php';

Environment::load();

/**
 * PDO singleton. Reads credentials from environment variables or an
 * untracked database.local.php file for shared hosts.
 */
class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $localConfigFile = __DIR__ . '/database.local.php';
            $localConfig = is_file($localConfigFile) ? require $localConfigFile : [];
            if (!is_array($localConfig)) {
                throw new \RuntimeException('config/database.local.php must return an array.');
            }

            $host = getenv('DB_HOST') ?: ($localConfig['host'] ?? '127.0.0.1');
            $db   = getenv('DB_NAME') ?: ($localConfig['database'] ?? 'jbtravels');
            $user = getenv('DB_USER') ?: ($localConfig['username'] ?? 'root');
            $pass = getenv('DB_PASSWORD') ?: ($localConfig['password'] ?? '');
            $port = getenv('DB_PORT') ?: ($localConfig['port'] ?? '');

            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            if ($port !== '') {
                if (!ctype_digit((string)$port)) {
                    throw new \RuntimeException('DB_PORT must be a number.');
                }
                $dsn .= ';port=' . (int)$port;
            }
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
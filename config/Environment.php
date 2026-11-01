<?php
declare(strict_types=1);

namespace Config;

final class Environment
{
    public static function load(): void
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Unable to read the application .env file.');
        }

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                throw new \RuntimeException('Invalid .env entry on line ' . ($index + 1) . '.');
            }

            $name = $matches[1];
            if (getenv($name) !== false) {
                continue;
            }

            $value = trim($matches[2]);
            if (strlen($value) >= 2) {
                $quote = $value[0];
                if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
                    $value = substr($value, 1, -1);
                }
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

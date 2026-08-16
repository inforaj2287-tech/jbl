<?php
/**
 * config/bootstrap.php
 * Moved from root config.php — provides small helpers used by legacy handlers.
 */
declare(strict_types=1);

define('DATA_DIR', __DIR__ . '/../data');
define('BOOKINGS_FILE', DATA_DIR . '/bookings.json');
define('CONTACTS_FILE', DATA_DIR . '/contacts.json');

function ensure_data_dir(): void {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
}

function read_json_file(string $path): array {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function append_json_record(string $path, array $record): bool {
    ensure_data_dir();
    $records = read_json_file($path);
    $records[] = $record;
    return file_put_contents($path, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function clean(string $value): string {
    return trim(strip_tags($value));
}

function json_response(bool $success, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function cab_rate(string $cabType): float {
    $rates = ['hatchback' => 9.50, 'sedan' => 10.50, 'suv' => 16.00];
    return $rates[$cabType] ?? $rates['hatchback'];
}

/* Notes: this bootstrap file is intentionally lightweight for the current demo.
   For the full implementation use PDO and the services in /config/Database.php */

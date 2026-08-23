<?php
/**
 * config/bootstrap.php
 * Small shared helpers used by the public-facing AJAX handlers
 * (api/contact.php, api/booking.php).
 */
declare(strict_types=1);

define('DATA_DIR', __DIR__ . '/../data');
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
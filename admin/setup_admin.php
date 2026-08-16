<?php
/**
 * admin/setup_admin.php
 * Create a development admin user. Localhost-only script.
 */
declare(strict_types=1);

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

require_once __DIR__ . '/../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();
    // ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(150) DEFAULT 'Administrator',
        role VARCHAR(50) DEFAULT 'admin',
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $email = 'admin@example.com';
    $password = bin2hex(random_bytes(3)); // short random password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Admin already exists: {$email}\n";
        exit;
    }

    $ins = $pdo->prepare('INSERT INTO admins (email, password, name) VALUES (?,?,?)');
    $ins->execute([$email, $hash, 'Development Admin']);

    echo "Created admin:\nEmail: {$email}\nPassword: {$password}\nPlease change the password after first login. (This page is localhost-only.)\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}

<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
// Simplified password storage: store single hashed password in data/admin_password.hash
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$pass = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
if ($pass === '' || $pass !== $confirm) {
    http_response_code(400);
    echo 'Passwords must match and not be empty.';
    exit;
}
if (strlen($pass) < 6) {
    http_response_code(400);
    echo 'Password must be at least 6 characters.';
    exit;
}

ensure_data_dir();
$hash = password_hash($pass, PASSWORD_DEFAULT);
$file = DATA_DIR . '/admin_password.hash';
if (file_put_contents($file, $hash, LOCK_EX) === false) {
    http_response_code(500);
    echo 'Failed to save password.';
    exit;
}

header('Location: dashboard.php?msg=pass_saved');
exit;

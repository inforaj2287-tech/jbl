<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/../config/Database.php';

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$currentPassword = (string)($_POST['current_password'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

if ($password === '' || $password !== $confirmPassword || strlen($password) < 6) {
    header('Location: dashboard.php?msg=pass_invalid');
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        header('Location: dashboard.php?msg=pass_wrong');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $_SESSION['admin_id']]);
    session_regenerate_id(true);

    header('Location: dashboard.php?msg=pass_saved');
    exit;
} catch (Exception $e) {
    error_log('Admin password update failed: ' . $e->getMessage());
    header('Location: dashboard.php?msg=pass_error');
    exit;
}

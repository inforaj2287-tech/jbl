<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
EOF

cat > /home/claude/project/admin/change_password.php << 'EOF'
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

$current = (string)($_POST['current_password'] ?? '');
$pass = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        header('Location: dashboard.php?msg=pass_wrong');
        exit;
    }
    if ($pass === '' || $pass !== $confirm || strlen($pass) < 6) {
        header('Location: dashboard.php?msg=pass_invalid');
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $_SESSION['admin_id']]);

    header('Location: dashboard.php?msg=pass_saved');
    exit;
} catch (Exception $e) {
    header('Location: dashboard.php?msg=pass_error');
    exit;
}
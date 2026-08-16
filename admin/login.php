<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/Database.php';
use Config\Database;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT id, password, name FROM admins WHERE email = ? AND status = 1 LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } catch (Exception $e) {
            $error = 'Server error.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>body{display:flex;align-items:center;justify-content:center;height:100vh;background:#f4f6f8} .box{width:360px;padding:22px;border-radius:8px;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.08)}</style>
</head>
<body>
  <div class="box">
    <h2>Admin Login</h2>
    <?php if ($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div><label>Email</label><input name="email" type="email" required></div>
      <div><label>Password</label><input name="password" type="password" required></div>
      <div style="margin-top:12px"><button type="submit">Login</button></div>
    </form>
    <p style="font-size:12px;margin-top:8px;color:#666">If you don't have an admin, run <code>/admin/setup_admin.php</code> locally.</p>
  </div>
</body>
</html>

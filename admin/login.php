<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/Database.php';
use Config\Database;

if (admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($mobile === '' || $password === '') {
        $error = 'Enter mobile number and password.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT id, name, password_hash FROM admins WHERE mobile = ? AND status = 1 LIMIT 1');
            $stmt->execute([$mobile]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Invalid mobile number or password.';
        } catch (Exception $e) {
            $error = 'Could not connect to the database.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — JB Travels</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
  <div class="login-box">
    <div class="login-brand">JB TRAVELS<span>Admin Panel</span></div>
    <h1>Admin Login</h1>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <div class="field">
        <label for="mobile">Mobile number</label>
        <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" maxlength="10" placeholder="10-digit mobile number" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-block">Login</button>
    </form>
    <p class="login-hint">Default demo login: 9999999999 / admin123</p>
  </div>
</body>
</html>
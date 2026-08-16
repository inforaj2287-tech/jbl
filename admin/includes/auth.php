<?php
declare(strict_types=1);
// Simple one-time password prompt for admin pages (no sessions).
require_once __DIR__ . '/../../config/bootstrap.php';

$hashFile = DATA_DIR . '/admin_password.hash';
$basename = basename($_SERVER['SCRIPT_NAME'] ?? '');
$allow = ['dashboard.php', 'change_password.php', 'login.php'];
if (in_array($basename, $allow, true)) {
    return; // allow these pages without prompt so password can be set
}

// If no password set, redirect to dashboard to set it
if (!file_exists($hashFile) || trim(file_get_contents($hashFile)) === '') {
    header('Location: ../dashboard.php');
    exit;
}

$storedHash = trim(file_get_contents($hashFile));
$cookieName = 'jb_admin_token';
$expected = sha1($storedHash . '|' . @filemtime($hashFile));

// validate cookie
if (!empty($_COOKIE[$cookieName]) && hash_equals($expected, (string)$_COOKIE[$cookieName])) {
    return;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    $pw = $_POST['admin_password'];
    if (password_verify($pw, $storedHash)) {
        setcookie($cookieName, $expected, time() + 7200, '/');
        // redirect to current URL to clear POST
        $loc = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'];
        header('Location: ' . $loc);
        exit;
    }
    $error = 'Invalid password.';
}

// prompt
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html><head><meta charset="utf-8"><title>Admin access</title>
<style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;display:flex;align-items:center;justify-content:center;height:100vh} .box{background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,0.08);width:360px}</style>
</head><body>
  <div class="box">
    <h3>Admin access</h3>
    <?php if ($error): ?><div style="color:#b00020;margin-bottom:8px"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <div><label>Password</label><input name="admin_password" type="password" required autofocus style="width:100%;padding:8px;margin-top:6px"></div>
      <div style="margin-top:12px"><button type="submit">Enter</button></div>
    </form>
  </div>
</body></html>

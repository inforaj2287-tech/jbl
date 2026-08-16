<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin — JB Travels</title>
  <?php
    // compute base path (strip /admin and deeper paths)
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $root = preg_replace('#/admin.*$#', '', $script);
    if ($root === '') $root = '/';
    $cssPath = rtrim($root, '/') . '/assets/css/admin.css';
  ?>
  <link rel="stylesheet" href="<?=htmlspecialchars($cssPath)?>">
  <style>body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:0;background:#f4f6f8}.admin-main{padding:28px}</style>
</head>
<body>
  <div class="admin-topbar">
    <div class="brand">JB Travels — Admin</div>
    <div class="actions">Logged in as <?=htmlspecialchars($_SESSION['admin_name'] ?? '');?> <a href="logout.php">Logout</a></div>
  </div>

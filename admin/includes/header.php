<?php
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminBase = admin_base_url();
// site root = admin base minus the trailing "admin/"
$siteRoot = preg_replace('#admin/$#', '', $adminBase);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — JB Travels</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($siteRoot) ?>assets/css/admin.css">
</head>
<body>
  <div class="admin-topbar">
    <div class="brand">JB Travels — Admin</div>
    <div class="actions">
      <span class="admin-name">Hi, <?= htmlspecialchars($adminName) ?></span>
      <a class="btn btn-outline" href="<?= htmlspecialchars($adminBase) ?>logout.php">Logout</a>
    </div>
  </div>
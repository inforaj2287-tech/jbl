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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="admin-topbar">
    <div class="brand">JB Travels — Admin</div>
    <div class="actions">
      <span class="admin-name">Hi, <?= htmlspecialchars($adminName) ?></span>
      <a class="btn btn-outline" href="<?= htmlspecialchars($adminBase) ?>logout.php">Logout</a>
    </div>
  </div>
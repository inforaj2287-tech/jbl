<?php $adminBase = admin_base_url(); ?>
<aside class="admin-sidebar">
  <nav>
    <ul>
      <li><a href="<?= htmlspecialchars($adminBase) ?>dashboard.php">Dashboard</a></li>
      <li><a href="<?= htmlspecialchars($adminBase) ?>cars/index.php">Cars &amp; Pricing</a></li>
      <li><a href="<?= htmlspecialchars($adminBase) ?>locations/index.php">Locations</a></li>
      <li><a href="<?= htmlspecialchars($adminBase) ?>locations/distances.php">Route Distances</a></li>
      <li><a href="<?= htmlspecialchars($adminBase) ?>bookings/index.php">Bookings</a></li>
    </ul>
  </nav>
</aside>
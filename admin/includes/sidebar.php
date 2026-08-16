<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<aside class="admin-sidebar">
  <nav>
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="cars/index.php">Cars</a></li>
      <li><a href="#">Bookings</a></li>
      <li><a href="#">Customers</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</aside>

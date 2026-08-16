<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/Database.php';
use Config\Database;

// fetch some summary numbers
try {
    $pdo = Database::getConnection();
    $bookingsCount = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $carsCount = $pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn();
    $customersCount = $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
} catch (Exception $e) {
    $bookingsCount = $carsCount = $customersCount = 0;
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="admin-main">
  <h1>Dashboard</h1>
  <div class="cards">
    <div class="card">Bookings<br><strong><?=htmlspecialchars($bookingsCount)?></strong></div>
    <div class="card">Cars<br><strong><?=htmlspecialchars($carsCount)?></strong></div>
    <div class="card">Customers<br><strong><?=htmlspecialchars($customersCount)?></strong></div>
  </div>
  <p><a class="btn" href="cars/index.php">Manage Cars</a></p>
  <section style="margin-top:28px;max-width:520px;background:#fff;padding:16px;border-radius:8px;">
    <h3>Change Admin Password</h3>
    <?php if (isset($_GET['msg']) && $_GET['msg']==='pass_saved'): ?><div style="padding:8px;background:#e6ffed;border:1px solid #b6f2c7;margin-bottom:12px">Password saved.</div><?php endif; ?>
    <form method="post" action="change_password.php">
      <div><label>New password</label><input name="password" type="password" required></div>
      <div><label>Confirm password</label><input name="confirm_password" type="password" required></div>
      <div style="margin-top:12px"><button type="submit">Save password</button></div>
    </form>
    <p style="font-size:12px;color:#666;margin-top:8px">Password stored locally in `data/admin_password.hash` (hashed). This is a simple single-admin flow without login.</p>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();
    $bookingsCount = (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $carsCount = (int)$pdo->query('SELECT COUNT(*) FROM cars WHERE status = 1')->fetchColumn();
    $customersCount = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'PENDING'")->fetchColumn();
    $revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status = 'PAID'")->fetchColumn();
} catch (Exception $e) {
    $bookingsCount = $carsCount = $customersCount = $pendingCount = 0;
    $revenue = 0.0;
}

$msg = $_GET['msg'] ?? '';
$messages = [
    'pass_saved'   => ['ok', 'Password updated successfully.'],
    'pass_wrong'   => ['err', 'Current password is incorrect.'],
    'pass_invalid' => ['err', 'New password must be at least 6 characters and match the confirmation.'],
    'pass_error'   => ['err', 'Could not update password. Please try again.'],
];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="admin-main">
  <h1>Dashboard</h1>
  <div class="cards">
    <div class="card"><span>Total Bookings</span><strong><?= $bookingsCount ?></strong></div>
    <div class="card"><span>Pending Bookings</span><strong><?= $pendingCount ?></strong></div>
    <div class="card"><span>Active Cars</span><strong><?= $carsCount ?></strong></div>
    <div class="card"><span>Customers</span><strong><?= $customersCount ?></strong></div>
    <div class="card"><span>Paid Revenue</span><strong>&#8377; <?= number_format($revenue, 2) ?></strong></div>
  </div>

  <p class="quick-links">
    <a class="btn" href="cars/index.php">Manage Cars</a>
    <a class="btn btn-outline" href="locations/index.php">Manage Locations</a>
    <a class="btn btn-outline" href="bookings/index.php">View Bookings</a>
  </p>

  <section class="panel">
    <h3>Change Password</h3>
    <?php if ($msg && isset($messages[$msg])): [$cls, $text] = $messages[$msg]; ?>
      <div class="<?= $cls === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="change_password.php" class="stacked-form">
      <div class="field"><label>Current password</label><input name="current_password" type="password" required></div>
      <div class="field"><label>New password</label><input name="password" type="password" required minlength="6"></div>
      <div class="field"><label>Confirm new password</label><input name="confirm_password" type="password" required minlength="6"></div>
      <button type="submit" class="btn">Save password</button>
    </form>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
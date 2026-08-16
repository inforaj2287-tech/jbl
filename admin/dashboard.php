<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}
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
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

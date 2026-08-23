<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['booking_id'])) {
    $bid = (int)$_POST['booking_id'];
    $newStatus = $_POST['booking_status'] ?? '';
    $allowed = ['PENDING','CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP','COMPLETED','CANCELLED'];
    if (in_array($newStatus, $allowed, true)) {
        $old = $pdo->prepare('SELECT booking_status FROM bookings WHERE id = ?');
        $old->execute([$bid]);
        $oldStatus = $old->fetchColumn();

        $pdo->prepare('UPDATE bookings SET booking_status = ? WHERE id = ?')->execute([$newStatus, $bid]);
        $pdo->prepare('INSERT INTO booking_status_history (booking_id, old_status, new_status, remarks) VALUES (?,?,?,?)')
            ->execute([$bid, $oldStatus ?: null, $newStatus, 'Updated from admin panel']);
    }
    header('Location: index.php?updated=1');
    exit;
}

$bookings = $pdo->query('
    SELECT b.*, c.name AS customer_name, c.mobile AS customer_mobile,
           f.city AS from_city, t.city AS to_city, cars.name AS car_name
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    JOIN locations f ON f.id = b.from_location_id
    JOIN locations t ON t.id = b.to_location_id
    JOIN cars ON cars.id = b.car_id
    ORDER BY b.created_at DESC
    LIMIT 200
')->fetchAll();

$statuses = ['PENDING','CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP','COMPLETED','CANCELLED'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <div class="page-head"><h1>Bookings</h1></div>
  <?php if (isset($_GET['updated'])): ?><div class="ok">Booking status updated.</div><?php endif; ?>

  <table class="admin-table">
    <thead>
      <tr><th>Booking #</th><th>Customer</th><th>Route</th><th>Car</th><th>Date</th><th>Total</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php if (!$bookings): ?><tr><td colspan="7" class="empty">No bookings yet.</td></tr><?php endif; ?>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['booking_number']) ?></td>
          <td><?= htmlspecialchars($b['customer_name']) ?><br><small><?= htmlspecialchars($b['customer_mobile']) ?></small></td>
          <td><?= htmlspecialchars($b['from_city']) ?> &rarr; <?= htmlspecialchars($b['to_city']) ?><br><small><?= number_format((float)$b['distance_km'],0) ?> km</small></td>
          <td><?= htmlspecialchars($b['car_name']) ?> (<?= $b['ac_type'] === 'AC' ? 'AC' : 'Non-AC' ?>)</td>
          <td><?= htmlspecialchars($b['pickup_date']) ?> <?= htmlspecialchars($b['pickup_time']) ?></td>
          <td>&#8377; <?= number_format((float)$b['total_amount'], 2) ?></td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
              <select name="booking_status" onchange="this.form.submit()">
                <?php foreach ($statuses as $s): ?>
                  <option value="<?= $s ?>" <?= $b['booking_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
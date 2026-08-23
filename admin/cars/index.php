<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();
    $cars = $pdo->query('
        SELECT c.*,
          (SELECT COUNT(*) FROM car_rates r WHERE r.car_id = c.id AND r.status = 1) AS rate_count
        FROM cars c ORDER BY c.id DESC
    ')->fetchAll();
} catch (Exception $e) {
    $cars = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <div class="page-head">
    <h1>Cars &amp; Pricing</h1>
    <a class="btn" href="create.php">+ Add Car</a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Image</th><th>Name</th><th>Brand / Model</th><th>Category</th><th>Seats</th>
        <th>Rate rows</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$cars): ?>
        <tr><td colspan="8" class="empty">No cars added yet. Click "Add Car" to create your fleet.</td></tr>
      <?php endif; ?>
      <?php foreach ($cars as $c): ?>
        <tr>
          <td><?php if ($c['image']): ?><img class="thumb" src="../../<?= htmlspecialchars($c['image']) ?>" alt=""><?php else: ?><span class="thumb placeholder"></span><?php endif; ?></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars(trim(($c['brand'] ?? '') . ' ' . ($c['model'] ?? ''))) ?></td>
          <td><?= htmlspecialchars($c['category']) ?></td>
          <td><?= htmlspecialchars((string)$c['seats']) ?></td>
          <td><?= (int)$c['rate_count'] ?> rate(s) — <a href="rates.php?car_id=<?= (int)$c['id'] ?>">manage</a></td>
          <td><span class="badge <?= $c['status'] ? 'badge-on' : 'badge-off' ?>"><?= $c['status'] ? 'Active' : 'Inactive' ?></span></td>
          <td class="row-actions">
            <a href="create.php?id=<?= (int)$c['id'] ?>">Edit</a>
            <a href="rates.php?car_id=<?= (int)$c['id'] ?>">Rates</a>
            <a class="danger" href="save.php?action=delete&amp;id=<?= (int)$c['id'] ?>" onclick="return confirm('Delete this car and all its rates?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
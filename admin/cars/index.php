<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT * FROM cars ORDER BY id DESC');
    $cars = $stmt->fetchAll();
} catch (Exception $e) {
    $cars = [];
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <h1>Cars</h1>
  <p><a class="btn" href="create.php">Add Car</a></p>
  <table class="admin-table">
    <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Seats</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($cars as $c): ?>
        <tr>
          <td><?=htmlspecialchars($c['id'])?></td>
          <td><?=htmlspecialchars($c['name'])?></td>
          <td><?=htmlspecialchars($c['category'])?></td>
          <td><?=htmlspecialchars($c['seats'])?></td>
          <td><?= $c['status'] ? 'Active' : 'Inactive' ?></td>
          <td><a href="create.php?id=<?=urlencode($c['id'])?>">Edit</a> | <a href="save.php?action=delete&id=<?=urlencode($c['id'])?>" onclick="return confirm('Delete?')">Delete</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

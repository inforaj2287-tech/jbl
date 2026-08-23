<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$pdo = Database::getConnection();
$locations = $pdo->query('SELECT * FROM locations ORDER BY city ASC')->fetchAll();

$editLoc = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM locations WHERE id = ? LIMIT 1');
    $s->execute([(int)$_GET['edit']]);
    $editLoc = $s->fetch() ?: null;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <div class="page-head">
    <h1>Locations</h1>
  </div>
  <p class="hint">Cities customers can pick as pickup/drop. Add latitude/longitude so distance (and price) can be
    auto-estimated for routes you haven't set an exact distance for. Use <a href="distances.php">Route Distances</a>
    to override the estimate for specific city pairs.</p>

  <?php if (isset($_GET['saved'])): ?><div class="ok">Location saved.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="ok">Location removed.</div><?php endif; ?>

  <table class="admin-table">
    <thead><tr><th>City</th><th>State</th><th>Latitude</th><th>Longitude</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($locations as $l): ?>
        <tr>
          <td><?= htmlspecialchars($l['city']) ?></td>
          <td><?= htmlspecialchars($l['state'] ?? '') ?></td>
          <td><?= htmlspecialchars((string)$l['latitude']) ?></td>
          <td><?= htmlspecialchars((string)$l['longitude']) ?></td>
          <td><span class="badge <?= $l['status'] ? 'badge-on' : 'badge-off' ?>"><?= $l['status'] ? 'Active' : 'Inactive' ?></span></td>
          <td class="row-actions">
            <a href="index.php?edit=<?= (int)$l['id'] ?>">Edit</a>
            <a class="danger" href="save.php?action=delete&amp;id=<?= (int)$l['id'] ?>" onclick="return confirm('Delete this location?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <section class="panel">
    <h3><?= $editLoc ? 'Edit location' : 'Add a location' ?></h3>
    <form method="post" action="save.php" class="stacked-form">
      <input type="hidden" name="id" value="<?= $editLoc ? (int)$editLoc['id'] : 0 ?>">
      <div class="form-grid">
        <div class="field"><label>City *</label><input name="city" value="<?= htmlspecialchars($editLoc['city'] ?? '') ?>" required></div>
        <div class="field"><label>State</label><input name="state" value="<?= htmlspecialchars($editLoc['state'] ?? '') ?>"></div>
        <div class="field"><label>Latitude</label><input name="latitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string)($editLoc['latitude'] ?? '')) ?>"></div>
        <div class="field"><label>Longitude</label><input name="longitude" type="number" step="0.0000001" value="<?= htmlspecialchars((string)($editLoc['longitude'] ?? '')) ?>"></div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="1" <?= (int)($editLoc['status'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= isset($editLoc) && (int)$editLoc['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn"><?= $editLoc ? 'Update' : 'Add Location' ?></button>
        <?php if ($editLoc): ?><a class="btn btn-outline" href="index.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
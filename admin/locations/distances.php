<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = (int)($_POST['from_location_id'] ?? 0);
    $to = (int)($_POST['to_location_id'] ?? 0);
    $km = (float)($_POST['distance_km'] ?? 0);
    if ($from && $to && $from !== $to && $km > 0) {
        $stmt = $pdo->prepare('INSERT INTO distances (from_location_id, to_location_id, distance_km) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE distance_km = VALUES(distance_km)');
        $stmt->execute([$from, $to, $km]);
    }
    header('Location: distances.php?saved=1');
    exit;
}

if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) $pdo->prepare('DELETE FROM distances WHERE id = ?')->execute([$id]);
    header('Location: distances.php?deleted=1');
    exit;
}

$locations = $pdo->query('SELECT id, city, state FROM locations WHERE status = 1 ORDER BY city ASC')->fetchAll();
$distances = $pdo->query('
    SELECT d.*, a.city AS from_city, b.city AS to_city
    FROM distances d
    JOIN locations a ON a.id = d.from_location_id
    JOIN locations b ON b.id = d.to_location_id
    ORDER BY a.city, b.city
')->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <div class="page-head"><h1>Route Distances</h1></div>
  <p class="hint">Optional. Set an exact road-km figure for a specific pickup &rarr; drop pair — it overrides the
    automatic straight-line estimate used everywhere else. Every fare is <strong>distance (km) &times; rate per km</strong>,
    so this number directly changes the price shown to customers on that route.</p>

  <?php if (isset($_GET['saved'])): ?><div class="ok">Distance saved.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="ok">Distance removed.</div><?php endif; ?>

  <table class="admin-table">
    <thead><tr><th>From</th><th>To</th><th>Distance (km)</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (!$distances): ?><tr><td colspan="4" class="empty">No manual overrides yet — distance is being auto-estimated from city coordinates.</td></tr><?php endif; ?>
      <?php foreach ($distances as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['from_city']) ?></td>
          <td><?= htmlspecialchars($d['to_city']) ?></td>
          <td><?= number_format((float)$d['distance_km'], 2) ?> km</td>
          <td><a class="danger" href="distances.php?action=delete&amp;id=<?= (int)$d['id'] ?>" onclick="return confirm('Remove this override?')">Delete</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <section class="panel">
    <h3>Add / update a route distance</h3>
    <form method="post" class="stacked-form">
      <div class="form-grid">
        <div class="field">
          <label>From city</label>
          <select name="from_location_id" required>
            <option value="">Select city</option>
            <?php foreach ($locations as $l): ?><option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['city']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>To city</label>
          <select name="to_location_id" required>
            <option value="">Select city</option>
            <?php foreach ($locations as $l): ?><option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['city']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Distance (km)</label>
          <input type="number" step="0.01" min="0.01" name="distance_km" required>
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn">Save Distance</button></div>
    </form>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
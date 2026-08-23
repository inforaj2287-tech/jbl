<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$carId = (int)($_GET['car_id'] ?? 0);
if ($carId <= 0) { header('Location: index.php'); exit; }

$pdo = Database::getConnection();

$carStmt = $pdo->prepare('SELECT * FROM cars WHERE id = ? LIMIT 1');
$carStmt->execute([$carId]);
$car = $carStmt->fetch();
if (!$car) { header('Location: index.php'); exit; }

$ratesStmt = $pdo->prepare('SELECT * FROM car_rates WHERE car_id = ? ORDER BY service_type, ac_type');
$ratesStmt->execute([$carId]);
$rates = $ratesStmt->fetchAll();

// editing an existing row?
$editRate = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM car_rates WHERE id = ? AND car_id = ? LIMIT 1');
    $es->execute([(int)$_GET['edit'], $carId]);
    $editRate = $es->fetch() ?: null;
}

$serviceTypes = ['OUTSTATION', 'LOCAL', 'AIRPORT'];
$acTypes = ['AC', 'NON_AC'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <div class="page-head">
    <h1>Pricing — <?= htmlspecialchars($car['name']) ?></h1>
    <a class="btn btn-outline" href="index.php">&larr; Back to Cars</a>
  </div>

  <?php if (isset($_GET['created'])): ?><div class="ok">Car created. Now add its AC / Non-AC pricing below.</div><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><div class="ok">Rate saved.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="ok">Rate removed.</div><?php endif; ?>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Service</th><th>AC Type</th><th>Fuel</th><th>Transmission</th>
        <th>Rate / km</th><th>Min km</th><th>Driver allow.</th><th>Discount</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rates): ?>
        <tr><td colspan="10" class="empty">No pricing added yet — use the form below. Each row you add is what makes a NON-AC or AC column show a price instead of "Not Available" on the site.</td></tr>
      <?php endif; ?>
      <?php foreach ($rates as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['service_type']) ?></td>
          <td><?= $r['ac_type'] === 'AC' ? 'AC' : 'Non-AC' ?></td>
          <td><?= htmlspecialchars($r['fuel_type']) ?></td>
          <td><?= htmlspecialchars($r['transmission']) ?></td>
          <td>&#8377; <?= number_format((float)$r['rate_per_km'], 2) ?></td>
          <td><?= number_format((float)$r['minimum_km'], 0) ?> km</td>
          <td>&#8377; <?= number_format((float)$r['driver_allowance'], 2) ?></td>
          <td>&#8377; <?= number_format((float)$r['discount_amount'], 2) ?></td>
          <td><span class="badge <?= $r['status'] ? 'badge-on' : 'badge-off' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
          <td class="row-actions">
            <a href="rates.php?car_id=<?= $carId ?>&amp;edit=<?= (int)$r['id'] ?>">Edit</a>
            <a class="danger" href="rate_save.php?action=delete&amp;id=<?= (int)$r['id'] ?>&amp;car_id=<?= $carId ?>" onclick="return confirm('Remove this rate?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <section class="panel">
    <h3><?= $editRate ? 'Edit rate' : 'Add a rate' ?></h3>
    <form method="post" action="rate_save.php" class="stacked-form">
      <input type="hidden" name="id" value="<?= $editRate ? (int)$editRate['id'] : 0 ?>">
      <input type="hidden" name="car_id" value="<?= $carId ?>">

      <div class="form-grid">
        <div class="field">
          <label>Service type *</label>
          <select name="service_type">
            <?php foreach ($serviceTypes as $s): ?>
              <option value="<?= $s ?>" <?= ($editRate['service_type'] ?? 'OUTSTATION') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>AC type *</label>
          <select name="ac_type">
            <?php foreach ($acTypes as $a): ?>
              <option value="<?= $a ?>" <?= ($editRate['ac_type'] ?? 'AC') === $a ? 'selected' : '' ?>><?= $a === 'AC' ? 'AC' : 'Non-AC' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Fuel type</label>
          <input name="fuel_type" value="<?= htmlspecialchars($editRate['fuel_type'] ?? 'Diesel') ?>">
        </div>
        <div class="field">
          <label>Transmission</label>
          <input name="transmission" value="<?= htmlspecialchars($editRate['transmission'] ?? 'Manual') ?>">
        </div>
        <div class="field">
          <label>Rate per km (&#8377;) *</label>
          <input name="rate_per_km" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['rate_per_km'] ?? '10.00')) ?>" required>
        </div>
        <div class="field">
          <label>Minimum km</label>
          <input name="minimum_km" type="number" step="1" min="0" value="<?= htmlspecialchars((string)($editRate['minimum_km'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Base fare (&#8377;, flat)</label>
          <input name="base_fare" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['base_fare'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Driver allowance (&#8377;)</label>
          <input name="driver_allowance" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['driver_allowance'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Night charge (&#8377;)</label>
          <input name="night_charge" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['night_charge'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Toll charge (&#8377;)</label>
          <input name="toll_charge" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['toll_charge'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Permit charge (&#8377;)</label>
          <input name="permit_charge" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['permit_charge'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Discount / "Save Upto" (&#8377;)</label>
          <input name="discount_amount" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['discount_amount'] ?? '0')) ?>">
        </div>
        <div class="field">
          <label>Tax %</label>
          <input name="tax_percent" type="number" step="0.01" min="0" value="<?= htmlspecialchars((string)($editRate['tax_percent'] ?? '5.00')) ?>">
        </div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="1" <?= (int)($editRate['status'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= isset($editRate) && (int)$editRate['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn"><?= $editRate ? 'Update Rate' : 'Add Rate' ?></button>
        <?php if ($editRate): ?><a class="btn btn-outline" href="rates.php?car_id=<?= $carId ?>">Cancel</a><?php endif; ?>
      </div>
    </form>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
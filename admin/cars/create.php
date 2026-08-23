<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$editing = false;
$car = ['id'=>0,'name'=>'','brand'=>'','model'=>'','category'=>'SEDAN','seats'=>4,'luggage_capacity'=>2,'registration_number'=>'','image'=>'','description'=>'','status'=>1];

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM cars WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) { $car = $row; $editing = true; }
}

$categories = ['HATCHBACK','SEDAN','SUV','MUV','PREMIUM'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <h1><?= $editing ? 'Edit Car' : 'Add Car' ?></h1>

  <form method="post" action="save.php" enctype="multipart/form-data" class="stacked-form panel">
    <input type="hidden" name="id" value="<?= (int)$car['id'] ?>">

    <div class="form-grid">
      <div class="field">
        <label>Car name *</label>
        <input name="name" value="<?= htmlspecialchars($car['name']) ?>" required placeholder="e.g. Swift Dzire">
      </div>
      <div class="field">
        <label>Brand</label>
        <input name="brand" value="<?= htmlspecialchars($car['brand']) ?>" placeholder="e.g. Maruti">
      </div>
      <div class="field">
        <label>Model</label>
        <input name="model" value="<?= htmlspecialchars($car['model']) ?>" placeholder="e.g. Dzire">
      </div>
      <div class="field">
        <label>Category *</label>
        <select name="category">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>" <?= $car['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Seats</label>
        <input name="seats" type="number" min="1" max="20" value="<?= (int)$car['seats'] ?>">
      </div>
      <div class="field">
        <label>Luggage capacity (bags)</label>
        <input name="luggage_capacity" type="number" min="0" max="10" value="<?= (int)$car['luggage_capacity'] ?>">
      </div>
      <div class="field">
        <label>Registration number</label>
        <input name="registration_number" value="<?= htmlspecialchars($car['registration_number'] ?? '') ?>" placeholder="e.g. KA-22-AB-1234">
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="1" <?= (int)$car['status'] === 1 ? 'selected' : '' ?>>Active (visible on site)</option>
          <option value="0" <?= (int)$car['status'] === 0 ? 'selected' : '' ?>>Inactive (hidden)</option>
        </select>
      </div>
    </div>

    <div class="field">
      <label>Car image</label>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      <?php if (!empty($car['image'])): ?>
        <div class="current-image"><img src="../../<?= htmlspecialchars($car['image']) ?>" alt=""> current image</div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label>Description</label>
      <textarea name="description" rows="3" placeholder="Short note shown to customers"><?= htmlspecialchars($car['description']) ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save Car</button>
      <a class="btn btn-outline" href="index.php">Cancel</a>
      <?php if ($editing): ?><a class="btn btn-outline" href="rates.php?car_id=<?= (int)$car['id'] ?>">Manage Pricing &rarr;</a><?php endif; ?>
    </div>
  </form>

  <?php if (!$editing): ?>
    <p class="hint">Save the car first, then you'll be able to add AC / Non-AC pricing per km.</p>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$editing = false;
$car = ['id'=>0,'name'=>'','brand'=>'','model'=>'','category'=>'SEDAN','seats'=>4,'image'=>'','description'=>'','status'=>1];
if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM cars WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) { $car = $row; $editing = true; }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="admin-main">
  <h1><?= $editing ? 'Edit' : 'Add' ?> Car</h1>
  <form method="post" action="save.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?=htmlspecialchars($car['id'])?>">
    <div><label>Name</label><input name="name" value="<?=htmlspecialchars($car['name'])?>" required></div>
    <div><label>Brand</label><input name="brand" value="<?=htmlspecialchars($car['brand'])?>"></div>
    <div><label>Model</label><input name="model" value="<?=htmlspecialchars($car['model'])?>"></div>
    <div><label>Category</label>
      <select name="category">
        <option value="HATCHBACK">HATCHBACK</option>
        <option value="SEDAN" <?= $car['category']==='SEDAN'?'selected':''?>>SEDAN</option>
        <option value="SUV" <?= $car['category']==='SUV'?'selected':''?>>SUV</option>
        <option value="MUV">MUV</option>
        <option value="PREMIUM">PREMIUM</option>
      </select>
    </div>
    <div><label>Seats</label><input name="seats" type="number" value="<?=htmlspecialchars($car['seats'])?>"></div>
    <div><label>Image</label><input type="file" name="image"></div>
    <div><label>Description</label><textarea name="description"><?=htmlspecialchars($car['description'])?></textarea></div>
    <div><label>Status</label><select name="status"><option value="1">Active</option><option value="0" <?= $car['status']==0?'selected':''?>>Inactive</option></select></div>
    <div style="margin-top:12px"><button type="submit">Save</button> <a href="index.php">Cancel</a></div>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

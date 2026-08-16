<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();
    $action = $_REQUEST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
            $stmt->execute([$id]);
        }
        header('Location: index.php'); exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $category = $_POST['category'] ?? 'SEDAN';
    $seats = (int)($_POST['seats'] ?? 4);
    $description = trim($_POST['description'] ?? '');
    $status = (int)($_POST['status'] ?? 1);

    // handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['image'];
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array(strtolower($ext), $allowed, true)) {
            throw new RuntimeException('Invalid image type.');
        }
        $dir = __DIR__ . '/../../assets/images/cars';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid('car_') . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('Failed to move uploaded file.');
        $imagePath = 'assets/images/cars/' . $filename;
    }

    if ($id > 0) {
        $fields = 'name=?,brand=?,model=?,category=?,seats=?,description=?,status=?';
        $params = [$name,$brand,$model,$category,$seats,$description,$status,$id];
        if ($imagePath) {
            $fields = 'name=?,brand=?,model=?,category=?,seats=?,image=?,description=?,status=?';
            $params = [$name,$brand,$model,$category,$seats,$imagePath,$description,$status,$id];
        }
        $stmt = $pdo->prepare('UPDATE cars SET ' . $fields . ' WHERE id = ?');
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cars (name,brand,model,category,seats,image,description,status) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$name,$brand,$model,$category,$seats,$imagePath,$description,$status]);
    }

    header('Location: index.php'); exit;

} catch (Exception $e) {
    error_log('Admin cars save error: ' . $e->getMessage());
    header('Location: index.php'); exit;
}

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? 'save';

if ($action === 'delete') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id) {
        $pdo->prepare('DELETE FROM locations WHERE id = ?')->execute([$id]);
    }
    header('Location: index.php?deleted=1');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$lat = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
$lng = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$status = (int)($_POST['status'] ?? 1);

if ($city === '') {
    header('Location: index.php?error=city_required');
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE locations SET city=?, state=?, latitude=?, longitude=?, status=? WHERE id=?');
        $stmt->execute([$city, $state, $lat, $lng, $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO locations (city, state, latitude, longitude, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$city, $state, $lat, $lng, $status]);
    }
    header('Location: index.php?saved=1');
    exit;
} catch (Exception $e) {
    error_log('location save error: ' . $e->getMessage());
    header('Location: index.php?error=1');
    exit;
}
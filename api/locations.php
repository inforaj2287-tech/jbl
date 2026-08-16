<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';
use Config\Database;

$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, city, state, country, airport_name FROM locations WHERE (city LIKE ? OR airport_name LIKE ?) AND status = 1 LIMIT 10');
    $like = $q . '%';
    $stmt->execute([$like, $like]);
    $rows = $stmt->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $label = $r['city'];
        if (!empty($r['state'])) $label .= ', ' . $r['state'];
        if (!empty($r['country'])) $label .= ', ' . $r['country'];
        if (!empty($r['airport_name'])) $label .= ' (' . $r['airport_name'] . ')';
        $out[] = ['id' => (int)$r['id'], 'label' => $label];
    }

    echo json_encode(['success' => true, 'data' => $out]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to fetch locations']);
}

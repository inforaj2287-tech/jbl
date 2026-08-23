<?php
declare(strict_types=1);
/**
 * api/locations.php
 * GET ?q=term       -> up to 10 autocomplete suggestions {id, label}
 * GET ?resolve=term -> best single match {id, city} used to turn the
 *                      free-text pickup/drop the user typed into a
 *                      location_id the rest of the API can use.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';
use Config\Database;

try {
    $pdo = Database::getConnection();

    if (isset($_GET['resolve'])) {
        $term = trim((string)$_GET['resolve']);
        if ($term === '') {
            echo json_encode(['success' => true, 'data' => null]);
            exit;
        }
        // exact match first, then "starts with", then "contains"
        $stmt = $pdo->prepare(
            "SELECT id, city, state FROM locations
             WHERE status = 1 AND city = ?
             LIMIT 1"
        );
        $stmt->execute([$term]);
        $row = $stmt->fetch();

        if (!$row) {
            $stmt = $pdo->prepare(
                "SELECT id, city, state FROM locations
                 WHERE status = 1 AND city LIKE ?
                 ORDER BY LENGTH(city) ASC LIMIT 1"
            );
            $stmt->execute([$term . '%']);
            $row = $stmt->fetch();
        }
        if (!$row) {
            $stmt = $pdo->prepare(
                "SELECT id, city, state FROM locations
                 WHERE status = 1 AND city LIKE ?
                 ORDER BY LENGTH(city) ASC LIMIT 1"
            );
            $stmt->execute(['%' . $term . '%']);
            $row = $stmt->fetch();
        }

        echo json_encode(['success' => true, 'data' => $row ?: null]);
        exit;
    }

    $q = trim((string)($_GET['q'] ?? ''));
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, city, state, country FROM locations WHERE city LIKE ? AND status = 1 ORDER BY city ASC LIMIT 10');
    $stmt->execute([$q . '%']);
    $rows = $stmt->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $label = $r['city'];
        if (!empty($r['state'])) $label .= ', ' . $r['state'];
        $out[] = ['id' => (int)$r['id'], 'city' => $r['city'], 'label' => $label];
    }

    echo json_encode(['success' => true, 'data' => $out]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to fetch locations']);
}
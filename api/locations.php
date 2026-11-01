<?php
declare(strict_types=1);
/**
 * api/locations.php
 * GET ?q=term       -> up to 10 autocomplete suggestions {id, label}
 * GET ?resolve=term -> exact active city match {id, city}
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
        $aliases = [
            'hubli' => 'Hubballi',
            'bengaluru' => 'Bengaluru',
            'belgaum' => 'Belagavi',
            'belgaum airport' => 'Belagavi',
            'belagavi airport' => 'Belagavi',
        ];
        $term = $aliases[strtolower($term)] ?? $term;
        $stmt = $pdo->prepare(
            'SELECT id, city, state FROM locations
             WHERE status = 1 AND LOWER(city) = LOWER(?)
             LIMIT 1'
        );
        $stmt->execute([$term]);
        $row = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $row ?: null]);
        exit;
    }

    if (isset($_GET['list'])) {
        $stmt = $pdo->query(
            'SELECT id, city, state FROM locations
             WHERE status = 1
             ORDER BY city ASC'
        );
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $label = $row['city'];
            if (!empty($row['state'])) $label .= ', ' . $row['state'];
            $out[] = ['id' => (int)$row['id'], 'city' => $row['city'], 'label' => $label];
        }
        echo json_encode(['success' => true, 'data' => $out]);
        exit;
    }

    $q = trim((string)($_GET['q'] ?? ''));
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $aliases = [
        'hubli' => 'Hubballi',
        'bengaluru' => 'Bengaluru',
        'belgaum' => 'Belagavi',
    ];
    $searchTerm = $aliases[strtolower($q)] ?? $q;
    $stmt = $pdo->prepare('SELECT id, city, state, country FROM locations WHERE city LIKE ? AND status = 1 ORDER BY city ASC LIMIT 10');
    $stmt->execute([$searchTerm . '%']);
    $rows = $stmt->fetchAll();

    if (count($rows) < 10 && strlen($searchTerm) >= 4) {
        $knownCities = array_column($rows, 'city');
        $stmt = $pdo->query('SELECT id, city, state, country FROM locations WHERE status = 1 ORDER BY city ASC');
        while ($row = $stmt->fetch()) {
            if (in_array($row['city'], $knownCities, true)) continue;
            $distance = levenshtein(strtolower($searchTerm), strtolower((string)$row['city']));
            $allowedDistance = max(1, (int)floor(max(strlen($searchTerm), strlen((string)$row['city'])) * 0.2));
            if ($distance <= $allowedDistance) {
                $row['_match_distance'] = $distance;
                $rows[] = $row;
            }
        }
        usort($rows, static function (array $a, array $b): int {
            return ($a['_match_distance'] ?? 0) <=> ($b['_match_distance'] ?? 0);
        });
        $rows = array_slice($rows, 0, 10);
    }

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
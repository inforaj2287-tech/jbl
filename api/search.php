<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/FareService.php';

use Config\Database;

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$required = ['service_type', 'trip_type', 'from_location_id', 'to_location_id', 'pickup_date', 'pickup_time'];
foreach ($required as $r) {
    if (empty($input[$r])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing field: {$r}"]);
        exit;
    }
}

$serviceType = $input['service_type'];
$tripType = $input['trip_type'];
$fromId = (int)$input['from_location_id'];
$toId = (int)$input['to_location_id'];

try {
    $pdo = Database::getConnection();

    // distance lookup
    $stmt = $pdo->prepare('SELECT distance_km FROM distances WHERE (from_location_id = ? AND to_location_id = ?) OR (from_location_id = ? AND to_location_id = ?) LIMIT 1');
    $stmt->execute([$fromId, $toId, $toId, $fromId]);
    $row = $stmt->fetch();
    $distance = $row ? (float)$row['distance_km'] : 100.0; // fallback

    // fetch available car variants
    $carsStmt = $pdo->prepare('SELECT c.id as car_id, c.name as car_name, c.category, c.seats, c.image, v.id as variant_id, v.variant_name, v.ac_type, v.fuel_type, v.transmission FROM cars c JOIN car_variants v ON v.car_id = c.id WHERE c.status = 1 AND v.status = 1');
    $carsStmt->execute();
    $cars = $carsStmt->fetchAll();

    $results = [];
    $rateStmt = $pdo->prepare('SELECT * FROM rates WHERE service_type = ? AND (variant_id = ? OR car_id = ? OR (variant_id IS NULL AND car_id IS NULL)) LIMIT 1');

    foreach ($cars as $c) {
        // find rate — try variant, then car, then fallback default
        $rateStmt->execute([$serviceType, $c['variant_id'], $c['car_id']]);
        $rate = $rateStmt->fetch();
        if (!$rate) {
            // fallback default rates per category
            $defaultStmt = $pdo->prepare('SELECT * FROM rates WHERE service_type = ? AND car_id IS NULL LIMIT 1');
            $defaultStmt->execute([$serviceType]);
            $rate = $defaultStmt->fetch();
        }

        if (!$rate) continue; // skip if no rate configured

        $fare = FareService::calculateOneWayFare($distance, $rate);

        $results[] = [
            'car_id' => (int)$c['car_id'],
            'variant_id' => (int)$c['variant_id'],
            'car_name' => $c['car_name'],
            'variant_name' => $c['variant_name'],
            'category' => $c['category'],
            'seats' => (int)$c['seats'],
            'ac_type' => $c['ac_type'],
            'image' => $c['image'],
            'distance_km' => $distance,
            'fare_breakdown' => $fare,
        ];
    }

    echo json_encode(['success' => true, 'message' => 'Cars found successfully', 'distance' => $distance, 'cars' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

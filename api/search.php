<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/FareService.php';
require_once __DIR__ . '/../services/DistanceService.php';

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$serviceType = strtoupper(trim((string)($input['service_type'] ?? 'OUTSTATION')));
$tripType    = strtoupper(trim((string)($input['trip_type'] ?? 'ONE_WAY')));
$pickupDate  = trim((string)($input['pickup_date'] ?? ''));
$pickupTime  = trim((string)($input['pickup_time'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate) || !strtotime($pickupDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid pickup date is required.']);
    exit;
}
if ($pickupDate < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pickup date cannot be in the past.']);
    exit;
}
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $pickupTime)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid pickup time is required.']);
    exit;
}

if (!in_array($serviceType, ['LOCAL', 'OUTSTATION', 'AIRPORT'], true)) {
    $serviceType = 'OUTSTATION';
}

try {
    $pdo = Database::getConnection();

    // ---- Resolve pickup/drop to location ids -----------------------
    $fromId = isset($input['from_location_id']) ? (int)$input['from_location_id'] : 0;
    $toId   = isset($input['to_location_id']) ? (int)$input['to_location_id'] : 0;

    if ($fromId <= 0 && !empty($input['pickup'])) {
        $fromId = resolve_location_id($pdo, (string)$input['pickup']);
    }
    if ($toId <= 0 && !empty($input['drop'])) {
        $toId = resolve_location_id($pdo, (string)$input['drop']);
    }

    if ($fromId <= 0 || $toId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Could not recognise the pickup/drop location. Please pick one from the suggestions.']);
        exit;
    }

    $fromLoc = fetch_location($pdo, $fromId);
    $toLoc   = fetch_location($pdo, $toId);
    if (!$fromLoc || !$toLoc || $fromId === $toId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pickup and drop must be different active locations.']);
        exit;
    }

    // ---- Distance, driven entirely by km (requirement: price by km) ----
    $distance = $serviceType === 'LOCAL' ? 0.0 : DistanceService::getDistanceKm($fromId, $toId);

    // ---- Active cars + their rates for this service type ----------
        $carsStmt = $pdo->prepare("SELECT id, name, brand, model, category, seats, luggage_capacity, image, description
                FROM cars
                WHERE status = 1
                    AND NOT EXISTS (
                            SELECT 1 FROM bookings b
                            WHERE b.car_id = cars.id
                                AND b.booking_status IN ('CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP')
                                AND ? BETWEEN b.pickup_date AND COALESCE(b.return_date, b.pickup_date)
                    )
                ORDER BY seats ASC, name ASC");
        $carsStmt->execute([$pickupDate]);
    $cars = $carsStmt->fetchAll();

    $rateStmt = $pdo->prepare('SELECT * FROM car_rates WHERE car_id = ? AND service_type = ? AND status = 1');

    $results = [];
    foreach ($cars as $c) {
        $rateStmt->execute([$c['id'], $serviceType]);
        $rates = $rateStmt->fetchAll();

        if (!$rates) {
            continue; // this car has no pricing at all for this service
        }

        $entry = [
            'car_id'      => (int)$c['id'],
            'car_name'    => $c['name'],
            'brand'       => $c['brand'],
            'category'    => $c['category'],
            'seats'       => (int)$c['seats'],
            'luggage'     => (int)$c['luggage_capacity'],
            'image'       => $c['image'],
            'description' => $c['description'],
            'non_ac'      => null,
            'ac'          => null,
        ];

        foreach ($rates as $rate) {
            $fare = $tripType === 'ROUND_TRIP'
                ? FareService::calculateRoundTripFare($distance, $rate)
                : FareService::calculateOneWayFare($distance, $rate);

            $bucket = [
                'car_rate_id'      => (int)$rate['id'],
                'ac_type'          => $rate['ac_type'],
                'fuel_type'        => $rate['fuel_type'],
                'transmission'     => $rate['transmission'],
                'rate_per_km'      => (float)$rate['rate_per_km'],
                'minimum_km'       => (float)$rate['minimum_km'],
                'discount_amount'  => (float)$rate['discount_amount'],
                'fare'             => $fare,
                'fare_breakdown'   => $fare,
                'total'            => $fare['total'],
            ];

            if ($rate['ac_type'] === 'AC') {
                $entry['ac'] = $bucket;
            } else {
                $entry['non_ac'] = $bucket;
            }
        }

        $results[] = $entry;
    }

    echo json_encode([
        'success'     => true,
        'message'     => 'Cars found successfully',
        'service_type'=> $serviceType,
        'trip_type'   => $tripType,
        'distance_km' => $distance,
        'from'        => $fromLoc,
        'to'          => $toLoc,
        'cars'        => $results,
    ]);

} catch (Exception $e) {
    error_log('search.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while searching cars']);
}

function resolve_location_id(\PDO $pdo, string $term): int
{
    $term = trim($term);
    if ($term === '') return 0;

    $aliases = [
        'hubli' => 'Hubballi',
        'bengaluru' => 'Bangalore',
        'belgaum' => 'Belagavi',
    ];
    $term = $aliases[strtolower($term)] ?? $term;

    $stmt = $pdo->prepare('SELECT id FROM locations WHERE status = 1 AND city = ? LIMIT 1');
    $stmt->execute([$term]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    $stmt = $pdo->prepare('SELECT id FROM locations WHERE status = 1 AND city LIKE ? ORDER BY LENGTH(city) ASC LIMIT 1');
    $stmt->execute([$term . '%']);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    $stmt = $pdo->prepare('SELECT id FROM locations WHERE status = 1 AND city LIKE ? ORDER BY LENGTH(city) ASC LIMIT 1');
    $stmt->execute(['%' . $term . '%']);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : 0;
}

function fetch_location(\PDO $pdo, int $id): ?array
{
    if ($id <= 0) return null;
    $stmt = $pdo->prepare('SELECT id, city, state FROM locations WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
<?php
declare(strict_types=1);
/**
 * api/booking.php
 * AJAX handler for booking submissions. Moved from root booking.php
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/FareService.php';

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// --- Collect + sanitise ---------------------------------------------------
$tripType = strtoupper(clean($_POST['trip_type'] ?? 'LOCAL'));
$serviceType = strtoupper(clean($_POST['service_type'] ?? 'LOCAL'));
$tripType = strtoupper(clean($_POST['trip_type'] ?? 'ONE_WAY'));
$fromId = isset($_POST['from_location_id']) ? (int)$_POST['from_location_id'] : 0;
$toId = isset($_POST['to_location_id']) ? (int)$_POST['to_location_id'] : 0;
$pickup   = clean($_POST['pickup'] ?? '');
$drop     = clean($_POST['drop'] ?? '');
$date     = clean($_POST['pickup_date'] ?? $_POST['date'] ?? '');
$time     = clean($_POST['pickup_time'] ?? $_POST['time'] ?? '');
$return_date = clean($_POST['return_date'] ?? '');
$return_time = clean($_POST['return_time'] ?? '');
$carId = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
$variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$cabType  = clean($_POST['cab_type'] ?? 'hatchback');
$km       = (float) ($_POST['km'] ?? 0);
$name     = clean($_POST['name'] ?? '');
$phone    = clean($_POST['phone'] ?? '');
$email    = clean($_POST['email'] ?? '');
$special_instructions = clean($_POST['special_instructions'] ?? '');

// --- Validate ---------------------------------------------------------
$errors = [];
if ($pickup === '')                        $errors[] = 'Pickup location is required.';
if ($drop === '')                          $errors[] = 'Drop location is required.';
if ($date === '')                          $errors[] = 'Pickup date is required.';
if ($time === '')                          $errors[] = 'Pickup time is required.';
if ($name === '')                          $errors[] = 'Name is required.';
if (!preg_match('/^[0-9]{10}$/', $phone))  $errors[] = 'Enter a valid 10-digit phone number.';
if ($km <= 0 && ($fromId === 0 || $toId === 0))
                                            $errors[] = 'Distance must be provided or selectable locations must exist.';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

// --- Use PDO transaction to create booking in DB -------------------------
try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // resolve location ids if missing
    if ($fromId === 0 && $pickup !== '') {
        $s = $pdo->prepare('SELECT id FROM locations WHERE city LIKE ? LIMIT 1');
        $s->execute([ $pickup . '%' ]);
        $r = $s->fetch();
        if ($r) $fromId = (int)$r['id'];
    }
    if ($toId === 0 && $drop !== '') {
        $s = $pdo->prepare('SELECT id FROM locations WHERE city LIKE ? LIMIT 1');
        $s->execute([ $drop . '%' ]);
        $r = $s->fetch();
        if ($r) $toId = (int)$r['id'];
    }

    // distance lookup
    $distance = $km;
    if ($fromId && $toId) {
        $stmt = $pdo->prepare('SELECT distance_km FROM distances WHERE (from_location_id = ? AND to_location_id = ?) OR (from_location_id = ? AND to_location_id = ?) LIMIT 1');
        $stmt->execute([$fromId, $toId, $toId, $fromId]);
        $row = $stmt->fetch();
        if ($row) $distance = (float)$row['distance_km'];
    }

    // pick car/variant if not given (choose first active)
    if ($carId === 0 && $variantId === 0) {
        $cstmt = $pdo->query('SELECT c.id as car_id, v.id as variant_id FROM cars c JOIN car_variants v ON v.car_id = c.id WHERE c.status = 1 AND v.status = 1 LIMIT 1');
        $c = $cstmt->fetch();
        if ($c) { $carId = (int)$c['car_id']; $variantId = (int)$c['variant_id']; }
    }

    // check availability (simple): same car already booked for that date
    if ($carId) {
        $av = $pdo->prepare("SELECT id FROM bookings WHERE car_id = ? AND booking_status IN ('CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP') AND pickup_date = ? LIMIT 1");
        $av->execute([$carId, $date]);
        if ($av->fetch()) {
            $pdo->rollBack();
            json_response(false, 'Selected car is not available for the chosen date/time.');
        }
    }

    // fetch rate
    $rateStmt = $pdo->prepare('SELECT * FROM rates WHERE service_type = ? AND (variant_id = ? OR car_id = ? OR (variant_id IS NULL AND car_id IS NULL)) LIMIT 1');
    $rateStmt->execute([$serviceType, $variantId ?: null, $carId ?: null]);
    $rate = $rateStmt->fetch();
    if (!$rate) {
        // try a default rate for service
        $dstmt = $pdo->prepare('SELECT * FROM rates WHERE service_type = ? LIMIT 1');
        $dstmt->execute([$serviceType]);
        $rate = $dstmt->fetch();
    }

    if (!$rate) {
        $pdo->rollBack();
        json_response(false, 'No rate configured for selected service.');
    }

    // calculate fare
    if ($tripType === 'ROUND_TRIP' || strtolower($tripType) === 'round_trip') {
        $fareInfo = FareService::calculateRoundTripFare($distance, $rate);
    } else {
        $fareInfo = FareService::calculateOneWayFare($distance, $rate);
    }

    // create/find customer
    $custStmt = $pdo->prepare('SELECT id FROM customers WHERE mobile = ? LIMIT 1');
    $custStmt->execute([$phone]);
    $cust = $custStmt->fetch();
    if ($cust) {
        $customerId = (int)$cust['id'];
        $pdo->prepare('UPDATE customers SET name = ?, email = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $email, $customerId]);
    } else {
        $ins = $pdo->prepare('INSERT INTO customers (name, mobile, email) VALUES (?, ?, ?)');
        $ins->execute([$name, $phone, $email]);
        $customerId = (int)$pdo->lastInsertId();
    }

    // booking number
    $bookingNumber = 'JBL' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    // insert booking
    $bstmt = $pdo->prepare('INSERT INTO bookings
        (booking_number, customer_id, service_type, trip_type, from_location_id, to_location_id, pickup_date, pickup_time, return_date, return_time, car_id, variant_id, distance_km, base_fare, driver_allowance, toll_charge, permit_charge, night_charge, discount, tax_amount, total_amount, payment_status, booking_status, special_instructions)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

    $bstmt->execute([
        $bookingNumber,
        $customerId,
        $serviceType,
        $tripType,
        $fromId,
        $toId,
        $date,
        $time,
        $return_date ?: null,
        $return_time ?: null,
        $carId,
        $variantId ?: null,
        $distance,
        $fareInfo['base_fare'] ?? 0,
        $fareInfo['driver_allowance'] ?? 0,
        $fareInfo['toll'] ?? 0,
        $fareInfo['permit'] ?? 0,
        $fareInfo['night_charge'] ?? 0,
        $fareInfo['discount'] ?? 0,
        $fareInfo['tax_amount'] ?? 0,
        $fareInfo['total'] ?? 0,
        'PENDING',
        'PENDING',
        $special_instructions
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    // insert booking status history
    $hist = $pdo->prepare('INSERT INTO booking_status_history (booking_id, old_status, new_status, remarks) VALUES (?,?,?,?)');
    $hist->execute([$bookingId, null, 'PENDING', 'Booking created via web']);

    // create payment placeholder
    $pstmt = $pdo->prepare('INSERT INTO payments (booking_id, payment_reference, amount, payment_method, status) VALUES (?,?,?,?,?)');
    $pstmt->execute([$bookingId, null, $fareInfo['total'] ?? 0, 'CASH', 'PENDING']);

    $pdo->commit();

    json_response(true, 'Booking confirmed', ['booking_number' => $bookingNumber, 'booking_id' => $bookingId, 'total' => $fareInfo['total'] ?? 0]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Booking error: ' . $e->getMessage());
    http_response_code(500);
    json_response(false, 'Server error while creating booking.');
}

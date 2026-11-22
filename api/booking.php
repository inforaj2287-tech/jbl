<?php
declare(strict_types=1);
/**
 * api/booking.php
 * Creates a booking after the user taps "Book Now" on the search
 * results page (or submits the homepage quick-booking form).
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/FareService.php';
require_once __DIR__ . '/../services/DistanceService.php';

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

$input = $_POST;
if (empty($input)) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $input = $raw;
}

// --- Collect + sanitise ---------------------------------------------------
$serviceType = strtoupper(clean((string)($input['service_type'] ?? 'OUTSTATION')));
$tripType    = strtoupper(str_replace([' ', '-'], '_', clean((string)($input['trip_type'] ?? 'ONE_WAY'))));
$fromId      = isset($input['from_location_id']) ? (int)$input['from_location_id'] : 0;
$toId        = isset($input['to_location_id']) ? (int)$input['to_location_id'] : 0;
$pickup      = clean((string)($input['pickup'] ?? ''));
$drop        = clean((string)($input['drop'] ?? ''));
$date        = clean((string)($input['pickup_date'] ?? $input['date'] ?? ''));
$time        = clean((string)($input['pickup_time'] ?? $input['time'] ?? ''));
$returnDate  = clean((string)($input['return_date'] ?? ''));
$returnTime  = clean((string)($input['return_time'] ?? ''));
$carId       = isset($input['car_id']) ? (int)$input['car_id'] : 0;
$acType      = strtoupper(clean((string)($input['ac_type'] ?? 'AC'))) === 'NON_AC' ? 'NON_AC' : 'AC';
$name        = clean((string)($input['name'] ?? ''));
$phone       = clean((string)($input['phone'] ?? ''));
$email       = clean((string)($input['email'] ?? ''));
$specialInstructions = clean((string)($input['special_instructions'] ?? ''));

// --- Validate ---------------------------------------------------------
$errors = [];
if (!in_array($serviceType, ['LOCAL', 'OUTSTATION', 'AIRPORT'], true)) $errors[] = 'A valid service type is required.';
if (!in_array($tripType, ['ONE_WAY', 'ROUND_TRIP'], true)) $errors[] = 'A valid trip type is required.';
if ($serviceType === 'LOCAL' && $tripType === 'ROUND_TRIP') $errors[] = 'Round trips are available for outstation and airport services only.';
$pickupDateValue = DateTime::createFromFormat('!Y-m-d', $date);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    || !$pickupDateValue
    || $pickupDateValue->format('Y-m-d') !== $date) $errors[] = 'A valid pickup date is required.';
if ($date !== '' && $date < date('Y-m-d')) $errors[] = 'Pickup date cannot be in the past.';
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) $errors[] = 'A valid pickup time is required.';
if ($name === '')                          $errors[] = 'Name is required.';
if (!preg_match('/^[0-9]{10}$/', $phone))  $errors[] = 'Enter a valid 10-digit phone number.';
if ($carId <= 0)                           $errors[] = 'Please choose a car.';
if ($tripType === 'ROUND_TRIP') {
    $returnDateValue = DateTime::createFromFormat('!Y-m-d', $returnDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnDate)
        || !$returnDateValue
        || $returnDateValue->format('Y-m-d') !== $returnDate
        || $returnDate < $date) {
        $errors[] = 'A valid return date on or after the pickup date is required.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $returnTime)) $errors[] = 'A valid return time is required.';
    if ($returnDate === $date && $returnTime !== '' && $time !== ''
        && strtotime($returnDate . ' ' . $returnTime) <= strtotime($date . ' ' . $time)) {
        $errors[] = 'Return time must be after pickup time for a same-day return.';
    }
} else {
    $returnDate = '';
    $returnTime = '';
}

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // resolve location ids if only names were sent
    if ($fromId <= 0 && $pickup !== '') {
        $s = $pdo->prepare('SELECT id FROM locations WHERE city LIKE ? LIMIT 1');
        $s->execute([$pickup . '%']);
        $r = $s->fetch();
        if ($r) $fromId = (int)$r['id'];
    }
    if ($toId <= 0 && $drop !== '') {
        $s = $pdo->prepare('SELECT id FROM locations WHERE city LIKE ? LIMIT 1');
        $s->execute([$drop . '%']);
        $r = $s->fetch();
        if ($r) $toId = (int)$r['id'];
    }

    if ($fromId <= 0 || $toId <= 0) {
        $pdo->rollBack();
        json_response(false, 'Pickup/drop location could not be identified.');
    }

    $distance = $serviceType === 'LOCAL' ? 0.0 : DistanceService::getDistanceKm($fromId, $toId);

    // fetch rate for the chosen car + ac_type + service
    $rateStmt = $pdo->prepare('SELECT * FROM car_rates WHERE car_id = ? AND ac_type = ? AND service_type = ? AND status = 1 LIMIT 1');
    $rateStmt->execute([$carId, $acType, $serviceType]);
    $rate = $rateStmt->fetch();

    if (!$rate) {
        $pdo->rollBack();
        json_response(false, 'Selected car/AC option is not available for this route.');
    }

    // simple availability check: same car already booked that date
    $av = $pdo->prepare("SELECT id FROM bookings
        WHERE car_id = ?
          AND booking_status IN ('CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP')
          AND pickup_date <= ?
          AND COALESCE(return_date, pickup_date) >= ?
        LIMIT 1");
    $av->execute([$carId, $returnDate !== '' ? $returnDate : $date, $date]);
    if ($av->fetch()) {
        $pdo->rollBack();
        json_response(false, 'Selected car is not available for the chosen date.');
    }

    $fareInfo = $tripType === 'ROUND_TRIP'
        ? FareService::calculateRoundTripFare($distance, $rate)
        : FareService::calculateOneWayFare($distance, $rate);

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

    $bookingNumber = 'JBL' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    $bstmt = $pdo->prepare('INSERT INTO bookings
        (booking_number, customer_id, service_type, trip_type, from_location_id, to_location_id, pickup_date, pickup_time, return_date, return_time, car_id, ac_type, distance_km, base_fare, driver_allowance, toll_charge, permit_charge, night_charge, discount, tax_amount, total_amount, payment_status, booking_status, special_instructions)
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
        $returnDate !== '' ? $returnDate : null,
        $returnTime !== '' ? $returnTime : null,
        $carId,
        $acType,
        $distance,
        $fareInfo['base_fare'] + $fareInfo['distance_fare'],
        $fareInfo['driver_allowance'],
        $fareInfo['toll_charge'],
        $fareInfo['permit_charge'],
        $fareInfo['night_charge'],
        $fareInfo['discount'],
        $fareInfo['tax_amount'],
        $fareInfo['total'],
        'PENDING',
        'PENDING',
        $specialInstructions,
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    $hist = $pdo->prepare('INSERT INTO booking_status_history (booking_id, old_status, new_status, remarks) VALUES (?,?,?,?)');
    $hist->execute([$bookingId, null, 'PENDING', 'Booking created via web']);

    $pstmt = $pdo->prepare('INSERT INTO payments (booking_id, payment_reference, amount, payment_method, status) VALUES (?,?,?,?,?)');
    $pstmt->execute([$bookingId, null, $fareInfo['total'], 'CASH', 'PENDING']);

    $pdo->commit();

    json_response(true, 'Booking confirmed', [
        'booking_number' => $bookingNumber,
        'booking_id' => $bookingId,
        'total' => $fareInfo['total'],
    ]);

} catch (DistanceUnavailableException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    json_response(false, $e->getMessage());
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Booking error: ' . $e->getMessage());
    http_response_code(500);
    json_response(false, 'Server error while creating booking.');
}
<?php
/**
 * booking.php
 * Handles the booking widget's AJAX submission (see script.js -> submitForm).
 * Validates input, recalculates the fare server-side, stores the booking,
 * and returns a JSON result the front end renders inline.
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// --- Collect + sanitise ---------------------------------------------------
$tripType = clean($_POST['trip_type'] ?? '');
$pickup   = clean($_POST['pickup'] ?? '');
$drop     = clean($_POST['drop'] ?? '');
$date     = clean($_POST['date'] ?? '');
$time     = clean($_POST['time'] ?? '');
$cabType  = clean($_POST['cab_type'] ?? 'hatchback');
$km       = (float) ($_POST['km'] ?? 0);
$name     = clean($_POST['name'] ?? '');
$phone    = clean($_POST['phone'] ?? '');

// --- Validate ---------------------------------------------------------
$errors = [];
if ($pickup === '')                        $errors[] = 'Pickup location is required.';
if ($drop === '')                          $errors[] = 'Drop location is required.';
if ($date === '')                          $errors[] = 'Pickup date is required.';
if ($time === '')                          $errors[] = 'Pickup time is required.';
if ($name === '')                          $errors[] = 'Name is required.';
if (!preg_match('/^[0-9]{10}$/', $phone))  $errors[] = 'Enter a valid 10-digit phone number.';
if ($km <= 0)                              $errors[] = 'Distance must be greater than zero.';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

// --- Recalculate fare server-side (never trust the client's total) -------
$rate = cab_rate($cabType);
$fare = round($km * $rate);

// --- Build + store record --------------------------------------------
$bookingId = 'RC' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

$record = [
    'booking_id' => $bookingId,
    'trip_type'  => $tripType ?: 'local',
    'pickup'     => $pickup,
    'drop'       => $drop,
    'date'       => $date,
    'time'       => $time,
    'cab_type'   => $cabType,
    'km'         => $km,
    'rate_per_km'=> $rate,
    'fare'       => $fare,
    'name'       => $name,
    'phone'      => $phone,
    'created_at' => date('c'),
];

$saved = append_json_record(BOOKINGS_FILE, $record);

if (!$saved) {
    json_response(false, 'Could not save your booking right now. Please call dispatch at 080-4684-4684.');
}

// In production: trigger an SMS/WhatsApp notification to dispatch + customer here.

json_response(true, "Booking {$bookingId} confirmed. Estimated fare ₹{$fare}. Our dispatch team will send driver details shortly.", [
    'booking_id' => $bookingId,
    'fare' => $fare,
]);

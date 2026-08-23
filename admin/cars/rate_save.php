<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../config/Database.php';
use Config\Database;

$pdo = Database::getConnection();
$action = $_REQUEST['action'] ?? 'save';
$carId = (int)($_REQUEST['car_id'] ?? 0);

if ($action === 'delete') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM car_rates WHERE id = ?');
        $stmt->execute([$id]);
    }
    header('Location: rates.php?car_id=' . $carId . '&deleted=1');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$serviceType = in_array($_POST['service_type'] ?? '', ['OUTSTATION','LOCAL','AIRPORT'], true) ? $_POST['service_type'] : 'OUTSTATION';
$acType = ($_POST['ac_type'] ?? '') === 'NON_AC' ? 'NON_AC' : 'AC';
$fuelType = trim($_POST['fuel_type'] ?? 'Diesel');
$transmission = trim($_POST['transmission'] ?? 'Manual');
$ratePerKm = (float)($_POST['rate_per_km'] ?? 0);
$minimumKm = (float)($_POST['minimum_km'] ?? 0);
$baseFare = (float)($_POST['base_fare'] ?? 0);
$driverAllowance = (float)($_POST['driver_allowance'] ?? 0);
$nightCharge = (float)($_POST['night_charge'] ?? 0);
$tollCharge = (float)($_POST['toll_charge'] ?? 0);
$permitCharge = (float)($_POST['permit_charge'] ?? 0);
$discountAmount = (float)($_POST['discount_amount'] ?? 0);
$taxPercent = (float)($_POST['tax_percent'] ?? 5);
$status = (int)($_POST['status'] ?? 1);

try {
    if ($carId <= 0 || $ratePerKm < 0) {
        throw new RuntimeException('Invalid data.');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE car_rates SET service_type=?, ac_type=?, fuel_type=?, transmission=?, rate_per_km=?, minimum_km=?, base_fare=?, driver_allowance=?, night_charge=?, toll_charge=?, permit_charge=?, discount_amount=?, tax_percent=?, status=? WHERE id=? AND car_id=?');
        $stmt->execute([$serviceType, $acType, $fuelType, $transmission, $ratePerKm, $minimumKm, $baseFare, $driverAllowance, $nightCharge, $tollCharge, $permitCharge, $discountAmount, $taxPercent, $status, $id, $carId]);
    } else {
        // upsert: one row per car+ac_type+service_type
        $stmt = $pdo->prepare('SELECT id FROM car_rates WHERE car_id=? AND ac_type=? AND service_type=? LIMIT 1');
        $stmt->execute([$carId, $acType, $serviceType]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE car_rates SET fuel_type=?, transmission=?, rate_per_km=?, minimum_km=?, base_fare=?, driver_allowance=?, night_charge=?, toll_charge=?, permit_charge=?, discount_amount=?, tax_percent=?, status=? WHERE id=?');
            $stmt->execute([$fuelType, $transmission, $ratePerKm, $minimumKm, $baseFare, $driverAllowance, $nightCharge, $tollCharge, $permitCharge, $discountAmount, $taxPercent, $status, $existing['id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO car_rates (car_id, service_type, ac_type, fuel_type, transmission, rate_per_km, minimum_km, base_fare, driver_allowance, night_charge, toll_charge, permit_charge, discount_amount, tax_percent, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$carId, $serviceType, $acType, $fuelType, $transmission, $ratePerKm, $minimumKm, $baseFare, $driverAllowance, $nightCharge, $tollCharge, $permitCharge, $discountAmount, $taxPercent, $status]);
        }
    }

    header('Location: rates.php?car_id=' . $carId . '&saved=1');
    exit;
} catch (Exception $e) {
    error_log('rate_save error: ' . $e->getMessage());
    header('Location: rates.php?car_id=' . $carId . '&error=1');
    exit;
}
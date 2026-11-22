<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query(
        "SELECT c.id, c.name, c.category, c.seats, c.luggage_capacity, c.image,
                MAX(CASE WHEN r.ac_type = 'AC' THEN 1 ELSE 0 END) AS ac_available
         FROM cars c
         LEFT JOIN car_rates r ON r.car_id = c.id AND r.status = 1
         WHERE c.status = 1
         GROUP BY c.id, c.name, c.category, c.seats, c.luggage_capacity, c.image
         ORDER BY c.seats ASC, c.name ASC"
    );

    $cars = [];
    foreach ($stmt->fetchAll() as $car) {
        $cars[] = [
            'id' => (int)$car['id'],
            'name' => $car['name'],
            'category' => $car['category'],
            'seats' => (int)$car['seats'],
            'luggage_capacity' => (int)$car['luggage_capacity'],
            'image' => $car['image'],
            'ac_available' => (bool)$car['ac_available'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $cars]);
} catch (Throwable $e) {
    error_log('Fleet API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Vehicle options are temporarily unavailable.']);
}

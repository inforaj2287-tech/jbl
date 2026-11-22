<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

class DistanceUnavailableException extends RuntimeException
{
}

/**
 * Works out the km distance between two saved locations, which is
 * the number every fare in the app is ultimately based on.
 *
 * Lookup order:
 *   1. An exact override in the `distances` table (either direction).
 *   2. A great-circle (haversine) estimate from each location's
 *      lat/lng, bumped up by a road-detour factor since roads are
 *      never perfectly straight.
 *   3. An explicit error if the route has no distance override or
 *      usable coordinates, rather than inventing a distance.
 */
class DistanceService
{
    /** Straight-line distance is always shorter than actual road distance. */
    private const ROAD_DETOUR_FACTOR = 1.18;

    public static function getDistanceKm(int $fromLocationId, int $toLocationId): float
    {
        return self::getDistanceDetails($fromLocationId, $toLocationId)['distance_km'];
    }

    public static function getDistanceDetails(int $fromLocationId, int $toLocationId): array
    {
        if ($fromLocationId === $toLocationId) {
            return ['distance_km' => 0.0, 'source' => 'same_location'];
        }

        $pdo = Database::getConnection();

        // 1. Exact override
        $stmt = $pdo->prepare(
            'SELECT distance_km FROM distances
             WHERE (from_location_id = ? AND to_location_id = ?)
                OR (from_location_id = ? AND to_location_id = ?)
             ORDER BY CASE WHEN from_location_id = ? AND to_location_id = ? THEN 0 ELSE 1 END
             LIMIT 1'
        );
        $stmt->execute([
            $fromLocationId, $toLocationId,
            $toLocationId, $fromLocationId,
            $fromLocationId, $toLocationId,
        ]);
        $row = $stmt->fetch();
        if ($row) {
            return ['distance_km' => (float)$row['distance_km'], 'source' => 'manual'];
        }

        // 2. Haversine estimate from lat/lng
        $locStmt = $pdo->prepare('SELECT id, latitude, longitude FROM locations WHERE id IN (?, ?)');
        $locStmt->execute([$fromLocationId, $toLocationId]);
        $locs = [];
        foreach ($locStmt->fetchAll() as $r) {
            $locs[(int)$r['id']] = $r;
        }

        $from = $locs[$fromLocationId] ?? null;
        $to = $locs[$toLocationId] ?? null;

        if ($from && $to && $from['latitude'] !== null && $from['longitude'] !== null
            && $to['latitude'] !== null && $to['longitude'] !== null) {
            $km = self::haversineKm(
                (float)$from['latitude'], (float)$from['longitude'],
                (float)$to['latitude'], (float)$to['longitude']
            );
            return [
                'distance_km' => round($km * self::ROAD_DETOUR_FACTOR, 2),
                'source' => 'estimate',
            ];
        }

        throw new DistanceUnavailableException(
            'No route distance is available for these locations. Please contact JB Travels for a quote.'
        );
    }

    private static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
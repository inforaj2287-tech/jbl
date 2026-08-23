<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

/**
 * Works out the km distance between two saved locations, which is
 * the number every fare in the app is ultimately based on.
 *
 * Lookup order:
 *   1. An exact override in the `distances` table (either direction).
 *   2. A great-circle (haversine) estimate from each location's
 *      lat/lng, bumped up by a road-detour factor since roads are
 *      never perfectly straight.
 *   3. A fixed fallback so the site never breaks if a city is
 *      missing coordinates.
 */
class DistanceService
{
    /** Straight-line distance is always shorter than actual road distance. */
    private const ROAD_DETOUR_FACTOR = 1.18;

    /** Used only when neither an override nor coordinates exist. */
    private const FALLBACK_KM = 100.0;

    public static function getDistanceKm(int $fromLocationId, int $toLocationId): float
    {
        if ($fromLocationId === $toLocationId) {
            return 0.0;
        }

        $pdo = Database::getConnection();

        // 1. Exact override
        $stmt = $pdo->prepare(
            'SELECT distance_km FROM distances
             WHERE (from_location_id = ? AND to_location_id = ?)
                OR (from_location_id = ? AND to_location_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$fromLocationId, $toLocationId, $toLocationId, $fromLocationId]);
        $row = $stmt->fetch();
        if ($row) {
            return (float)$row['distance_km'];
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
            return round($km * self::ROAD_DETOUR_FACTOR, 2);
        }

        // 3. Fallback
        return self::FALLBACK_KM;
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
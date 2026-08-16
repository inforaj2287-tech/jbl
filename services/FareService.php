<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class FareService
{
    public static function calculateOneWayFare(float $distanceKm, array $rateRow): array
    {
        $ratePerKm = (float)($rateRow['rate_per_km'] ?? 0);
        $minimumKm = (float)($rateRow['minimum_km'] ?? 0);
        $driverAllowance = (float)($rateRow['driver_allowance'] ?? 0);
        $toll = (float)($rateRow['toll_charge'] ?? 0);
        $permit = (float)($rateRow['permit_charge'] ?? 0);
        $night = (float)($rateRow['night_charge'] ?? 0);
        $discount = (float)($rateRow['discount'] ?? 0);
        $taxPercent = (float)($rateRow['tax_percent'] ?? 0);
        $extraKmRate = (float)($rateRow['extra_km_rate'] ?? 0);

        $chargeableKm = max($distanceKm, $minimumKm);
        $baseFare = round($chargeableKm * $ratePerKm, 2);

        $subtotal = $baseFare + $driverAllowance + $toll + $permit + $night;
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $total = round($subtotal + $taxAmount - $discount, 2);

        return [
            'distance_km' => $distanceKm,
            'chargeable_km' => $chargeableKm,
            'rate_per_km' => $ratePerKm,
            'base_fare' => $baseFare,
            'driver_allowance' => $driverAllowance,
            'toll' => $toll,
            'permit' => $permit,
            'night_charge' => $night,
            'discount' => $discount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'total' => $total
        ];
    }

    public static function calculateRoundTripFare(float $distanceKm, array $rateRow, int $returnMultiplier = 2): array
    {
        // Simple round-trip: assume returnMultiplier * distance (2 for return)
        return self::calculateOneWayFare($distanceKm * $returnMultiplier, $rateRow);
    }

    // Local and airport fares can be implemented similarly when needed
}

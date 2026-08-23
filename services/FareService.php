<?php
declare(strict_types=1);

/**
 * Turns a car_rates row + a km distance into a full fare breakdown.
 * This is the single place price-by-kilometer math happens, so the
 * search page, the booking API and any admin "preview fare" screen
 * all agree on the same number.
 */
class FareService
{
    public static function calculateFare(float $distanceKm, array $rateRow, int $tripMultiplier = 1): array
    {
        $distanceKm = $distanceKm * $tripMultiplier;

        $ratePerKm       = (float)($rateRow['rate_per_km'] ?? 0);
        $minimumKm       = (float)($rateRow['minimum_km'] ?? 0);
        $baseFare        = (float)($rateRow['base_fare'] ?? 0);
        $driverAllowance = (float)($rateRow['driver_allowance'] ?? 0);
        $night           = (float)($rateRow['night_charge'] ?? 0);
        $toll            = (float)($rateRow['toll_charge'] ?? 0);
        $permit          = (float)($rateRow['permit_charge'] ?? 0);
        $discount        = (float)($rateRow['discount_amount'] ?? 0);
        $taxPercent      = (float)($rateRow['tax_percent'] ?? 0);

        // The core "price by kilometers" calculation.
        $chargeableKm = max($distanceKm, $minimumKm);
        $distanceFare = round($chargeableKm * $ratePerKm, 2);

        $subtotal = $baseFare + $distanceFare + $driverAllowance + $toll + $permit + $night;
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $total = round($subtotal + $taxAmount - $discount, 2);
        if ($total < 0) $total = 0.0;

        return [
            'distance_km'       => round($distanceKm, 2),
            'chargeable_km'     => $chargeableKm,
            'rate_per_km'       => $ratePerKm,
            'base_fare'         => $baseFare,
            'distance_fare'     => $distanceFare,
            'driver_allowance'  => $driverAllowance,
            'toll_charge'       => $toll,
            'permit_charge'     => $permit,
            'night_charge'      => $night,
            'discount'          => $discount,
            'tax_percent'       => $taxPercent,
            'tax_amount'        => $taxAmount,
            'total'             => $total,
        ];
    }

    public static function calculateOneWayFare(float $distanceKm, array $rateRow): array
    {
        return self::calculateFare($distanceKm, $rateRow, 1);
    }

    public static function calculateRoundTripFare(float $distanceKm, array $rateRow): array
    {
        return self::calculateFare($distanceKm, $rateRow, 2);
    }
}
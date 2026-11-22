-- One-time migration for databases created before tax defaulted to zero.
-- Reset legacy 5% rows while preserving any other explicitly configured rate.
ALTER TABLE car_rates
    MODIFY tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00;

UPDATE car_rates
SET tax_percent = 0.00
WHERE tax_percent = 5.00;

-- ============================================================
-- JB Travels — schema.sql
-- Full database schema for the car booking website + admin panel.
-- Safe to re-run: drops tables (in FK-safe order) and recreates them.
-- ============================================================

DROP TABLE IF EXISTS booking_status_history;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS car_rates;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS distances;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS admins;

-- ------------------------------------------------------------
-- Admin login (mobile number + password, single role, no
-- self-registration — accounts are created directly in the DB
-- or by another admin from the admin panel).
-- ------------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Locations — every pickup/drop city. latitude/longitude are
-- used to auto-estimate km distance when no exact route has
-- been entered in `distances` (see services/DistanceService.php).
-- ------------------------------------------------------------
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'India',
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_city (city)
);

-- ------------------------------------------------------------
-- Optional exact road-distance overrides between two cities.
-- When a route is not listed here, the app estimates distance
-- from lat/lng automatically.
-- ------------------------------------------------------------
CREATE TABLE distances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_location_id INT NOT NULL,
    to_location_id INT NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_route (from_location_id, to_location_id),
    FOREIGN KEY (from_location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (to_location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Customers who book a ride (created on first booking).
-- ------------------------------------------------------------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile)
);

-- ------------------------------------------------------------
-- Cars — every field the admin fills in when adding a car.
-- This is exactly what the search-results page displays:
-- name, seats, category (badge), image.
-- ------------------------------------------------------------
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    category ENUM('HATCHBACK','SEDAN','SUV','MUV','PREMIUM') NOT NULL DEFAULT 'SEDAN',
    seats INT DEFAULT 4,
    luggage_capacity INT DEFAULT 2,
    registration_number VARCHAR(30),
    image VARCHAR(255),
    description TEXT,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Car rates — one row per (car, AC/Non-AC, service type).
-- This drives BOTH the fare and everything shown per column in
-- the search-results table: NON-AC / AC price, the "Save Upto"
-- coupon pill (discount_amount) and the "Fare Details" popup.
-- When a car has no row for a given ac_type + service, that
-- column shows "Not Available" (exactly like the screenshot).
-- ------------------------------------------------------------
CREATE TABLE car_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    ac_type ENUM('AC','NON_AC') NOT NULL DEFAULT 'AC',
    service_type ENUM('LOCAL','OUTSTATION','AIRPORT') NOT NULL DEFAULT 'OUTSTATION',
    fuel_type VARCHAR(50) DEFAULT 'Diesel',
    transmission VARCHAR(50) DEFAULT 'Manual',
    rate_per_km DECIMAL(10,2) NOT NULL DEFAULT 0,   -- price per km, this is the core "price by distance" figure
    minimum_km DECIMAL(10,2) NOT NULL DEFAULT 0,    -- minimum billable km for the trip
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 0,     -- flat charge added on top (e.g. local package base)
    driver_allowance DECIMAL(10,2) NOT NULL DEFAULT 0,
    night_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
    toll_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
    permit_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0, -- powers the "Save Upto ₹x" coupon pill
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_car_ac_service (car_id, ac_type, service_type),
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Bookings created when a user taps "Book Now".
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    trip_type VARCHAR(50) NOT NULL,
    from_location_id INT NOT NULL,
    to_location_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    return_date DATE NULL,
    return_time TIME NULL,
    car_id INT NOT NULL,
    ac_type ENUM('AC','NON_AC') NOT NULL DEFAULT 'AC',
    distance_km DECIMAL(10,2) DEFAULT 0,
    base_fare DECIMAL(10,2) DEFAULT 0,
    driver_allowance DECIMAL(10,2) DEFAULT 0,
    toll_charge DECIMAL(10,2) DEFAULT 0,
    permit_charge DECIMAL(10,2) DEFAULT 0,
    night_charge DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_status ENUM('PENDING','PAID','FAILED','REFUNDED') DEFAULT 'PENDING',
    booking_status ENUM('PENDING','CONFIRMED','ASSIGNED','DRIVER_ASSIGNED','ON_TRIP','COMPLETED','CANCELLED') DEFAULT 'PENDING',
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (car_id) REFERENCES cars(id),
    FOREIGN KEY (from_location_id) REFERENCES locations(id),
    FOREIGN KEY (to_location_id) REFERENCES locations(id),
    INDEX idx_booking_number (booking_number),
    INDEX idx_customer (customer_id),
    INDEX idx_pickup_date (pickup_date)
);

CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT NOT NULL,
    payment_reference VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    status ENUM('PENDING','SUCCESS','FAILED','REFUNDED') DEFAULT 'PENDING',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE booking_status_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    remarks TEXT,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- ============================================================
-- Seed data
-- ============================================================

-- Default admin login: mobile 9999999999 / password admin123
-- (change it from the admin panel after first login)
INSERT INTO admins (name, mobile, password_hash) VALUES
('Admin', '9999999999', '$2y$10$SZsy.N4mxLHrcDOEehqScOuYv4nnSkWmr6ggzdVSO1B4hF93Ex5TK');

-- Locations with lat/lng so distance can be auto-estimated
-- for any route that doesn't have an explicit row in `distances`.
INSERT INTO locations (city, state, latitude, longitude) VALUES
('Belagavi','Karnataka',15.8497,74.4977),
('Hubballi','Karnataka',15.3647,75.1240),
('Dharwad','Karnataka',15.4589,75.0078),
('Gokak','Karnataka',16.1667,74.8333),
('Nipani','Karnataka',16.4000,74.3833),
('Khanapur','Karnataka',15.6333,74.5000),
('Bangalore','Karnataka',12.9716,77.5946),
('Mysore','Karnataka',12.2958,76.6394),
('Mangalore','Karnataka',12.9141,74.8560),
('Chennai','Tamil Nadu',13.0827,80.2707),
('Hyderabad','Telangana',17.3850,78.4867),
('Pune','Maharashtra',18.5204,73.8567),
('Mumbai','Maharashtra',19.0760,72.8777),
('Goa','Goa',15.2993,74.1240),
('Coimbatore','Tamil Nadu',11.0168,76.9558),
('Kochi','Kerala',9.9312,76.2673);

-- A couple of known/verified road distances (override the
-- straight-line estimate for accuracy on the most common routes).
INSERT INTO distances (from_location_id, to_location_id, distance_km)
SELECT a.id, b.id, d.km FROM
(SELECT 'Belagavi' f, 'Hubballi' t, 105.00 km
 UNION ALL SELECT 'Belagavi','Goa',225.00
 UNION ALL SELECT 'Belagavi','Bangalore',500.00
 UNION ALL SELECT 'Belagavi','Pune',335.00
 UNION ALL SELECT 'Hubballi','Bangalore',410.00) d
JOIN locations a ON a.city = d.f
JOIN locations b ON b.city = d.t;

-- Cars (matches the fleet shown on the site: Alto / Dzire / Innova, etc.)
INSERT INTO cars (name, brand, model, category, seats, luggage_capacity, image, description, status) VALUES
('Maruti Alto','Maruti','Alto','HATCHBACK',4,1,'','Compact hatchback, ideal for short local trips.',1),
('Swift Dzire','Maruti','Dzire','SEDAN',4,2,'','Comfortable sedan, most popular for outstation trips.',1),
('Honda Amaze','Honda','Amaze','SEDAN',4,2,'','Spacious sedan with a smooth ride.',1),
('Toyota Innova','Toyota','Innova','SUV',7,3,'','Roomy SUV for group and family travel.',1),
('Toyota Innova Crysta','Toyota','Innova Crysta','SUV',7,3,'','Premium SUV with extra legroom and comfort.',1);

-- Rates — rate_per_km drives the distance-based price shown on
-- the search-results page. Leaving a row out for a given
-- car + ac_type makes that column show "Not Available".
INSERT INTO car_rates (car_id, ac_type, service_type, fuel_type, transmission, rate_per_km, minimum_km, driver_allowance, tax_percent, discount_amount) VALUES
-- Maruti Alto
(1,'NON_AC','OUTSTATION','Petrol','Manual', 9.50, 130, 250, 5.00, 0),
(1,'AC','OUTSTATION','Petrol','Manual',10.50, 130, 250, 5.00, 0),
(1,'NON_AC','LOCAL','Petrol','Manual', 9.00, 0, 100, 5.00, 0),
(1,'AC','LOCAL','Petrol','Manual', 10.00, 0, 100, 5.00, 0),
-- Swift Dzire
(2,'NON_AC','OUTSTATION','Diesel','Manual',10.50, 130, 300, 5.00, 0),
(2,'AC','OUTSTATION','Diesel','Manual',11.50, 130, 300, 5.00, 0),
(2,'AC','LOCAL','Diesel','Manual',12.00, 0, 150, 5.00, 0),
-- Honda Amaze (AC only, on purpose — shows "Not Available" for Non-AC)
(3,'AC','OUTSTATION','Petrol','Automatic',12.00, 130, 300, 5.00, 0),
-- Toyota Innova (AC only)
(4,'AC','OUTSTATION','Diesel','Manual',16.00, 130, 500, 5.00, 200),
(4,'AC','LOCAL','Diesel','Manual',17.00, 0, 250, 5.00, 0),
-- Toyota Innova Crysta (AC only, premium)
(5,'AC','OUTSTATION','Diesel','Automatic',19.00, 130, 600, 5.00, 300);

-- End of schema

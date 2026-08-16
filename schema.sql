-- JB Travels schema.sql

DROP TABLE IF EXISTS booking_status_history;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rates;
DROP TABLE IF EXISTS car_variants;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS distances;

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'India',
    airport_name VARCHAR(150),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_city (city)
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile)
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(150) DEFAULT 'Administrator',
    role VARCHAR(50) DEFAULT 'admin',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    category ENUM('HATCHBACK','SEDAN','SUV','MUV','PREMIUM') NOT NULL,
    seats INT DEFAULT 4,
    luggage_capacity INT DEFAULT 1,
    image VARCHAR(255),
    description TEXT,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE car_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    variant_name VARCHAR(100),
    ac_type ENUM('AC','NON_AC') DEFAULT 'AC',
    fuel_type VARCHAR(50),
    transmission VARCHAR(50),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

CREATE TABLE rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(50) NOT NULL,
    trip_type VARCHAR(50),
    car_id INT,
    variant_id INT,
    rate_per_km DECIMAL(10,2) DEFAULT 0,
    minimum_km DECIMAL(10,2) DEFAULT 0,
    driver_allowance DECIMAL(10,2) DEFAULT 0,
    night_charge DECIMAL(10,2) DEFAULT 0,
    extra_km_rate DECIMAL(10,2) DEFAULT 0,
    extra_hour_rate DECIMAL(10,2) DEFAULT 0,
    toll_charge DECIMAL(10,2) DEFAULT 0,
    permit_charge DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE distances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_location_id INT NOT NULL,
    to_location_id INT NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_route (from_location_id, to_location_id)
);

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
    variant_id INT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Seed locations
INSERT INTO locations (city, state) VALUES
('Bangalore','Karnataka'),
('Hubli','Karnataka'),
('Belgaum','Karnataka'),
('Mysore','Karnataka'),
('Mangalore','Karnataka'),
('Chennai','Tamil Nadu'),
('Hyderabad','Telangana'),
('Pune','Maharashtra'),
('Mumbai','Maharashtra'),
('Goa','Goa'),
('Coimbatore','Tamil Nadu'),
('Kochi','Kerala');

-- Seed services
INSERT INTO services (name, code) VALUES
('Local','LOCAL'),
('Outstation','OUTSTATION'),
('Airport','AIRPORT'),
('Package','PACKAGE');

-- Seed cars
INSERT INTO cars (name, brand, category, seats, image) VALUES
('Maruti Alto','Maruti','HATCHBACK',4,''),
('Wagon R','Maruti','HATCHBACK',4,''),
('Swift Dzire','Maruti','SEDAN',4,''),
('Honda Amaze','Honda','SEDAN',4,''),
('Toyota Etios','Toyota','SEDAN',4,''),
('Maruti Ertiga','Maruti','MUV',6,''),
('Toyota Innova','Toyota','SUV',7,''),
('Toyota Innova Crysta','Toyota','SUV',7,'');

-- Seed car variants (AC and NON_AC)
INSERT INTO car_variants (car_id, variant_name, ac_type, fuel_type, transmission) VALUES
(1,'Alto AC','AC','Petrol','Manual'),
(1,'Alto Non-AC','NON_AC','Petrol','Manual'),
(3,'Swift Dzire AC','AC','Diesel','Manual'),
(3,'Swift Dzire Non-AC','NON_AC','Diesel','Manual'),
(7,'Innova AC','AC','Diesel','Manual');

-- Seed rates (development)
INSERT INTO rates (service_type, trip_type, car_id, variant_id, rate_per_km, minimum_km, driver_allowance, toll_charge, permit_charge, discount, tax_percent) VALUES
('OUTSTATION','ONE_WAY',NULL,NULL,9.50,0,0,0,0,0,5.00),
('OUTSTATION','ONE_WAY',3,NULL,10.50,130,300,0,0,200,5.00),
('OUTSTATION','ONE_WAY',7,NULL,16.00,130,500,0,0,200,5.00),
('LOCAL',NULL,NULL,NULL,12.00,0,50,0,0,0,5.00),
('AIRPORT',NULL,NULL,NULL,0,0,0,0,0,0,5.00);

-- Seed distances for key routes (sample numbers)
INSERT INTO distances (from_location_id, to_location_id, distance_km) VALUES
(1,2,420.00), -- Bangalore -> Hubli (example)
(1,3,260.00), -- Bangalore -> Belgaum
(1,4,150.00), -- Bangalore -> Mysore
(1,5,350.00);

-- End of schema

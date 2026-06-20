-- ============================================
-- Farm Manager - Database Schema
-- MySQL 5.7+ / 8.0+
-- ============================================

CREATE DATABASE IF NOT EXISTS ton_db1
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ton_db1;

-- ============================================
-- USERS & AUTH
-- ============================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50) DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('farmer','crew_manager','admin') NOT NULL DEFAULT 'farmer',
    first_name VARCHAR(100) DEFAULT '',
    last_name VARCHAR(100) DEFAULT '',
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    language_pref VARCHAR(10) DEFAULT 'en',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) DEFAULT '',
    user_agent TEXT,
    status ENUM('success','failed') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT DEFAULT 0,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_table_record (table_name, record_id)
) ENGINE=InnoDB;

-- ============================================
-- FARMERS (extended profile)
-- ============================================

CREATE TABLE farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) DEFAULT '',
    date_of_birth DATE,
    gender ENUM('male','female','other','') DEFAULT '',
    address_line1 VARCHAR(255) DEFAULT '',
    address_line2 VARCHAR(255) DEFAULT '',
    city VARCHAR(100) DEFAULT '',
    state VARCHAR(100) DEFAULT '',
    country VARCHAR(100) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    alt_phone VARCHAR(50) DEFAULT '',
    id_proof_type VARCHAR(50) DEFAULT '',
    id_proof_number VARCHAR(100) DEFAULT '',
    id_proof_file VARCHAR(255) DEFAULT '',
    language_pref VARCHAR(10) DEFAULT 'en',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- LAND PARCELS
-- ============================================

CREATE TABLE land_parcels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(255) DEFAULT 'Main Parcel',
    survey_number VARCHAR(100) DEFAULT '',
    gps_lat DECIMAL(10,7) DEFAULT NULL,
    gps_lng DECIMAL(10,7) DEFAULT NULL,
    soil_type VARCHAR(100) DEFAULT '',
    water_source VARCHAR(100) DEFAULT '',
    irrigation_method VARCHAR(100) DEFAULT '',
    acreage DECIMAL(10,2) DEFAULT 0.00,
    ownership_type ENUM('owned','leased','shared','government') DEFAULT 'owned',
    lease_start DATE,
    lease_end DATE,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- PLANTING
-- ============================================

CREATE TABLE plantings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    land_id INT NOT NULL,
    farmer_id INT NOT NULL,
    crop_name VARCHAR(255) NOT NULL,
    seed_variety VARCHAR(255) DEFAULT '',
    planting_date DATE NOT NULL,
    expected_harvest_date DATE,
    actual_harvest_date DATE,
    growth_stage VARCHAR(100) DEFAULT 'seed',
    area_planted DECIMAL(10,2) DEFAULT 0.00,
    expected_yield DECIMAL(10,2) DEFAULT 0.00,
    actual_yield DECIMAL(10,2) DEFAULT 0.00,
    weather_advisory TEXT,
    notes TEXT,
    status ENUM('active','harvested','failed','cancelled') DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_land_id (land_id),
    FOREIGN KEY (land_id) REFERENCES land_parcels(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- INPUTS (fertilizers, pesticides, organic)
-- ============================================

CREATE TABLE inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    farmer_id INT NOT NULL,
    type ENUM('fertilizer','pesticide','organic','other') NOT NULL DEFAULT 'fertilizer',
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(255) DEFAULT '',
    quantity DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'kg',
    cost DECIMAL(12,2) DEFAULT 0.00,
    application_date DATE,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_planting_id (planting_id),
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (planting_id) REFERENCES plantings(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- CREWS & TASKS
-- ============================================

CREATE TABLE crews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    role VARCHAR(100) DEFAULT 'worker',
    daily_wage DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE crew_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crew_id INT NOT NULL,
    planting_id INT,
    task_date DATE NOT NULL,
    task_description VARCHAR(500) NOT NULL,
    hours_worked DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_crew_id (crew_id),
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    FOREIGN KEY (planting_id) REFERENCES plantings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE crew_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crew_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','half_day','leave') DEFAULT 'present',
    wages_paid DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_crew_id (crew_id),
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- PACKING
-- ============================================

CREATE TABLE packing_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    farmer_id INT NOT NULL,
    harvest_date DATE NOT NULL,
    weight_kg DECIMAL(10,2) DEFAULT 0.00,
    grade VARCHAR(50) DEFAULT 'A',
    packaging_type VARCHAR(100) DEFAULT '',
    lot_number VARCHAR(100) NOT NULL,
    quality_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lot_number (lot_number),
    INDEX idx_planting_id (planting_id),
    FOREIGN KEY (planting_id) REFERENCES plantings(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- INVENTORY
-- ============================================

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    packing_batch_id INT NOT NULL,
    farmer_id INT NOT NULL,
    warehouse_location VARCHAR(255) DEFAULT '',
    quantity_in DECIMAL(10,2) DEFAULT 0.00,
    quantity_out DECIMAL(10,2) DEFAULT 0.00,
    current_stock DECIMAL(10,2) DEFAULT 0.00,
    alert_threshold DECIMAL(10,2) DEFAULT 10.00,
    last_movement_date DATE,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_packing_batch_id (packing_batch_id),
    FOREIGN KEY (packing_batch_id) REFERENCES packing_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- CUSTOMERS
-- ============================================

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    address VARCHAR(500) DEFAULT '',
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ORDERS
-- ============================================

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    farmer_id INT NOT NULL,
    order_date DATE NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    delivery_status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    invoice_number VARCHAR(100) DEFAULT '',
    shipping_address VARCHAR(500) DEFAULT '',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    packing_batch_id INT,
    description VARCHAR(255) DEFAULT '',
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(12,2) DEFAULT 0.00,
    total_price DECIMAL(12,2) DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (packing_batch_id) REFERENCES packing_batches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TRANSACTIONS (payments ledger)
-- ============================================

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    type ENUM('payment_received','payment_made','refund','adjustment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reference VARCHAR(255) DEFAULT '',
    related_order_id INT,
    payment_method VARCHAR(50) DEFAULT 'cash',
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    FOREIGN KEY (related_order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Farm Manager - Seed Data
-- ============================================

USE farm_manager;

-- Admin user (password: admin123)
INSERT INTO users (email, phone, password_hash, role, first_name, last_name, status) VALUES
('admin@farmapp.com', '555-0100', '$2y$12$dhg.X0HCkoFE4GAEVCJEI.kukHwgqSBEisvz67AZDYy8zoWMVzhmu', 'admin', 'System', 'Admin', 'active');

-- Farmer users (password: farmer123)
INSERT INTO users (email, phone, password_hash, role, first_name, last_name, status) VALUES
('john@farm.com', '555-0101', '$2y$12$dhg.X0HCkoFE4GAEVCJEI.kukHwgqSBEisvz67AZDYy8zoWMVzhmu', 'farmer', 'John', 'Smith', 'active'),
('maria@farm.com', '555-0102', '$2y$12$dhg.X0HCkoFE4GAEVCJEI.kukHwgqSBEisvz67AZDYy8zoWMVzhmu', 'farmer', 'Maria', 'Garcia', 'active');

-- Crew manager (password: crew123)
INSERT INTO users (email, phone, password_hash, role, first_name, last_name, status) VALUES
('crew@farm.com', '555-0103', '$2y$12$dhg.X0HCkoFE4GAEVCJEI.kukHwgqSBEisvz67AZDYy8zoWMVzhmu', 'crew_manager', 'Carlos', 'Rodriguez', 'active');

-- Farmer Profiles
INSERT INTO farmers (user_id, first_name, last_name, date_of_birth, gender, address_line1, city, state, country, postal_code, phone) VALUES
(2, 'John', 'Smith', '1985-03-15', 'male', '123 Farm Road', 'Springfield', 'Illinois', 'USA', '62701', '555-0101'),
(3, 'Maria', 'Garcia', '1990-07-22', 'female', '456 Harvest Lane', 'Sacramento', 'California', 'USA', '95814', '555-0102');

-- Land Parcels
INSERT INTO land_parcels (farmer_id, name, survey_number, gps_lat, gps_lng, soil_type, water_source, irrigation_method, acreage, ownership_type) VALUES
(1, 'North Field', 'SRV-2024-001', 39.7817, -89.6501, 'Loam', 'Well', 'Drip', 50.00, 'owned'),
(1, 'South Field', 'SRV-2024-002', 39.7800, -89.6520, 'Clay Loam', 'River', 'Sprinkler', 30.00, 'leased'),
(2, 'Vineyard Plot', 'SRV-2024-003', 38.5816, -121.4944, 'Sandy Loam', 'Well', 'Drip', 25.00, 'owned');

-- Plantings
INSERT INTO plantings (land_id, farmer_id, crop_name, seed_variety, planting_date, expected_harvest_date, growth_stage, area_planted, status) VALUES
(1, 1, 'Corn', 'Pioneer P1197', '2025-04-01', '2025-09-15', 'vegetative', 45.00, 'active'),
(2, 1, 'Soybeans', 'Asgrow AG36X6', '2025-05-01', '2025-10-01', 'germination', 28.00, 'active'),
(3, 2, 'Grapes', 'Cabernet Sauvignon', '2025-03-15', '2025-08-30', 'flowering', 20.00, 'active');

-- Inputs
INSERT INTO inputs (planting_id, farmer_id, type, name, brand, quantity, unit, cost, application_date) VALUES
(1, 1, 'fertilizer', 'Nitrogen Blend', 'AgriGrow', 500.00, 'kg', 1200.00, '2025-04-15'),
(1, 1, 'pesticide', 'Corn Borer Control', 'CropShield', 10.00, 'liters', 350.00, '2025-05-01'),
(3, 2, 'organic', 'Compost Mix', 'BioFarm', 200.00, 'kg', 600.00, '2025-04-01');

-- Crews
INSERT INTO crews (farmer_id, name, phone, role, daily_wage, status) VALUES
(1, 'Mike Johnson', '555-0201', 'field_worker', 150.00, 'active'),
(1, 'Sarah Lee', '555-0202', 'supervisor', 200.00, 'active'),
(2, 'Pablo Herrera', '555-0203', 'field_worker', 140.00, 'active');

-- Crew Tasks
INSERT INTO crew_tasks (crew_id, planting_id, task_date, task_description, hours_worked, status) VALUES
(1, 1, '2025-04-15', 'Apply fertilizer to North Field', 6.00, 'completed'),
(2, 1, '2025-04-15', 'Supervise fertilizer application', 6.00, 'completed'),
(3, 3, '2025-04-01', 'Prepare vineyard rows for planting', 8.00, 'completed');

-- Packing Batches (from previous harvest)
INSERT INTO packing_batches (planting_id, farmer_id, harvest_date, weight_kg, grade, packaging_type, lot_number) VALUES
(1, 1, '2024-09-20', 1500.00, 'A', '25kg Bags', 'LOT-2024-0001'),
(3, 2, '2024-09-01', 800.00, 'Premium', 'Crates', 'LOT-2024-0002');

-- Inventory
INSERT INTO inventory (packing_batch_id, farmer_id, warehouse_location, quantity_in, quantity_out, current_stock, alert_threshold) VALUES
(1, 1, 'Warehouse A - Bay 3', 1500.00, 200.00, 1300.00, 100.00),
(2, 2, 'Cellar Storage', 800.00, 100.00, 700.00, 50.00);

-- Customers
INSERT INTO customers (farmer_id, name, email, phone, address, credit_limit) VALUES
(1, 'FreshMart Groceries', 'buyer@freshmart.com', '555-0301', '789 Market Street, Springfield, IL', 50000.00),
(1, 'GreenLeaf Co-op', 'orders@greenleaf.org', '555-0302', '321 Organic Ave, Chicago, IL', 30000.00),
(2, 'Napa Wine Distributors', 'procurement@napawine.com', '555-0303', '100 Wine Trail, Napa, CA', 75000.00);

-- Orders
INSERT INTO orders (customer_id, farmer_id, order_date, total_amount, delivery_status, invoice_number) VALUES
(1, 1, '2025-01-15', 3000.00, 'delivered', 'INV-2025-0001'),
(3, 2, '2025-02-01', 8000.00, 'processing', 'INV-2025-0002');

-- Order Items
INSERT INTO order_items (order_id, packing_batch_id, description, quantity, unit_price, total_price) VALUES
(1, 1, 'Corn - Grade A - 25kg Bags', 200.00, 15.00, 3000.00),
(2, 2, 'Cabernet Grapes - Premium Crates', 100.00, 80.00, 8000.00);

-- Transactions
INSERT INTO transactions (farmer_id, type, amount, reference, related_order_id, payment_method, transaction_date) VALUES
(1, 'payment_received', 3000.00, 'Payment for INV-2025-0001', 1, 'bank_transfer', '2025-01-20'),
(1, 'payment_made', 1200.00, 'Fertilizer purchase - AgriGrow', NULL, 'cash', '2025-04-15'),
(2, 'payment_received', 4000.00, 'Partial payment for INV-2025-0002', 2, 'check', '2025-02-10');

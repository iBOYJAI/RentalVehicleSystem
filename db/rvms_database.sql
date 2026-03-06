-- Rental Vehicle Management System Database
-- Created for XAMPP MySQL
-- Database: rvms_db

CREATE DATABASE IF NOT EXISTS rvms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rvms_db;

-- Users Table (Admin, Staff, Customer)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vehicle Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    vehicle_name VARCHAR(100) NOT NULL,
    brand VARCHAR(50),
    model VARCHAR(50),
    year INT,
    color VARCHAR(30),
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    chassis_number VARCHAR(100),
    engine_number VARCHAR(100),
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    seating_capacity INT DEFAULT 4,
    daily_rate DECIMAL(10, 2) NOT NULL,
    weekly_rate DECIMAL(10, 2),
    monthly_rate DECIMAL(10, 2),
    image VARCHAR(255),
    description TEXT,
    status ENUM('available', 'rented', 'maintenance', 'inactive') DEFAULT 'available',
    mileage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_registration (registration_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    license_number VARCHAR(50),
    license_expiry DATE,
    id_proof VARCHAR(255),
    address_proof VARCHAR(255),
    status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_code (customer_code),
    INDEX idx_phone (phone),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pickup_location VARCHAR(255),
    dropoff_location VARCHAR(255),
    daily_rate DECIMAL(10, 2) NOT NULL,
    total_days INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    tax DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    advance_payment DECIMAL(10, 2) DEFAULT 0,
    status ENUM('pending', 'approved', 'active', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking_number (booking_number),
    INDEX idx_customer (customer_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_number VARCHAR(20) UNIQUE NOT NULL,
    payment_type ENUM('advance', 'full', 'partial', 'refund') DEFAULT 'full',
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') DEFAULT 'cash',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_payment_number (payment_number),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    booking_id INT NOT NULL,
    customer_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax DECIMAL(10, 2) DEFAULT 0,
    discount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    status ENUM('draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_booking (booking_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance/Damage Log Table
CREATE TABLE IF NOT EXISTS maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    booking_id INT,
    maintenance_type ENUM('service', 'repair', 'damage', 'inspection', 'other') DEFAULT 'service',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cost DECIMAL(10, 2) DEFAULT 0,
    maintenance_date DATE NOT NULL,
    completed_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    service_provider VARCHAR(100),
    next_service_date DATE,
    mileage_at_service INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_status (status),
    INDEX idx_maintenance_date (maintenance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Logs Table for Automation Tracking
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL,
    action VARCHAR(255) NOT NULL,
    reference_id VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin User
-- NOTE: Default password is "password" for both admin and staff accounts
-- IMPORTANT: Change passwords immediately after first login via Settings page
INSERT INTO users (username, email, password, full_name, role, phone, status) VALUES
('admin', 'admin@rvms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', '1234567890', 'active');

-- Insert Default Staff User
INSERT INTO users (username, email, password, full_name, role, phone, status) VALUES
('staff', 'staff@rvms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Member', 'staff', '1234567891', 'active');

-- Insert Default Customer User
INSERT INTO users (username, email, password, full_name, role, phone, status) VALUES
('customer', 'customer@rvms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sample Customer', 'customer', '1234567892', 'active');

-- Insert Vehicle Categories
INSERT INTO categories (name, description, icon, status) VALUES
('Car', 'Sedan, Hatchback, SUV cars', 'car', 'active'),
('Bike', 'Motorcycles and scooters', 'bike', 'active'),
('Van', 'Commercial vans and minivans', 'van', 'active'),
('Lorry', 'Trucks and heavy vehicles', 'lorry', 'active'),
('Bus', 'Passenger buses', 'bus', 'active');

-- Insert Sample Vehicles
INSERT INTO vehicles (category_id, vehicle_name, brand, model, year, color, registration_number, chassis_number, engine_number, fuel_type, seating_capacity, daily_rate, weekly_rate, monthly_rate, description, status) VALUES
(1, 'Toyota Camry 2023', 'Toyota', 'Camry', 2023, 'White', 'ABC-1234', 'CH123456789', 'ENG123456', 'petrol', 5, 2500.00, 15000.00, 60000.00, 'Comfortable sedan with modern features', 'available'),
(1, 'Honda Civic 2022', 'Honda', 'Civic', 2022, 'Black', 'XYZ-5678', 'CH987654321', 'ENG987654', 'petrol', 5, 2200.00, 13000.00, 55000.00, 'Reliable and fuel-efficient', 'available'),
(2, 'Yamaha R15', 'Yamaha', 'R15', 2023, 'Blue', 'BIKE-001', 'CHBIKE001', 'ENGBIKE001', 'petrol', 2, 800.00, 4500.00, 18000.00, 'Sporty motorcycle', 'available'),
(3, 'Toyota Hiace', 'Toyota', 'Hiace', 2022, 'White', 'VAN-001', 'CHVAN001', 'ENGVAN001', 'diesel', 12, 3500.00, 20000.00, 80000.00, 'Commercial van for group transport', 'available'),
(4, 'Tata Lorry', 'Tata', 'Lorry 407', 2021, 'Yellow', 'LRY-001', 'CHLRY001', 'ENGLRY001', 'diesel', 3, 5000.00, 28000.00, 110000.00, 'Heavy duty truck', 'available');

-- Insert Sample Customers
INSERT INTO customers (customer_code, full_name, email, phone, address, license_number, license_expiry, status) VALUES
('CUST001', 'John Doe', 'john.doe@email.com', '9876543210', '123 Main Street, City', 'DL1234567890', '2025-12-31', 'active'),
('CUST002', 'Jane Smith', 'jane.smith@email.com', '9876543211', '456 Park Avenue, City', 'DL9876543210', '2026-06-30', 'active'),
('CUST003', 'Robert Johnson', 'robert.j@email.com', '9876543212', '789 Oak Road, City', 'DL1122334455', '2025-09-15', 'active');

-- Insert Sample Settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'Rental Vehicle Management System', 'text', 'Company Name'),
('company_address', '123 Business Street, City, Country', 'text', 'Company Address'),
('company_phone', '+1 234 567 8900', 'text', 'Company Phone'),
('company_email', 'info@rvms.com', 'email', 'Company Email'),
('tax_rate', '10', 'number', 'Tax Rate Percentage'),
('currency', 'INR', 'text', 'Currency Code'),
('invoice_prefix', 'INV-', 'text', 'Invoice Number Prefix'),
('booking_prefix', 'BK-', 'text', 'Booking Number Prefix');

-- Create Views for Dashboard and Reconciliation
CREATE OR REPLACE VIEW dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM vehicles WHERE status = 'available') as available_vehicles,
    (SELECT COUNT(*) FROM vehicles WHERE status = 'rented') as rented_vehicles,
    (SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'approved', 'active')) as active_bookings,
    (SELECT COUNT(*) FROM customers WHERE status = 'active') as active_customers,
    (SELECT COUNT(*) FROM invoices WHERE status IN ('sent', 'partially_paid')) as pending_invoices,
    (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE DATE(created_at) = CURDATE()) as today_revenue,
    (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) as month_revenue;

-- Reconciliation View: Highlights mismatches between Invoices and actually completed Payments
CREATE OR REPLACE VIEW payment_reconciliation AS
SELECT 
    i.invoice_number,
    b.booking_number,
    c.full_name as customer_name,
    i.total_amount as invoice_amount,
    i.paid_amount as invoice_recorded_paid,
    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = i.booking_id AND p.status = 'completed') as actual_payments_sum,
    (i.total_amount - (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = i.booking_id AND p.status = 'completed')) as balance_mismatch,
    i.status as invoice_status
FROM invoices i
JOIN bookings b ON i.booking_id = b.id
JOIN customers c ON i.customer_id = c.id;


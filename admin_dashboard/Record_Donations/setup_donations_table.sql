-- Create donations table for Church Management System
-- Run this script in phpMyAdmin or MySQL to create the table

CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    donation_type ENUM('offering', 'projectoffering', 'tithe', 'welfare', 'expense') NOT NULL,
    date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    
    -- Offering specific fields
    service_type VARCHAR(100) DEFAULT NULL,
    service_time TIME DEFAULT NULL,
    counted_by VARCHAR(100) DEFAULT NULL,
    
    -- Project Offering specific fields
    project_name VARCHAR(200) DEFAULT NULL,
    
    -- Tithe specific fields
    member_name VARCHAR(200) DEFAULT NULL,
    member_email VARCHAR(200) DEFAULT NULL,
    receipt_number VARCHAR(100) DEFAULT NULL,
    
    -- Welfare specific fields
    payment_period VARCHAR(50) DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    
    -- Expense specific fields
    category VARCHAR(100) DEFAULT NULL,
    custom_category VARCHAR(200) DEFAULT NULL,
    vendor VARCHAR(200) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    
    -- Common fields
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_donation_type (donation_type),
    INDEX idx_date (date),
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

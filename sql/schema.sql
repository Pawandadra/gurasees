-- Gur Asees Ayurveda — complete database schema
--
-- Fresh install:
--   1. Create the MySQL database and set credentials in .env
--   2. php scripts/install_db.php
--   3. php scripts/seed_users.php

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Users (admin, manager, receptionist)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    role ENUM('admin', 'manager', 'receptionist') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Patients
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(12) NULL,
    name VARCHAR(120) NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    delivery_address TEXT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_gst_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'upi', 'card', 'bank', 'other') NULL,
    payment_status ENUM('paid', 'pending', 'partial') NULL,
    payment_paid_amount DECIMAL(10, 2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_patient_code (patient_code),
    INDEX idx_phone (phone),
    INDEX idx_name (name),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Symptoms (manager-defined) and patient links
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS symptoms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(120) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_symptom_label (label),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_symptoms (
    patient_id INT UNSIGNED NOT NULL,
    symptom_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (patient_id, symptom_id),
    CONSTRAINT fk_ps_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_symptom FOREIGN KEY (symptom_id) REFERENCES symptoms(id) ON DELETE RESTRICT,
    INDEX idx_ps_symptom (symptom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Medicines (inventory) and visit dispensing
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_medicine_name (name),
    INDEX idx_medicine_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    visited_at DATETIME NOT NULL,
    notes TEXT NULL,
    visit_charge DECIMAL(10, 2) NOT NULL DEFAULT 0,
    visit_gst DECIMAL(10, 2) NOT NULL DEFAULT 0,
    medicine_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    medicine_gst DECIMAL(10, 2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    recorded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_visit_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_visit_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visit_patient_date (patient_id, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_medicines (
    visit_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (visit_id, medicine_id),
    CONSTRAINT fk_vm_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_vm_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    INDEX idx_vm_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Clinic settings (payment defaults, GST rates, visit charge default)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS clinic_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO clinic_settings (setting_key, setting_value) VALUES
    ('payment.default_amount', '0'),
    ('payment.default_method', 'cash'),
    ('payment.default_status', 'pending'),
    ('gst.registration_percent', '5.00'),
    ('gst.visit_charge_percent', '5.00'),
    ('gst.medicine_percent', '5.00'),
    ('visit.default_charge', '0.00');

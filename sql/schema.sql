-- Gur Asees Ayurveda — complete database schema (fresh install)
--
-- 1. Create the MySQL database and set credentials in .env
-- 2. php scripts/install_db.php
-- 3. php scripts/seed_users.php   (creates admin only; add other users in the app)

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
    additional_phone VARCHAR(15) NULL,
    address TEXT NOT NULL,
    delivery_address TEXT NULL,
    remarks TEXT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_gst_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'upi', 'card', 'bank', 'other') NULL,
    payment_status ENUM('paid', 'pending', 'partial') NULL,
    payment_paid_amount DECIMAL(10, 2) NULL,
    total_balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_patient_code (patient_code),
    INDEX idx_phone (phone),
    INDEX idx_additional_phone (additional_phone),
    INDEX idx_name (name),
    INDEX idx_created (created_at),
    INDEX idx_patients_gender (gender),
    INDEX idx_patients_age (age)
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

CREATE TABLE IF NOT EXISTS patient_profile_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    edited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_by INT UNSIGNED NULL,
    snapshot JSON NOT NULL,
    CONSTRAINT fk_pph_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_pph_user FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pph_patient_edited (patient_id, edited_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Medicines (inventory) and visit dispensing
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    kind ENUM('unit', 'bulk') NOT NULL DEFAULT 'unit',
    unit_price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    portion_size_ml INT UNSIGNED NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_medicine_name (name),
    INDEX idx_medicine_active (is_active, sort_order),
    INDEX idx_medicine_kind (kind, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    visited_at DATETIME NOT NULL,
    notes TEXT NULL,
    delivery_method ENUM('self', 'by_bus', 'courier') NOT NULL DEFAULT 'self',
    visit_charge DECIMAL(10, 2) NOT NULL DEFAULT 0,
    visit_gst DECIMAL(10, 2) NOT NULL DEFAULT 0,
    medicine_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    medicine_gst DECIMAL(10, 2) NOT NULL DEFAULT 0,
    courier_charge DECIMAL(10, 2) NOT NULL DEFAULT 0,
    courier_gst DECIMAL(10, 2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    courier_dispatched_at DATETIME NULL,
    courier_dispatched_by INT UNSIGNED NULL,
    courier_status ENUM('pending', 'sent', 'canceled') NULL DEFAULT NULL,
    payment_method ENUM('cash', 'upi', 'card', 'bank', 'other') NULL,
    payment_status ENUM('paid', 'pending', 'partial') NULL,
    payment_paid_amount DECIMAL(10, 2) NULL,
    recorded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_visit_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_visit_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_visit_courier_user FOREIGN KEY (courier_dispatched_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visit_patient_date (patient_id, visited_at),
    INDEX idx_visit_visited_at (visited_at),
    INDEX idx_visit_recorded_by (recorded_by),
    INDEX idx_visit_courier_status (courier_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_medicines (
    visit_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    courier_quantity INT UNSIGNED NOT NULL DEFAULT 0,
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
    ('visit.default_charge', '0.00'),
    ('visit.default_payment_method', 'cash'),
    ('visit.default_payment_status', 'paid'),
    ('courier.default_charge', '0.00'),
    ('gst.courier_percent', '5.00');

-- ---------------------------------------------------------------------------
-- Stock purchase bills (bill header, line items, optional attachment)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS stock_bills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(64) NOT NULL,
    register_number VARCHAR(64) NOT NULL,
    supplier VARCHAR(255) NOT NULL,
    bill_date DATE NOT NULL,
    delivery_date DATE NULL,
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    file_stored_name VARCHAR(255) NULL,
    file_original_name VARCHAR(255) NULL,
    file_mime VARCHAR(120) NULL,
    file_size INT UNSIGNED NULL,
    submitted_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_bill_user FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_stock_bill_number (bill_number),
    UNIQUE KEY uk_stock_bill_register_number (register_number),
    INDEX idx_stock_bill_date (bill_date DESC),
    INDEX idx_stock_bill_delivery (delivery_date),
    INDEX idx_stock_bill_submitted (submitted_by, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_bill_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(12, 3) NOT NULL DEFAULT 0,
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_stock_item_bill FOREIGN KEY (bill_id) REFERENCES stock_bills(id) ON DELETE CASCADE,
    INDEX idx_stock_item_bill (bill_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

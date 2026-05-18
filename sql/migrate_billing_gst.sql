-- GST and visit billing columns.
-- Run: php scripts/migrate_billing_gst.php

SET NAMES utf8mb4;

INSERT INTO clinic_settings (setting_key, setting_value) VALUES
    ('gst.registration_percent', '5.00'),
    ('gst.visit_charge_percent', '5.00'),
    ('gst.medicine_percent', '5.00'),
    ('visit.default_charge', '0.00')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'patients'
      AND COLUMN_NAME = 'payment_gst_amount'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE patients ADD COLUMN payment_gst_amount DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER payment_amount',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'visits'
      AND COLUMN_NAME = 'visit_charge'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE visits
        ADD COLUMN visit_charge DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER notes,
        ADD COLUMN visit_gst DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER visit_charge,
        ADD COLUMN medicine_gst DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER medicine_total,
        ADD COLUMN grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER medicine_gst',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE visits
SET grand_total = medicine_total
WHERE grand_total = 0 AND medicine_total > 0;

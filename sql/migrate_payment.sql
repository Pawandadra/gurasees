-- Payment settings and patient payment columns.
-- Run: php scripts/migrate_payment.php

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clinic_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'patients'
      AND COLUMN_NAME = 'payment_amount'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE patients
        ADD COLUMN payment_amount DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER delivery_address,
        ADD COLUMN payment_method ENUM(''cash'', ''upi'', ''card'', ''bank'', ''other'') NULL AFTER payment_amount,
        ADD COLUMN payment_status ENUM(''paid'', ''pending'', ''partial'') NULL AFTER payment_method,
        ADD COLUMN payment_paid_amount DECIMAL(10, 2) NULL AFTER payment_status',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO clinic_settings (setting_key, setting_value) VALUES
    ('payment.default_amount', '0'),
    ('payment.default_method', 'cash'),
    ('payment.default_status', 'paid');

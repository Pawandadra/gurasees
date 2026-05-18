-- Add symptoms support to an existing Gur Asees Ayurveda database.
-- Run: php scripts/migrate_symptoms.php

SET NAMES utf8mb4;

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

-- delivery_address for databases created before this column existed
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'patients'
      AND COLUMN_NAME = 'delivery_address'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE patients ADD COLUMN delivery_address TEXT NULL AFTER address',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bulk liquid syrups and portioning into sellable bottles. Apply: php scripts/migrate_db.php

SET NAMES utf8mb4;

ALTER TABLE medicines
    ADD COLUMN kind ENUM('unit', 'bulk') NOT NULL DEFAULT 'unit' AFTER name,
    ADD COLUMN bulk_source_id INT UNSIGNED NULL AFTER stock_quantity,
    ADD COLUMN portion_size_ml INT UNSIGNED NULL AFTER bulk_source_id,
    ADD CONSTRAINT fk_medicine_bulk_source FOREIGN KEY (bulk_source_id) REFERENCES medicines(id) ON DELETE SET NULL,
    ADD INDEX idx_medicine_kind (kind, is_active),
    ADD INDEX idx_medicine_bulk_source (bulk_source_id);

CREATE TABLE IF NOT EXISTS medicine_portion_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bulk_medicine_id INT UNSIGNED NOT NULL,
    sellable_medicine_id INT UNSIGNED NOT NULL,
    portion_size_ml INT UNSIGNED NOT NULL,
    bottles_created INT UNSIGNED NOT NULL,
    ml_used INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_portion_bulk FOREIGN KEY (bulk_medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    CONSTRAINT fk_portion_sellable FOREIGN KEY (sellable_medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT,
    CONSTRAINT fk_portion_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_portion_bulk (bulk_medicine_id),
    INDEX idx_portion_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

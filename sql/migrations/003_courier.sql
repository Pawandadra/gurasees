-- Courier delivery for visit medicines. Apply: php scripts/migrate_db.php

SET NAMES utf8mb4;

ALTER TABLE visit_medicines
    ADD COLUMN courier_quantity INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE visits
    ADD COLUMN courier_dispatched_at DATETIME NULL,
    ADD COLUMN courier_dispatched_by INT UNSIGNED NULL,
    ADD CONSTRAINT fk_visit_courier_user FOREIGN KEY (courier_dispatched_by) REFERENCES users(id) ON DELETE SET NULL;

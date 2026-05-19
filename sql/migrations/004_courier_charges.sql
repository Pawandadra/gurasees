-- Courier charge on visits. Apply: php scripts/migrate_db.php

SET NAMES utf8mb4;

ALTER TABLE visits
    ADD COLUMN courier_charge DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER medicine_gst,
    ADD COLUMN courier_gst DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER courier_charge;

INSERT IGNORE INTO clinic_settings (setting_key, setting_value) VALUES
    ('courier.default_charge', '0.00'),
    ('gst.courier_percent', '5.00');

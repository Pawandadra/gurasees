-- Visit payment method and status. Apply: php scripts/migrate_db.php

SET NAMES utf8mb4;

ALTER TABLE visits
    ADD COLUMN payment_method ENUM('cash', 'upi', 'card', 'bank', 'other') NULL AFTER grand_total,
    ADD COLUMN payment_status ENUM('paid', 'pending', 'partial') NULL AFTER payment_method,
    ADD COLUMN payment_paid_amount DECIMAL(10, 2) NULL AFTER payment_status;

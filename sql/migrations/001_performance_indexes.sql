-- Performance indexes. Safe to re-run: duplicate index errors are ignored by migrate_db.php.
-- Apply: php scripts/migrate_db.php

SET NAMES utf8mb4;

ALTER TABLE patients ADD INDEX idx_patients_gender (gender);
ALTER TABLE patients ADD INDEX idx_patients_age (age);
ALTER TABLE visits ADD INDEX idx_visit_visited_at (visited_at);
ALTER TABLE visits ADD INDEX idx_visit_recorded_by (recorded_by);

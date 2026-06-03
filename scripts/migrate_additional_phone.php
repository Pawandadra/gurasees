<?php

declare(strict_types=1);

/**
 * CLI: php scripts/migrate_additional_phone.php
 * Adds optional additional_phone column to existing patients tables.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $pdo = db();
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'patients'
           AND COLUMN_NAME = 'additional_phone'"
    );
    $exists = (int) $stmt->fetchColumn() > 0;

    if ($exists) {
        echo "Column patients.additional_phone already exists.\n";
        exit(0);
    }

    $pdo->exec(
        'ALTER TABLE patients
         ADD COLUMN additional_phone VARCHAR(15) NULL AFTER phone,
         ADD INDEX idx_additional_phone (additional_phone)'
    );

    echo "Added patients.additional_phone successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

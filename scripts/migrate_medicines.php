<?php

declare(strict_types=1);

/**
 * CLI: php scripts/migrate_medicines.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$sqlFile = dirname(__DIR__) . '/sql/migrate_medicines.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "migrate_medicines.sql not found.\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read migration.\n");
    exit(1);
}

try {
    db()->exec($sql);
    echo "Medicines tables migrated successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

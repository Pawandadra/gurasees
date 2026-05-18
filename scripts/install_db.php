<?php

declare(strict_types=1);

/**
 * CLI: php scripts/install_db.php
 * Creates tables from sql/schema.sql using .env credentials.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$sqlFile = dirname(__DIR__) . '/sql/schema.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "schema.sql not found.\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read schema.\n");
    exit(1);
}

try {
    $pdo = db();
    $pdo->exec($sql);
    echo "Database schema installed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

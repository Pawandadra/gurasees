<?php

declare(strict_types=1);

/**
 * CLI: php scripts/migrate_db.php
 * Applies sql/migrations/*.sql in order (idempotent where supported).
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$dir = dirname(__DIR__) . '/sql/migrations';
if (!is_dir($dir)) {
    fwrite(STDERR, "No migrations directory.\n");
    exit(1);
}

$files = glob($dir . '/*.sql');
if ($files === false || $files === []) {
    echo "No migration files found.\n";
    exit(0);
}

sort($files);
$pdo = db();

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, 'Could not read ' . basename($file) . ".\n");
        exit(1);
    }

    echo 'Applying ' . basename($file) . "…\n";
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }

        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // 1061 = duplicate key name (index already exists)
            if ((string) $e->getCode() !== '42000' && !str_contains($e->getMessage(), '1061')) {
                fwrite(STDERR, 'Error in ' . basename($file) . ': ' . $e->getMessage() . "\n");
                exit(1);
            }
        }
    }
}

echo "Migrations applied successfully.\n";

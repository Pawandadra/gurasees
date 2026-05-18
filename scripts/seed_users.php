<?php

declare(strict_types=1);

/**
 * CLI: php scripts/seed_users.php
 * Creates default users (change passwords after first login).
 */
require dirname(__DIR__) . '/app/bootstrap.php';

$defaults = [
    ['username' => 'admin', 'password' => 'Admin@123', 'name' => 'Administrator', 'role' => 'admin'],
    ['username' => 'manager', 'password' => 'Manager@123', 'name' => 'Clinic Manager', 'role' => 'manager'],
    ['username' => 'reception', 'password' => 'Reception@123', 'name' => 'Reception Desk', 'role' => 'receptionist'],
];

try {
    $pdo = db();
    $check = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ((int) $check > 0) {
        echo "Users already exist. Skipping seed.\n";
        exit(0);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, name, role) VALUES (:u, :p, :n, :r)'
    );

    foreach ($defaults as $row) {
        $stmt->execute([
            'u' => $row['username'],
            'p' => password_hash($row['password'], PASSWORD_DEFAULT),
            'n' => $row['name'],
            'r' => $row['role'],
        ]);
        echo "Created: {$row['username']} ({$row['role']})\n";
    }

    echo "Done. Change default passwords in production.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

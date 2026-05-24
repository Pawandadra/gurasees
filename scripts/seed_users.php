<?php

declare(strict_types=1);

/**
 * CLI: php scripts/seed_users.php
 * Creates the initial admin account when the users table is empty.
 * Managers and receptionists are added later via Users (admin only).
 */
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = [
    'username' => 'admin',
    'password' => 'Admin@123',
    'name' => 'Administrator',
    'role' => 'admin',
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
    $stmt->execute([
        'u' => $admin['username'],
        'p' => password_hash($admin['password'], PASSWORD_DEFAULT),
        'n' => $admin['name'],
        'r' => $admin['role'],
    ]);

    echo "Created admin user: {$admin['username']}\n";
    echo "Change the default password after first login.\n";
    echo "Add manager and receptionist accounts from Users in the app.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

<?php

declare(strict_types=1);

final class User
{
    /**
     * @return array{id: int, username: string, name: string, role: string}|null
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, username, password_hash, name, role
             FROM users
             WHERE username = :username AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'password_hash' => (string) $row['password_hash'],
            'name' => (string) $row['name'],
            'role' => (string) $row['role'],
        ];
    }

    /**
     * @return array{ok: true, user: array{id: int, name: string, role: string}}|array{ok: false, error: string}
     */
    public static function attemptLogin(string $username, string $password): array
    {
        $username = strtolower(input_string($username, 50));
        if ($username === '') {
            return ['ok' => false, 'error' => 'invalid'];
        }

        $user = self::findByUsername($username);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'invalid'];
        }

        return [
            'ok' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ],
        ];
    }
}

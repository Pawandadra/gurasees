<?php

declare(strict_types=1);

final class User
{    
    private const ROLES = ['admin', 'manager', 'receptionist'];

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

    public static function listAll(): array
    {
        $stmt = db()->query(
            'SELECT id, username, name, role, is_active, created_at
             FROM users
             ORDER BY id ASC'
        );

        $users = [];

        foreach ($stmt->fetchAll() as $row) {
            $users[] = [
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
                'name' => (string) $row['name'],
                'role' => (string) $row['role'],
                'is_active' => (int) $row['is_active'],
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $users;
    }

    public static function create(array $data): array
    {
        $username = strtolower(input_string($data['username'] ?? '', 50));
        $name = input_string($data['name'] ?? '', 120);
        $role = strtolower(input_string($data['role'] ?? '', 30));

        $password = is_string($data['password'] ?? null)
            ? (string) $data['password']
            : '';

        $confirmPassword = is_string($data['password_confirm'] ?? null)
            ? (string) $data['password_confirm']
            : '';

        $errors = self::validateUserFields($username, $name, $role);
        $errors = array_merge($errors, self::validatePassword($password, $confirmPassword, true));

        if ($username !== '' && self::usernameExists($username)) {
            $errors['username'] = __('users.error.username_exists');
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $stmt = db()->prepare(
            'INSERT INTO users (username, password_hash, name, role, is_active)
             VALUES (:username, :password_hash, :name, :role, 1)'
        );

        try {
            $stmt->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name' => $name,
                'role' => $role,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'ok' => false,
                    'errors' => [
                        'username' => __('users.error.username_exists'),
                    ],
                ];
            }

            throw $e;
        }

        return ['ok' => true];
    }

    public static function update(int $id, array $data): array
    {
        if ($id <= 0 || !self::exists($id)) {
            return [
                'ok' => false,
                'errors' => [
                    'user' => __('users.error.user_not_found'),
                ],
            ];
        }

        $username = strtolower(input_string($data['username'] ?? '', 50));
        $name = input_string($data['name'] ?? '', 120);
        $role = strtolower(input_string($data['role'] ?? '', 30));
        $isActive = (string) ($data['is_active'] ?? '1') === '1' ? 1 : 0;

        $password = is_string($data['password'] ?? null)
            ? (string) $data['password']
            : '';

        $confirmPassword = is_string($data['password_confirm'] ?? null)
            ? (string) $data['password_confirm']
            : '';

        $errors = self::validateUserFields($username, $name, $role);
        $errors = array_merge($errors, self::validatePassword($password, $confirmPassword, false));

        if ($username !== '' && self::usernameExists($username, $id)) {
            $errors['username'] = __('users.error.username_exists');
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if ($password !== '') {
            $stmt = db()->prepare(
                'UPDATE users
                 SET username = :username,
                     name = :name,
                     role = :role,
                     is_active = :is_active,
                     password_hash = :password_hash
                 WHERE id = :id
                 LIMIT 1'
            );

            $stmt->execute([
                'username' => $username,
                'name' => $name,
                'role' => $role,
                'is_active' => $isActive,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $id,
            ]);
        } else {
            $stmt = db()->prepare(
                'UPDATE users
                 SET username = :username,
                     name = :name,
                     role = :role,
                     is_active = :is_active
                 WHERE id = :id
                 LIMIT 1'
            );

            $stmt->execute([
                'username' => $username,
                'name' => $name,
                'role' => $role,
                'is_active' => $isActive,
                'id' => $id,
            ]);
        }

        return ['ok' => true];
    }

    public static function deleteById(int $id): array
    {
        if ($id <= 0) {
            return [
                'ok' => false,
                'error' => __('users.error.invalid_user'),
            ];
        }

        try {
            $stmt = db()->prepare(
                'DELETE FROM users
                 WHERE id = :id
                 LIMIT 1'
            );

            $stmt->execute([
                'id' => $id,
            ]);

            if ($stmt->rowCount() > 0) {
                return ['ok' => true];
            }

            return [
                'ok' => false,
                'error' => __('users.error.no_change'),
            ];
        } catch (PDOException) {
            return [
                'ok' => false,
                'error' => __('users.error.delete_failed'),
            ];
        }
    }

    private static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = db()->prepare(
                'SELECT COUNT(*)
                 FROM users
                 WHERE username = :username
                 AND id != :id'
            );

            $stmt->execute([
                'username' => $username,
                'id' => $exceptId,
            ]);

            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE username = :username'
        );

        $stmt->execute(['username' => $username]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function exists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function validateUserFields(string $username, string $name, string $role): array
    {
        $errors = [];

        if (!preg_match('/^[a-z0-9_]{3,50}$/', $username)) {
            $errors['username'] = __('users.error.username');
        }

        if (mb_strlen($name) < 2) {
            $errors['name'] = __('users.error.name');
        }

        if (!in_array($role, self::ROLES, true)) {
            $errors['role'] = __('users.error.role');
        }

        return $errors;
    }

    private static function validatePassword(string $password, string $confirmPassword, bool $required): array
    {
        $errors = [];

        if ($required || $password !== '' || $confirmPassword !== '') {
            if (strlen($password) < 8) {
                $errors['password'] = __('users.error.password');
            }

            if ($password !== $confirmPassword) {
                $errors['password_confirm'] = __('users.error.password_confirm');
            }
        }

        return $errors;
    }

    




    public static function findById(int $id, bool $withPassword = false): ?array
    {
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, username, password_hash, name, role, is_active, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $user = [
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'name' => (string) $row['name'],
        'role' => (string) $row['role'],
        'is_active' => (int) $row['is_active'],
        'created_at' => (string) $row['created_at'],
    ];

    if ($withPassword) {
        $user['password_hash'] = (string) $row['password_hash'];
    }

    return $user;
    }

    public static function updateOwnProfile(int $id, array $data, bool $canChangeRole): array
    {
        $current = self::findById($id);

        if ($current === null) {
            return [
                'ok' => false,
                'errors' => [
                'user' => __('users.error.user_not_found'),
                ],
            ];
        }

    // Username and role will NOT be changed from profile page.
        $username = (string) $current['username'];
        $role = (string) $current['role'];

    // Only full name can be changed.
        $name = input_string($data['name'] ?? '', 120);

        $errors = [];

        if (mb_strlen($name) < 2) {
        $errors['name'] = __('users.error.name');
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $stmt = db()->prepare(
            'UPDATE users
             SET name = :name
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);

        return [
            'ok' => true,
            'user' => [
                'id' => $id,
                'username' => $username,
                'name' => $name,
                'role' => $role,
            ],
        ];
    }

    public static function changeOwnPassword(    
    int $id,
    string $oldPassword,
    string $newPassword,
    string $confirmPassword
    ): array {
        $user = self::findById($id, true);

        if ($user === null) {
            return [
                'ok' => false,
                'errors' => [
                    'user' => __('users.error.user_not_found'),
                ],
            ];
        }

        $errors = [];

        if ($oldPassword === '') {
            $errors['old_password'] = __('profile.error.old_password_required');
        }

        if (strlen($newPassword) < 8) {
            $errors['new_password'] = __('users.error.password');
        }

        if ($newPassword !== $confirmPassword) {
            $errors['new_password_confirm'] = __('users.error.password_confirm');
        }

        if ($errors === [] && !password_verify($oldPassword, (string) $user['password_hash'])) {
            $errors['old_password'] = __('profile.error.old_password_wrong');
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $stmt = db()->prepare(
            'UPDATE users
             SET password_hash = :password_hash
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $id,
        ]);

        return ['ok' => true];
    }
}
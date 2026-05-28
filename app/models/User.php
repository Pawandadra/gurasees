<?php

declare(strict_types=1);

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_RECEPTIONIST = 'receptionist';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_RECEPTIONIST,
    ];

    private const USERNAME_PATTERN = '/^[a-z0-9_]{3,50}$/';
    private const MIN_PASSWORD_LENGTH = 8;

    /**
     * @return array{id: int, username: string, name: string, role: string}|null
     */
    public static function findByUsername(string $username): ?array
    {
        $row = self::fetchByUsername($username, true);

        return $row !== null ? self::mapLoginRow($row) : null;
    }

    /**
     * @return array{ok: true, user: array{id: int, name: string, role: string}}|array{ok: false, error: string}
     */
    public static function attemptLogin(string $username, string $password): array
    {
        $username = self::normalizeUsername($username);
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

    /**
     * @return list<array{id: int, username: string, name: string, role: string, is_active: bool}>
     */
    public static function listForManage(): array
    {
        $stmt = db()->query(
            'SELECT id, username, name, role, is_active
             FROM users
             ORDER BY is_active DESC, role ASC, name ASC, id ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
                'name' => (string) $row['name'],
                'role' => (string) $row['role'],
                'is_active' => (int) $row['is_active'] === 1,
            ];
        }

        return $rows;
    }

    /**
     * @return array{id: int, username: string, name: string, role: string, is_active: bool}|null
     */
    public static function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, username, name, role, is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'name' => (string) $row['name'],
            'role' => (string) $row['role'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }

    public static function isActive(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $stmt = db()->prepare('SELECT is_active FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false && (int) $row['is_active'] === 1;
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function create(array $raw): array
    {
        $username = self::normalizeUsername((string) ($raw['username'] ?? ''));
        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $role = self::normalizeRole((string) ($raw['role'] ?? ''));
        $password = (string) ($raw['password'] ?? '');
        $passwordConfirm = (string) ($raw['password_confirm'] ?? '');

        $errors = self::validateIdentity($username, $name, $role);
        $errors = array_merge($errors, self::validatePasswordPair($password, $passwordConfirm, true));
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $existing = self::fetchByUsername($username, false);
        if ($existing !== null) {
            if ((int) $existing['is_active'] === 1) {
                return ['ok' => false, 'errors' => ['username' => __('user.error.duplicate_username')]];
            }

            self::reactivateUser(
                (int) $existing['id'],
                $name,
                $role,
                password_hash($password, PASSWORD_DEFAULT)
            );

            return ['ok' => true];
        }

        $stmt = db()->prepare(
            'INSERT INTO users (username, password_hash, name, role, is_active)
             VALUES (:username, :password_hash, :name, :role, 1)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'role' => $role,
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function update(int $id, array $raw, int $actingUserId): array
    {
        $user = self::findById($id);
        if ($user === null) {
            return ['ok' => false, 'errors' => ['_form' => __('user.error.not_found')]];
        }

        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $role = self::normalizeRole((string) ($raw['role'] ?? ''));
        $password = (string) ($raw['password'] ?? '');
        $passwordConfirm = (string) ($raw['password_confirm'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('user.error.name');
        }
        if (!in_array($role, self::ROLES, true)) {
            $errors['role'] = __('user.error.role');
        }
        if ($password !== '' || $passwordConfirm !== '') {
            $errors = array_merge($errors, self::validatePasswordPair($password, $passwordConfirm, true));
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if ($id === $actingUserId && $role !== $user['role']) {
            return ['ok' => false, 'errors' => ['role' => __('user.error.cannot_change_own_role')]];
        }

        if (
            $user['role'] === self::ROLE_ADMIN
            && $role !== self::ROLE_ADMIN
            && self::countActiveAdmins() <= 1
            && $user['is_active']
        ) {
            return ['ok' => false, 'errors' => ['role' => __('user.error.last_admin')]];
        }

        $wantActive = self::parseIsActive($raw);
        $statusError = self::validateDeactivate($id, $wantActive, $user, $role, $actingUserId);
        if ($statusError !== null) {
            return ['ok' => false, 'errors' => ['is_active' => $statusError]];
        }

        $params = [
            'name' => $name,
            'role' => $role,
            'is_active' => $wantActive ? 1 : 0,
            'id' => $id,
        ];
        $passwordSql = '';
        if ($password !== '') {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = db()->prepare(
            "UPDATE users SET name = :name, role = :role, is_active = :is_active{$passwordSql} WHERE id = :id"
        );
        $stmt->execute($params);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true, name: string, changed: bool}|array{ok: false, errors: array<string, string>}
     */
    public static function updateProfile(int $id, array $raw): array
    {
        $user = self::fetchByIdWithPassword($id);
        if ($user === null || !(bool) $user['is_active']) {
            return ['ok' => false, 'errors' => ['_form' => __('user.error.not_found')]];
        }

        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $currentPassword = (string) ($raw['current_password'] ?? '');
        $newPassword = (string) ($raw['new_password'] ?? '');
        $confirm = (string) ($raw['password_confirm'] ?? '');
        $passwordChangeRequested = $newPassword !== '' || $confirm !== '' || $currentPassword !== '';

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('validation.required');
        }

        if ($passwordChangeRequested) {
            if ($currentPassword === '' || !password_verify($currentPassword, (string) $user['password_hash'])) {
                $errors['current_password'] = __('profile.error.current_password');
            }
            if ($newPassword === '' && $confirm === '') {
                $errors['new_password'] = __('validation.required');
            } else {
                if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
                    $errors['new_password'] = __('user.error.password_length');
                }
                if ($newPassword !== $confirm) {
                    $errors['password_confirm'] = __('user.error.password_mismatch');
                }
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $currentName = (string) ($user['name'] ?? '');
        $changed = $name !== $currentName || $passwordChangeRequested;
        if (!$changed) {
            return ['ok' => true, 'name' => $currentName, 'changed' => false];
        }

        $params = ['id' => $id, 'name' => $name];
        $passwordSql = '';
        if ($newPassword !== '') {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $stmt = db()->prepare("UPDATE users SET name = :name{$passwordSql} WHERE id = :id");
        $stmt->execute($params);

        return ['ok' => true, 'name' => $name, 'changed' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function setActive(int $id, bool $active, int $actingUserId): array
    {
        $user = self::findById($id);
        if ($user === null) {
            return ['ok' => false, 'errors' => ['_form' => __('user.error.not_found')]];
        }

        if ($user['is_active'] === $active) {
            return ['ok' => true];
        }

        $statusError = self::validateDeactivate($id, $active, $user, (string) $user['role'], $actingUserId);
        if ($statusError !== null) {
            return ['ok' => false, 'errors' => ['_form' => $statusError]];
        }

        $stmt = db()->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'errors' => ['_form' => __('user.error.not_found')]];
        }

        return ['ok' => true];
    }

    public static function countActiveAdmins(): int
    {
        $stmt = db()->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1"
        );

        return (int) $stmt->fetchColumn();
    }

    public static function roleLabel(string $role): string
    {
        return auth_role_label($role);
    }

    /**
     * @return array<string, string>
     */
    private static function validateIdentity(string $username, string $name, string $role): array
    {
        $errors = [];
        if ($username === '' || !preg_match(self::USERNAME_PATTERN, $username)) {
            $errors['username'] = __('user.error.username');
        }
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('user.error.name');
        }
        if (!in_array($role, self::ROLES, true)) {
            $errors['role'] = __('user.error.role');
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private static function validatePasswordPair(
        string $password,
        string $passwordConfirm,
        bool $required
    ): array {
        $errors = [];
        if ($password === '' && $passwordConfirm === '') {
            return $required ? ['password' => __('user.error.password_required')] : [];
        }
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = __('user.error.password_length');
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = __('user.error.password_mismatch');
        }

        return $errors;
    }

    private static function normalizeUsername(string $username): string
    {
        return strtolower(input_string($username, 50));
    }

    private static function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        return mb_substr($name, 0, 120);
    }

    private static function normalizeRole(string $role): string
    {
        $role = strtolower(input_string($role, 20));

        return in_array($role, self::ROLES, true) ? $role : '';
    }

    private static function parseIsActive(array $raw): bool
    {
        return filter_var($raw['is_active'] ?? '', FILTER_VALIDATE_INT) === 1;
    }

    /**
     * @param array{role: string, is_active: bool} $user
     */
    private static function validateDeactivate(
        int $id,
        bool $wantActive,
        array $user,
        string $roleAfter,
        int $actingUserId
    ): ?string {
        if ($wantActive || !$user['is_active']) {
            return null;
        }

        if ($id === $actingUserId) {
            return __('user.error.cannot_deactivate_self');
        }

        if ($roleAfter === self::ROLE_ADMIN && self::countActiveAdmins() <= 1) {
            return __('user.error.last_admin');
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchByUsername(string $username, bool $activeOnly): ?array
    {
        if ($username === '') {
            return null;
        }

        $sql = 'SELECT id, username, password_hash, name, role, is_active
                FROM users
                WHERE username = :username';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchByIdWithPassword(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, password_hash, name, is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, username: string, password_hash: string, name: string, role: string}
     */
    private static function mapLoginRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'password_hash' => (string) $row['password_hash'],
            'name' => (string) $row['name'],
            'role' => (string) $row['role'],
        ];
    }

    private static function reactivateUser(
        int $id,
        string $name,
        string $role,
        string $passwordHash
    ): void {
        $stmt = db()->prepare(
            'UPDATE users
             SET name = :name, role = :role, password_hash = :password_hash, is_active = 1
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'role' => $role,
            'password_hash' => $passwordHash,
            'id' => $id,
        ]);
    }
}

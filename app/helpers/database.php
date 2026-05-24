<?php

declare(strict_types=1);

/**
 * PDO database connection (singleton). Uses prepared statements only.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require APP_PATH . '/config/database.php';

    if ($cfg['name'] === '') {
        throw new RuntimeException(
            (bool) config('debug')
                ? 'Database not configured. Copy .env.example to .env'
                : 'Database not configured.'
        );
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    return $pdo;
}

/**
 * Escape user input for SQL LIKE patterns (% and _ are wildcards).
 */
function db_escape_like(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function db_like_contains(string $value): string
{
    return '%' . db_escape_like($value) . '%';
}

/**
 * @param array<string, mixed> $bind
 * @param list<string> $intKeys
 */
function db_bind_named(PDOStatement $stmt, array $bind, array $intKeys = []): void
{
    foreach ($bind as $key => $value) {
        $type = in_array($key, $intKeys, true) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue(':' . $key, $value, $type);
    }
}

/**
 * Whitelist-only ORDER BY fragment (column + direction).
 *
 * @param array<string, string> $whitelist sort key => SQL expression
 */
function db_order_sql(array $whitelist, string $sortKey, string $dir, string $defaultKey): string
{
    $column = $whitelist[$sortKey] ?? $whitelist[$defaultKey];
    $direction = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

    return $column . ' ' . $direction;
}

/**
 * Positional placeholders for IN (...).
 */
function db_sql_in_placeholders(int $count): string
{
    if ($count < 1) {
        throw new InvalidArgumentException('IN list requires at least one value.');
    }

    return implode(',', array_fill(0, $count, '?'));
}

/**
 * Patient text search (code, name, phone) for prepared statements.
 *
 * @return array{sql: string, bind: array<string, mixed>, int_keys: list<string>}
 */
function db_patient_search_clause(string $tableAlias, string $query): array
{
    $prefix = $tableAlias !== '' ? rtrim($tableAlias, '.') . '.' : '';
    $phoneDigits = preg_replace('/\D+/', '', $query) ?? '';

    return [
        'sql' => '(
                ' . $prefix . 'patient_code LIKE :code
                OR ' . $prefix . 'name LIKE :name
                OR ' . $prefix . 'phone LIKE :phone
                OR (:has_phone_digits = 1 AND REPLACE(REPLACE(REPLACE(' . $prefix . 'phone, \' \', \'\'), \'-\', \'\'), \'+\', \'\') LIKE :phone_digits)
            )',
        'bind' => [
            'code' => db_like_contains(strtoupper(str_replace(' ', '', $query))),
            'name' => db_like_contains($query),
            'phone' => db_like_contains($query),
            'has_phone_digits' => $phoneDigits !== '' ? 1 : 0,
            'phone_digits' => $phoneDigits !== '' ? db_like_contains($phoneDigits) : '0',
        ],
        'int_keys' => ['has_phone_digits'],
    ];
}

/**
 * Strip window-function total column from list rows.
 *
 * @param list<array<string, mixed>> $rows
 * @return array{rows: list<array<string, mixed>>, total: int}
 */
function db_strip_list_total(array $rows, string $totalColumn = '_list_total'): array
{
    if ($rows === []) {
        return ['rows' => [], 'total' => 0];
    }

    $total = (int) ($rows[0][$totalColumn] ?? 0);
    foreach ($rows as &$row) {
        unset($row[$totalColumn]);
    }
    unset($row);

    return ['rows' => $rows, 'total' => $total];
}

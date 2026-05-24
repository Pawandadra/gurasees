<?php

declare(strict_types=1);

final class Courier
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELED = 'canceled';

    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'date' => 'v.visited_at',
        'patient_id' => 'p.patient_code',
        'patient' => 'p.name',
        'phone' => 'p.phone',
        'status' => 'v.courier_status',
    ];

    /**
     * @return array{sort: string, dir: string}
     */
    public static function normalizeSort(string $sort, string $dir): array
    {
        $sort = strtolower($sort);
        if (!isset(self::SORT_COLUMNS[$sort])) {
            $sort = 'date';
        }

        $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';

        return ['sort' => $sort, 'dir' => $dir];
    }

    /**
     * @param array{q?: string, status?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function listFiltered(array $filters = [], string $sort = 'date', string $dir = 'desc'): array
    {
        $sortParams = self::normalizeSort($sort, $dir);
        $where = self::buildListWhere($filters);
        $orderSql = self::buildOrderSql($sortParams['sort'], $sortParams['dir']);

        $sql = 'SELECT v.id AS visit_id, v.visited_at, v.courier_dispatched_at, v.courier_status,
                       p.patient_code, p.name AS patient_name, p.phone,
                       p.address, p.delivery_address
                FROM visits v
                INNER JOIN patients p ON p.id = v.patient_id
                WHERE ' . $where['sql'] . "
                ORDER BY {$orderSql}, v.visited_at ASC, v.id ASC";

        $stmt = db()->prepare($sql);
        db_bind_named($stmt, $where['bind'], ['has_phone_digits']);
        $stmt->execute();

        return self::hydratePackageRows($stmt->fetchAll());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPackages(): array
    {
        return self::listFiltered([], 'date', 'desc');
    }

    /**
     * @param array{q?: string, status?: string} $filters
     * @return array{sql: string, bind: array<string, mixed>}
     */
    private static function buildListWhere(array $filters): array
    {
        $parts = [
            'p.patient_code IS NOT NULL',
            'v.courier_status IS NOT NULL',
            'EXISTS (
                SELECT 1 FROM visit_medicines vm
                WHERE vm.visit_id = v.id AND vm.courier_quantity > 0
            )',
        ];
        $bind = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $search = db_patient_search_clause('p', $q);
            $parts[] = '(' . $search['sql'] . ' OR p.address LIKE :address OR p.delivery_address LIKE :address)';
            $bind = array_merge($bind, $search['bind']);
            $bind['address'] = db_like_contains($q);
        }

        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        if (in_array($status, [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_CANCELED], true)) {
            $parts[] = 'v.courier_status = :status';
            $bind['status'] = $status;
        }

        return ['sql' => implode(' AND ', $parts), 'bind' => $bind];
    }

    private static function buildOrderSql(string $sort, string $dir): string
    {
        if ($sort === 'status') {
            $direction = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

            return 'FIELD(v.courier_status, '
                . "'" . self::STATUS_PENDING . "', "
                . "'" . self::STATUS_SENT . "', "
                . "'" . self::STATUS_CANCELED . "'"
                . ") {$direction}";
        }

        return db_order_sql(self::SORT_COLUMNS, $sort, $dir, 'date');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPackage(int $visitId): ?array
    {
        if ($visitId < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT v.id AS visit_id, v.visited_at, v.notes, v.courier_dispatched_at, v.courier_status,
                    p.patient_code, p.name AS patient_name, p.phone,
                    p.address, p.delivery_address
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE v.id = :id
               AND p.patient_code IS NOT NULL
               AND v.courier_status IS NOT NULL
               AND EXISTS (
                   SELECT 1 FROM visit_medicines vm
                   WHERE vm.visit_id = v.id AND vm.courier_quantity > 0
               )'
        );
        $stmt->execute(['id' => $visitId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $rows = self::hydratePackageRows([$row]);

        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPendingPackage(int $visitId): ?array
    {
        $package = self::findPackage($visitId);
        if ($package === null || ($package['courier_status'] ?? '') !== self::STATUS_PENDING) {
            return null;
        }

        return $package;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPrintablePackage(int $visitId): ?array
    {
        $package = self::findPackage($visitId);
        if ($package === null) {
            return null;
        }

        $status = (string) ($package['courier_status'] ?? '');
        if ($status === self::STATUS_CANCELED) {
            return null;
        }

        return $package;
    }

    public static function dispatch(int $visitId, int $userId): bool
    {
        if ($visitId < 1 || $userId < 1) {
            return false;
        }

        $stmt = db()->prepare(
            'UPDATE visits
             SET courier_status = :sent,
                 courier_dispatched_at = NOW(),
                 courier_dispatched_by = :uid
             WHERE id = :id
               AND courier_status = :pending
               AND EXISTS (
                   SELECT 1 FROM visit_medicines vm
                   WHERE vm.visit_id = visits.id AND vm.courier_quantity > 0
               )'
        );
        $stmt->execute([
            'id' => $visitId,
            'uid' => $userId,
            'sent' => self::STATUS_SENT,
            'pending' => self::STATUS_PENDING,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function cancel(int $visitId): bool
    {
        if ($visitId < 1) {
            return false;
        }

        $stmt = db()->prepare(
            'UPDATE visits
             SET courier_status = :canceled,
                 courier_dispatched_at = NULL,
                 courier_dispatched_by = NULL
             WHERE id = :id
               AND courier_status = :pending
               AND EXISTS (
                   SELECT 1 FROM visit_medicines vm
                   WHERE vm.visit_id = visits.id AND vm.courier_quantity > 0
               )'
        );
        $stmt->execute([
            'id' => $visitId,
            'canceled' => self::STATUS_CANCELED,
            'pending' => self::STATUS_PENDING,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SENT => __('courier.status.sent'),
            self::STATUS_CANCELED => __('courier.status.canceled'),
            default => __('courier.status.pending'),
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function resolveStatus(array $row): string
    {
        $status = (string) ($row['courier_status'] ?? '');
        if (in_array($status, [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_CANCELED], true)) {
            return $status;
        }

        if (!empty($row['courier_dispatched_at'])) {
            return self::STATUS_SENT;
        }

        return self::STATUS_PENDING;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function hydratePackageRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $visitIds = array_map(static fn (array $row): int => (int) $row['visit_id'], $rows);
        $linesByVisit = self::courierLinesForVisits($visitIds);

        foreach ($rows as &$row) {
            $id = (int) $row['visit_id'];
            $row['courier_lines'] = $linesByVisit[$id] ?? [];
            $row['delivery_display'] = self::formatDeliveryAddress($row);
            $row['courier_status'] = self::resolveStatus($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param list<int> $visitIds
     * @return array<int, list<array{name: string, quantity: int}>>
     */
    private static function courierLinesForVisits(array $visitIds): array
    {
        $visitIds = array_values(array_unique(array_filter(
            array_map('intval', $visitIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($visitIds === []) {
            return [];
        }

        $map = [];
        $sql = 'SELECT vm.visit_id, m.name, vm.courier_quantity
                FROM visit_medicines vm
                INNER JOIN medicines m ON m.id = vm.medicine_id
                WHERE vm.visit_id IN (%s) AND vm.courier_quantity > 0
                ORDER BY vm.visit_id ASC, m.name ASC';

        foreach (array_chunk($visitIds, 250) as $chunk) {
            $stmt = db()->prepare(sprintf($sql, db_sql_in_placeholders(count($chunk))));
            $stmt->execute($chunk);

            foreach ($stmt->fetchAll() as $row) {
                $vid = (int) $row['visit_id'];
                $map[$vid] ??= [];
                $map[$vid][] = [
                    'name' => (string) $row['name'],
                    'quantity' => (int) $row['courier_quantity'],
                ];
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function formatDeliveryAddress(array $row): string
    {
        $delivery = trim((string) ($row['delivery_address'] ?? ''));
        if ($delivery !== '') {
            return $delivery;
        }

        return trim((string) ($row['address'] ?? ''));
    }

    /**
     * @param list<array{name: string, quantity: int}> $lines
     */
    public static function formatMedicineSummary(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        $parts = [];
        foreach ($lines as $line) {
            $parts[] = $line['name'] . ' ×' . (int) $line['quantity'];
        }

        return implode(', ', $parts);
    }
}

<?php

declare(strict_types=1);

final class Medicine
{
    public const KIND_UNIT = 'unit';
    public const KIND_BULK = 'bulk';

    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'name' => 'name',
        'kind' => 'kind',
        'stock' => 'stock_quantity',
    ];

    /**
     * @return array{sort: string, dir: string}
     */
    public static function normalizeSort(string $sort, string $dir): array
    {
        $sort = strtolower($sort);
        if (!isset(self::SORT_COLUMNS[$sort])) {
            $sort = 'name';
        }

        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        return ['sort' => $sort, 'dir' => $dir];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function listForFilter(): array
    {
        $stmt = db()->query(
            'SELECT id, name
             FROM medicines
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $rows;
    }

    /**
     * For reception — unit stock and bulk liquids (qty = units; ml deducted from bulk).
     *
     * @return list<array{id: int, name: string}>
     */
    public static function listForReception(): array
    {
        $stmt = db()->query(
            'SELECT id, name, kind, portion_size_ml
             FROM medicines
             WHERE is_active = 1
               AND (
                    (kind = \'unit\' AND stock_quantity > 0)
                    OR (
                        kind = \'bulk\'
                        AND portion_size_ml IS NOT NULL
                        AND portion_size_ml > 0
                        AND stock_quantity >= portion_size_ml
                    )
               )
             ORDER BY sort_order ASC, name ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => self::receptionDisplayName(
                    (string) $row['name'],
                    (string) $row['kind'],
                    $row['portion_size_ml'] !== null ? (int) $row['portion_size_ml'] : null
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param array{q?: string, kind?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function listForManage(array $filters = [], string $sort = 'name', string $dir = 'asc'): array
    {
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'name');

        $where = ['is_active = 1'];
        $bind = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'name LIKE :q';
            $bind['q'] = '%' . $q . '%';
        }

        $kind = strtolower(trim((string) ($filters['kind'] ?? '')));
        if ($kind === self::KIND_UNIT || $kind === self::KIND_BULK) {
            $where[] = 'kind = :kind';
            $bind['kind'] = $kind;
        }

        $sql = 'SELECT id, name
                FROM medicines
                WHERE ' . implode(' AND ', $where) . "
                ORDER BY {$orderSql}, id ASC";

        $stmt = db()->prepare($sql);
        $stmt->execute($bind);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function create(array $raw): array
    {
        return self::createUnit($raw);
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    private static function createUnit(array $raw): array
    {
        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $stockRaw = $raw['stock_quantity'] ?? '';
        if ($stockRaw === '' || $stockRaw === null) {
            $stock = 0;
        } else {
            $stock = filter_var($stockRaw, FILTER_VALIDATE_INT);
            $stock = $stock !== false ? max(0, (int) $stock) : -1;
        }

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('medicine.error.name');
        }
        if ($stock < 0) {
            $errors['stock_quantity'] = __('medicine.error.stock');
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $existing = self::findByName($name);
        if ($existing !== null) {
            if ((int) $existing['is_active'] === 1) {
                return ['ok' => false, 'errors' => ['name' => __('medicine.error.duplicate')]];
            }

            self::reactivate((int) $existing['id'], $stock);

            return ['ok' => true];
        }

        $stmt = db()->prepare(
            'INSERT INTO medicines (name, kind, unit_price, stock_quantity, sort_order)
             VALUES (:name, :kind, :unit_price, :stock_quantity, :sort_order)'
        );
        $stmt->execute([
            'name' => $name,
            'kind' => self::KIND_UNIT,
            'unit_price' => 0,
            'stock_quantity' => $stock,
            'sort_order' => self::nextSortOrder(),
        ]);

        return ['ok' => true];
    }

    public static function deactivate(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $stmt = db()->prepare('UPDATE medicines SET is_active = 0 WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function updateName(int $id, array $raw): array
    {
        $medicine = self::findManageById($id);
        if ($medicine === null) {
            return ['ok' => false, 'errors' => ['_form' => __('medicine.error.not_found')]];
        }

        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('medicine.error.name');
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if ($name === $medicine['name']) {
            return ['ok' => true];
        }

        $existing = self::findByName($name);
        if ($existing !== null && (int) $existing['id'] !== $id) {
            return ['ok' => false, 'errors' => ['name' => __('medicine.error.duplicate')]];
        }

        $stmt = db()->prepare(
            'UPDATE medicines SET name = :name WHERE id = :id AND is_active = 1'
        );
        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'errors' => ['_form' => __('medicine.error.not_found')]];
        }

        return ['ok' => true];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    public static function findManageById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, name
             FROM medicines
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
        ];
    }

    /**
     * @param list<int> $ids
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     kind: string,
     *     stock_quantity: int,
     *     portion_size_ml: int|null
     * }>
     */
    public static function findActiveByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
        if ($ids === []) {
            return [];
        }

        $map = [];
        $sql = 'SELECT id, name, kind, stock_quantity, portion_size_ml
                FROM medicines
                WHERE is_active = 1
                  AND id IN (%s)
                  AND (kind = \'unit\' OR kind = \'bulk\')';

        foreach (array_chunk($ids, 100) as $chunk) {
            $stmt = db()->prepare(sprintf($sql, db_sql_in_placeholders(count($chunk))));
            $stmt->execute($chunk);

            foreach ($stmt->fetchAll() as $row) {
                $id = (int) $row['id'];
                $map[$id] = [
                    'id' => $id,
                    'name' => (string) $row['name'],
                    'kind' => (string) $row['kind'],
                    'stock_quantity' => (int) $row['stock_quantity'],
                    'portion_size_ml' => $row['portion_size_ml'] !== null ? (int) $row['portion_size_ml'] : null,
                ];
            }
        }

        return $map;
    }

    private static function receptionDisplayName(string $name, string $kind, ?int $portionMl): string
    {
        if ($kind === self::KIND_BULK && $portionMl !== null && $portionMl > 0) {
            return $name . ' · ' . self::formatVolumeMl($portionMl) . '/unit';
        }

        return $name;
    }

    public static function formatVolumeMl(int $ml): string
    {
        if ($ml >= 1000 && $ml % 1000 === 0) {
            return number_format($ml / 1000) . ' L';
        }

        return number_format($ml) . ' ml';
    }

    /**
     * @param list<array{medicine_id: int, quantity: int}> $lines
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function validateVisitLines(array $lines): array
    {
        if ($lines === []) {
            return ['ok' => true];
        }

        $aggregated = [];
        foreach ($lines as $line) {
            $id = (int) ($line['medicine_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($id < 1 || $qty < 1) {
                continue;
            }
            $aggregated[$id] = ($aggregated[$id] ?? 0) + $qty;
        }

        if ($aggregated === []) {
            return ['ok' => false, 'errors' => ['medicines' => __('medicine.error.lines')]];
        }

        $catalog = self::findActiveByIds(array_keys($aggregated));
        foreach ($aggregated as $medicineId => $qty) {
            $medicine = $catalog[$medicineId] ?? null;
            if ($medicine === null) {
                return ['ok' => false, 'errors' => ['medicines' => __('medicine.error.unavailable')]];
            }

            if ((string) $medicine['kind'] === self::KIND_BULK) {
                $portionMl = (int) ($medicine['portion_size_ml'] ?? 0);
                if ($portionMl < 1) {
                    return ['ok' => false, 'errors' => ['medicines' => __('medicine.error.unavailable')]];
                }
                $neededMl = $qty * $portionMl;
                $availableMl = (int) $medicine['stock_quantity'];
                if ($neededMl > $availableMl) {
                    return [
                        'ok' => false,
                        'errors' => [
                            'medicines' => __('medicine.error.bulk_insufficient_ml', [
                                'name' => $medicine['name'],
                                'needed' => self::formatVolumeMl($neededMl),
                                'available' => self::formatVolumeMl($availableMl),
                            ]),
                        ],
                    ];
                }
                continue;
            }

            if ($qty > $medicine['stock_quantity']) {
                return [
                    'ok' => false,
                    'errors' => [
                        'medicines' => __('medicine.error.insufficient', ['name' => $medicine['name']]),
                    ],
                ];
            }
        }

        return ['ok' => true];
    }

    /**
     * @param list<array{medicine_id: int, quantity: int}> $lines
     */
    public static function restoreVisitStock(int $visitId): void
    {
        if ($visitId < 1) {
            return;
        }

        $stmt = db()->prepare(
            'SELECT vm.medicine_id, vm.quantity, m.kind, m.portion_size_ml
             FROM visit_medicines vm
             INNER JOIN medicines m ON m.id = vm.medicine_id
             WHERE vm.visit_id = :vid'
        );
        $stmt->execute(['vid' => $visitId]);

        $restoreUnit = db()->prepare(
            'UPDATE medicines SET stock_quantity = stock_quantity + :add WHERE id = :id'
        );

        foreach ($stmt->fetchAll() as $row) {
            $medicineId = (int) $row['medicine_id'];
            $qty = (int) $row['quantity'];
            if ($medicineId < 1 || $qty < 1) {
                continue;
            }

            $add = (string) $row['kind'] === self::KIND_BULK
                ? $qty * max(1, (int) ($row['portion_size_ml'] ?? 0))
                : $qty;

            $restoreUnit->execute([
                'add' => $add,
                'id' => $medicineId,
            ]);
        }
    }

    /**
     * @param list<array{medicine_id: int, quantity: int}> $lines
     */
    public static function attachToVisit(int $visitId, array $lines): void
    {
        if ($visitId < 1 || $lines === []) {
            return;
        }

        $aggregated = [];
        foreach ($lines as $line) {
            $id = (int) ($line['medicine_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($id < 1 || $qty < 1) {
                continue;
            }
            $courierQty = min($qty, max(0, (int) ($line['courier_quantity'] ?? 0)));
            if (!isset($aggregated[$id])) {
                $aggregated[$id] = ['quantity' => 0, 'courier_quantity' => 0];
            }
            $aggregated[$id]['quantity'] += $qty;
            $aggregated[$id]['courier_quantity'] += $courierQty;
        }

        foreach ($aggregated as $medicineId => $line) {
            $aggregated[$medicineId]['courier_quantity'] = min(
                (int) $line['quantity'],
                (int) $line['courier_quantity']
            );
        }

        $pdo = db();
        $catalog = self::findActiveByIds(array_keys($aggregated));

        $ins = $pdo->prepare(
            'INSERT INTO visit_medicines (visit_id, medicine_id, quantity, unit_price, line_total, courier_quantity)
             VALUES (:visit_id, :medicine_id, :quantity, 0, 0, :courier_quantity)'
        );
        $deductUnit = $pdo->prepare(
            'UPDATE medicines SET stock_quantity = stock_quantity - :deduct
             WHERE id = :id AND kind = :kind AND stock_quantity >= :min_stock'
        );
        $deductBulk = $pdo->prepare(
            'UPDATE medicines SET stock_quantity = stock_quantity - :deduct
             WHERE id = :id AND kind = :kind AND stock_quantity >= :min_stock'
        );

        foreach ($aggregated as $medicineId => $line) {
            $qty = (int) $line['quantity'];
            $medicine = $catalog[$medicineId] ?? null;
            if ($medicine === null) {
                throw new RuntimeException('Medicine unavailable');
            }

            $ins->execute([
                'visit_id' => $visitId,
                'medicine_id' => $medicineId,
                'quantity' => $qty,
                'courier_quantity' => (int) $line['courier_quantity'],
            ]);

            if ((string) $medicine['kind'] === self::KIND_BULK) {
                $portionMl = (int) ($medicine['portion_size_ml'] ?? 0);
                if ($portionMl < 1) {
                    throw new RuntimeException('Invalid bulk portion');
                }
                $deductMl = $qty * $portionMl;
                $deductBulk->execute([
                    'deduct' => $deductMl,
                    'id' => $medicineId,
                    'kind' => self::KIND_BULK,
                    'min_stock' => $deductMl,
                ]);
                if ($deductBulk->rowCount() === 0) {
                    throw new RuntimeException('Bulk stock deduct failed');
                }
                continue;
            }

            $deductUnit->execute([
                'deduct' => $qty,
                'id' => $medicineId,
                'kind' => self::KIND_UNIT,
                'min_stock' => $qty,
            ]);
            if ($deductUnit->rowCount() === 0) {
                throw new RuntimeException('Unit stock deduct failed');
            }
        }
    }

    public static function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    public static function formatPriceDisplay(float $price): string
    {
        return '₹' . number_format($price, 2);
    }

    private static function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        return mb_substr($name, 0, 120);
    }

    /**
     * @return array{id: int, is_active: int}|null
     */
    private static function findByName(string $name): ?array
    {
        $stmt = db()->prepare('SELECT id, is_active FROM medicines WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row !== false ? ['id' => (int) $row['id'], 'is_active' => (int) $row['is_active']] : null;
    }

    private static function reactivate(int $id, int $stock): void
    {
        $stmt = db()->prepare(
            'UPDATE medicines
             SET is_active = 1, kind = :kind, unit_price = 0,
                 stock_quantity = :stock_quantity, portion_size_ml = NULL,
                 sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'kind' => self::KIND_UNIT,
            'stock_quantity' => $stock,
            'sort_order' => self::nextSortOrder(),
        ]);
    }

    private static function nextSortOrder(): int
    {
        $max = db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM medicines')->fetchColumn();

        return ((int) $max) + 1;
    }
}

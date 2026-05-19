<?php

declare(strict_types=1);

final class Medicine
{
    public const KIND_UNIT = 'unit';
    public const KIND_BULK = 'bulk';

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function listForFilter(): array
    {
        $stmt = db()->prepare(
            'SELECT id, name
             FROM medicines
             WHERE is_active = 1 AND kind = :kind
             ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['kind' => self::KIND_UNIT]);

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
     * For reception — no stock quantity exposed.
     *
     * @return list<array{id: int, name: string, unit_price: string}>
     */
    public static function listForReception(): array
    {
        $stmt = db()->prepare(
            'SELECT id, name, unit_price
             FROM medicines
             WHERE is_active = 1 AND kind = :kind AND stock_quantity > 0
             ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['kind' => self::KIND_UNIT]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'unit_price' => self::formatPrice((float) $row['unit_price']),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForManage(): array
    {
        $stmt = db()->query(
            'SELECT m.id, m.name, m.kind, m.unit_price, m.stock_quantity,
                    m.bulk_source_id, m.portion_size_ml, b.name AS bulk_source_name
             FROM medicines m
             LEFT JOIN medicines b ON b.id = m.bulk_source_id
             WHERE m.is_active = 1
             ORDER BY m.kind DESC, m.sort_order ASC, m.name ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $kind = (string) $row['kind'];
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'kind' => $kind,
                'unit_price' => self::formatPrice((float) $row['unit_price']),
                'stock_quantity' => (int) $row['stock_quantity'],
                'bulk_source_id' => $row['bulk_source_id'] !== null ? (int) $row['bulk_source_id'] : null,
                'portion_size_ml' => $row['portion_size_ml'] !== null ? (int) $row['portion_size_ml'] : null,
                'bulk_source_name' => $row['bulk_source_name'] !== null ? (string) $row['bulk_source_name'] : null,
                'stock_display' => self::formatStockDisplay($kind, (int) $row['stock_quantity']),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id: int, name: string, stock_quantity: int}>
     */
    public static function listBulkForPortioning(): array
    {
        $stmt = db()->prepare(
            'SELECT id, name, stock_quantity
             FROM medicines
             WHERE is_active = 1 AND kind = :kind AND stock_quantity > 0
             ORDER BY name ASC'
        );
        $stmt->execute(['kind' => self::KIND_BULK]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'stock_quantity' => (int) $row['stock_quantity'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function create(array $raw): array
    {
        $type = strtolower(trim((string) ($raw['medicine_type'] ?? self::KIND_UNIT)));

        return $type === self::KIND_BULK ? self::createBulk($raw) : self::createUnit($raw);
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    private static function createUnit(array $raw): array
    {
        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $price = self::parsePrice((string) ($raw['unit_price'] ?? ''));
        $stock = filter_var($raw['stock_quantity'] ?? '', FILTER_VALIDATE_INT);
        $stock = $stock !== false ? max(0, (int) $stock) : -1;

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('medicine.error.name');
        }
        if ($price === null || $price <= 0) {
            $errors['unit_price'] = __('medicine.error.price');
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

            self::reactivate((int) $existing['id'], $price, $stock);

            return ['ok' => true];
        }

        $stmt = db()->prepare(
            'INSERT INTO medicines (name, kind, unit_price, stock_quantity, sort_order)
             VALUES (:name, :kind, :unit_price, :stock_quantity, :sort_order)'
        );
        $stmt->execute([
            'name' => $name,
            'kind' => self::KIND_UNIT,
            'unit_price' => $price,
            'stock_quantity' => $stock,
            'sort_order' => self::nextSortOrder(),
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    private static function createBulk(array $raw): array
    {
        $name = self::normalizeName((string) ($raw['name'] ?? ''));
        $containerMl = self::parseVolumeMl($raw, 'container_ml', 'container_volume_unit');
        $containerCount = filter_var($raw['container_count'] ?? '', FILTER_VALIDATE_INT);
        $unitSizeMl = self::parseVolumeMl($raw, 'unit_size_ml', 'unit_size_volume_unit');
        $containerCount = $containerCount !== false ? max(1, (int) $containerCount) : 0;
        $totalMl = $containerMl * $containerCount;

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = __('medicine.error.name');
        }
        if ($containerMl < 1) {
            $errors['container_ml'] = __('medicine.error.container_ml');
        }
        if ($containerCount < 1) {
            $errors['container_count'] = __('medicine.error.container_count');
        }
        if ($unitSizeMl < 1) {
            $errors['unit_size_ml'] = __('medicine.error.unit_size_ml');
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $existing = self::findByName($name);
        if ($existing !== null && (int) $existing['is_active'] === 1) {
            return ['ok' => false, 'errors' => ['name' => __('medicine.error.duplicate')]];
        }

        if ($existing !== null) {
            self::reactivateBulk((int) $existing['id'], $totalMl, $unitSizeMl);

            return ['ok' => true];
        }

        $stmt = db()->prepare(
            'INSERT INTO medicines (name, kind, unit_price, stock_quantity, portion_size_ml, sort_order)
             VALUES (:name, :kind, 0, :stock_quantity, :unit_size_ml, :sort_order)'
        );
        $stmt->execute([
            'name' => $name,
            'kind' => self::KIND_BULK,
            'stock_quantity' => $totalMl,
            'unit_size_ml' => $unitSizeMl,
            'sort_order' => self::nextSortOrder(),
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true, sellable_id: int}|array{ok: false, errors: array<string, string>}
     */
    public static function portionFromBulk(array $raw): array
    {
        $bulkId = filter_var($raw['bulk_id'] ?? '', FILTER_VALIDATE_INT);
        $portionMl = filter_var($raw['portion_ml'] ?? '', FILTER_VALIDATE_INT);
        $bottleCount = filter_var($raw['bottle_count'] ?? '', FILTER_VALIDATE_INT);
        $sellableName = self::normalizeName((string) ($raw['sellable_name'] ?? ''));
        $price = self::parsePrice((string) ($raw['unit_price'] ?? ''));

        $bulkId = $bulkId !== false ? (int) $bulkId : 0;
        $portionMl = $portionMl !== false ? max(1, (int) $portionMl) : 0;
        $bottleCount = $bottleCount !== false ? max(1, (int) $bottleCount) : 0;
        $mlNeeded = $portionMl * $bottleCount;

        $errors = [];
        if ($bulkId < 1) {
            $errors['bulk_id'] = __('medicine.error.bulk_required');
        }
        if ($portionMl < 1) {
            $errors['portion_ml'] = __('medicine.error.portion_ml');
        }
        if ($bottleCount < 1) {
            $errors['bottle_count'] = __('medicine.error.bottle_count');
        }
        if (mb_strlen($sellableName) < 2) {
            $errors['sellable_name'] = __('medicine.error.sellable_name');
        }
        if ($price === null || $price <= 0) {
            $errors['portion_unit_price'] = __('medicine.error.price');
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $bulk = self::findBulkById($bulkId);
        if ($bulk === null) {
            return ['ok' => false, 'errors' => ['bulk_id' => __('medicine.error.bulk_not_found')]];
        }
        if ($mlNeeded > $bulk['stock_quantity']) {
            return [
                'ok' => false,
                'errors' => [
                    'bottle_count' => __('medicine.error.bulk_insufficient', [
                        'available' => self::formatVolumeMl($bulk['stock_quantity']),
                        'needed' => self::formatVolumeMl($mlNeeded),
                    ]),
                ],
            ];
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            $sellable = self::findSellableByBulkAndPortion($bulkId, $portionMl);
            if ($sellable !== null) {
                $sellableId = (int) $sellable['id'];
                $stmt = $pdo->prepare(
                    'UPDATE medicines
                     SET stock_quantity = stock_quantity + :add, unit_price = :price
                     WHERE id = :id AND kind = :kind'
                );
                $stmt->execute([
                    'add' => $bottleCount,
                    'price' => $price,
                    'id' => $sellableId,
                    'kind' => self::KIND_UNIT,
                ]);
            } else {
                $existing = self::findByName($sellableName);
                if ($existing !== null && (int) $existing['is_active'] === 1) {
                    $pdo->rollBack();

                    return ['ok' => false, 'errors' => ['sellable_name' => __('medicine.error.duplicate')]];
                }

                if ($existing !== null) {
                    $sellableId = (int) $existing['id'];
                    $stmt = $pdo->prepare(
                        'UPDATE medicines
                         SET is_active = 1, kind = :kind, unit_price = :price,
                             stock_quantity = stock_quantity + :add,
                             bulk_source_id = :bulk_id, portion_size_ml = :portion_ml,
                             sort_order = :sort_order
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'kind' => self::KIND_UNIT,
                        'price' => $price,
                        'add' => $bottleCount,
                        'bulk_id' => $bulkId,
                        'portion_ml' => $portionMl,
                        'sort_order' => self::nextSortOrder(),
                        'id' => $sellableId,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO medicines (name, kind, unit_price, stock_quantity, bulk_source_id, portion_size_ml, sort_order)
                         VALUES (:name, :kind, :price, :stock, :bulk_id, :portion_ml, :sort_order)'
                    );
                    $stmt->execute([
                        'name' => $sellableName,
                        'kind' => self::KIND_UNIT,
                        'price' => $price,
                        'stock' => $bottleCount,
                        'bulk_id' => $bulkId,
                        'portion_ml' => $portionMl,
                        'sort_order' => self::nextSortOrder(),
                    ]);
                    $sellableId = (int) $pdo->lastInsertId();
                }
            }

            $deduct = $pdo->prepare(
                'UPDATE medicines
                 SET stock_quantity = stock_quantity - :deduct
                 WHERE id = :id AND kind = :kind AND stock_quantity >= :min_ml'
            );
            $deduct->execute([
                'deduct' => $mlNeeded,
                'id' => $bulkId,
                'kind' => self::KIND_BULK,
                'min_ml' => $mlNeeded,
            ]);
            if ($deduct->rowCount() === 0) {
                $pdo->rollBack();

                return ['ok' => false, 'errors' => ['bottle_count' => __('medicine.error.bulk_insufficient_short')]];
            }

            $userId = self::currentUserId();
            $log = $pdo->prepare(
                'INSERT INTO medicine_portion_logs
                    (bulk_medicine_id, sellable_medicine_id, portion_size_ml, bottles_created, ml_used, created_by)
                 VALUES (:bulk_id, :sellable_id, :portion_ml, :bottles, :ml_used, :uid)'
            );
            $log->execute([
                'bulk_id' => $bulkId,
                'sellable_id' => $sellableId,
                'portion_ml' => $portionMl,
                'bottles' => $bottleCount,
                'ml_used' => $mlNeeded,
                'uid' => $userId > 0 ? $userId : null,
            ]);

            $pdo->commit();

            return ['ok' => true, 'sellable_id' => $sellableId];
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'errors' => ['_form' => __('medicine.error.portion_failed')]];
        }
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
     * @return array{id: int, name: string, unit_price: float, stock_quantity: int}|null
     */
    public static function findActiveById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, name, unit_price, stock_quantity
             FROM medicines
             WHERE id = :id AND is_active = 1 AND kind = :kind
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kind' => self::KIND_UNIT]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'unit_price' => (float) $row['unit_price'],
            'stock_quantity' => (int) $row['stock_quantity'],
        ];
    }

    /**
     * @param list<int> $ids
     * @return array<int, array{id: int, name: string, unit_price: float, stock_quantity: int}>
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
        $sql = 'SELECT id, name, unit_price, stock_quantity
                FROM medicines
                WHERE is_active = 1 AND kind = \'unit\' AND id IN (%s)';

        foreach (array_chunk($ids, 100) as $chunk) {
            $stmt = db()->prepare(sprintf($sql, db_sql_in_placeholders(count($chunk))));
            $stmt->execute($chunk);

            foreach ($stmt->fetchAll() as $row) {
                $id = (int) $row['id'];
                $map[$id] = [
                    'id' => $id,
                    'name' => (string) $row['name'],
                    'unit_price' => (float) $row['unit_price'],
                    'stock_quantity' => (int) $row['stock_quantity'],
                ];
            }
        }

        return $map;
    }

    /** @return array{id: int, name: string, stock_quantity: int}|null */
    private static function findBulkById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, name, stock_quantity
             FROM medicines
             WHERE id = :id AND is_active = 1 AND kind = :kind
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kind' => self::KIND_BULK]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'stock_quantity' => (int) $row['stock_quantity'],
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private static function findSellableByBulkAndPortion(int $bulkId, int $portionMl): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, name
             FROM medicines
             WHERE is_active = 1 AND kind = :kind
               AND bulk_source_id = :bulk_id AND portion_size_ml = :portion_ml
             LIMIT 1'
        );
        $stmt->execute([
            'kind' => self::KIND_UNIT,
            'bulk_id' => $bulkId,
            'portion_ml' => $portionMl,
        ]);
        $row = $stmt->fetch();

        return $row !== false
            ? ['id' => (int) $row['id'], 'name' => (string) $row['name']]
            : null;
    }

    public static function kindLabel(string $kind): string
    {
        return $kind === self::KIND_BULK
            ? __('medicine.kind.bulk')
            : __('medicine.kind.unit');
    }

    public static function formatStockDisplay(string $kind, int $stock): string
    {
        if ($kind === self::KIND_BULK) {
            return self::formatVolumeMl($stock);
        }

        return (string) $stock;
    }

    public static function formatVolumeMl(int $ml): string
    {
        if ($ml >= 1000 && $ml % 1000 === 0) {
            return number_format($ml / 1000) . ' L';
        }

        return number_format($ml) . ' ml';
    }

    public static function formatPortionHint(int $portionMl): string
    {
        return __('medicine.portion.size_label', ['size' => self::formatVolumeMl($portionMl)]);
    }

    private static function reactivateBulk(int $id, int $totalMl, int $unitSizeMl): void
    {
        $stmt = db()->prepare(
            'UPDATE medicines
             SET is_active = 1, kind = :kind, unit_price = 0,
                 stock_quantity = stock_quantity + :add_ml, portion_size_ml = :unit_size_ml,
                 bulk_source_id = NULL, sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute([
            'kind' => self::KIND_BULK,
            'add_ml' => $totalMl,
            'unit_size_ml' => $unitSizeMl,
            'sort_order' => self::nextSortOrder(),
            'id' => $id,
        ]);
    }

    private static function currentUserId(): ?int
    {
        $user = auth_user();

        return isset($user['id']) ? (int) $user['id'] : null;
    }

    /**
     * @param list<array{medicine_id: int, quantity: int}> $lines
     * @return array{ok: true, total: float}|array{ok: false, errors: array<string, string>}
     */
    public static function validateVisitLines(array $lines): array
    {
        if ($lines === []) {
            return ['ok' => true, 'total' => 0.0];
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
        $total = 0.0;
        foreach ($aggregated as $medicineId => $qty) {
            $medicine = $catalog[$medicineId] ?? null;
            if ($medicine === null) {
                return ['ok' => false, 'errors' => ['medicines' => __('medicine.error.unavailable')]];
            }
            if ($qty > $medicine['stock_quantity']) {
                return [
                    'ok' => false,
                    'errors' => [
                        'medicines' => __('medicine.error.insufficient', ['name' => $medicine['name']]),
                    ],
                ];
            }
            $total += $qty * $medicine['unit_price'];
        }

        return ['ok' => true, 'total' => round($total, 2)];
    }

    /**
     * @param list<array{medicine_id: int, quantity: int}> $lines
     */
    public static function attachToVisit(int $visitId, array $lines): float
    {
        if ($visitId < 1 || $lines === []) {
            return 0.0;
        }

        $aggregated = [];
        foreach ($lines as $line) {
            $id = (int) ($line['medicine_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($id < 1 || $qty < 1) {
                continue;
            }
            $courierQty = min($qty, max(0, (int) ($line['courier_quantity'] ?? 0)));
            $aggregated[$id] = [
                'quantity' => $qty,
                'courier_quantity' => $courierQty,
            ];
        }

        $pdo = db();
        $total = 0.0;
        $catalog = self::findActiveByIds(array_keys($aggregated));

        $ins = $pdo->prepare(
            'INSERT INTO visit_medicines (visit_id, medicine_id, quantity, unit_price, line_total, courier_quantity)
             VALUES (:visit_id, :medicine_id, :quantity, :unit_price, :line_total, :courier_quantity)'
        );
        $deduct = $pdo->prepare(
            'UPDATE medicines SET stock_quantity = stock_quantity - :deduct
             WHERE id = :id AND kind = :kind AND stock_quantity >= :min_stock'
        );

        foreach ($aggregated as $medicineId => $line) {
            $qty = (int) $line['quantity'];
            $medicine = $catalog[$medicineId] ?? null;
            if ($medicine === null) {
                continue;
            }

            $unitPrice = (float) $medicine['unit_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $total += $lineTotal;

            $ins->execute([
                'visit_id' => $visitId,
                'medicine_id' => $medicineId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'courier_quantity' => (int) $line['courier_quantity'],
            ]);

            $deduct->execute([
                'deduct' => $qty,
                'id' => $medicineId,
                'kind' => self::KIND_UNIT,
                'min_stock' => $qty,
            ]);
        }

        return round($total, 2);
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

    private static function parseVolumeMl(array $raw, string $valueKey, string $unitKey): int
    {
        $value = trim((string) ($raw[$valueKey] ?? ''));
        if ($value === '' || !is_numeric($value)) {
            return 0;
        }

        $amount = (float) $value;
        $unit = strtolower(trim((string) ($raw[$unitKey] ?? 'ml')));
        if (in_array($unit, ['l', 'litre', 'liter', 'litres', 'liters'], true)) {
            return max(1, (int) round($amount * 1000));
        }

        return max(1, (int) round($amount));
    }

    private static function parsePrice(string $value): ?float
    {
        $value = trim(str_replace(',', '', $value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
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

    private static function reactivate(int $id, float $price, int $stock): void
    {
        $stmt = db()->prepare(
            'UPDATE medicines
             SET is_active = 1, kind = :kind, unit_price = :unit_price,
                 stock_quantity = :stock_quantity, bulk_source_id = NULL, portion_size_ml = NULL,
                 sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'kind' => self::KIND_UNIT,
            'unit_price' => $price,
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

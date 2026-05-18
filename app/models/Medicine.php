<?php

declare(strict_types=1);

final class Medicine
{
    /**
     * For reception — no stock quantity exposed.
     *
     * @return list<array{id: int, name: string, unit_price: string}>
     */
    public static function listForReception(): array
    {
        $stmt = db()->query(
            'SELECT id, name, unit_price
             FROM medicines
             WHERE is_active = 1 AND stock_quantity > 0
             ORDER BY sort_order ASC, name ASC'
        );

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
     * @return list<array{id: int, name: string, unit_price: string, stock_quantity: int}>
     */
    public static function listForManage(): array
    {
        $stmt = db()->query(
            'SELECT id, name, unit_price, stock_quantity
             FROM medicines
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'unit_price' => self::formatPrice((float) $row['unit_price']),
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
            'INSERT INTO medicines (name, unit_price, stock_quantity, sort_order)
             VALUES (:name, :unit_price, :stock_quantity, :sort_order)'
        );
        $stmt->execute([
            'name' => $name,
            'unit_price' => $price,
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
            'unit_price' => (float) $row['unit_price'],
            'stock_quantity' => (int) $row['stock_quantity'],
        ];
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

        $total = 0.0;
        foreach ($aggregated as $medicineId => $qty) {
            $medicine = self::findActiveById($medicineId);
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
            $aggregated[$id] = ($aggregated[$id] ?? 0) + $qty;
        }

        $pdo = db();
        $total = 0.0;

        $ins = $pdo->prepare(
            'INSERT INTO visit_medicines (visit_id, medicine_id, quantity, unit_price, line_total)
             VALUES (:visit_id, :medicine_id, :quantity, :unit_price, :line_total)'
        );
        $deduct = $pdo->prepare(
            'UPDATE medicines SET stock_quantity = stock_quantity - :deduct
             WHERE id = :id AND stock_quantity >= :min_stock'
        );

        foreach ($aggregated as $medicineId => $qty) {
            $medicine = self::findActiveById($medicineId);
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
            ]);

            $deduct->execute([
                'deduct' => $qty,
                'id' => $medicineId,
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
             SET is_active = 1, unit_price = :unit_price, stock_quantity = :stock_quantity, sort_order = :sort_order
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
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

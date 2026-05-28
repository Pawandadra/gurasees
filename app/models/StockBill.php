<?php

declare(strict_types=1);

final class StockBill
{
    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'bill_number' => 'sb.bill_number',
        'register_number' => 'sb.register_number',
        'supplier' => 'sb.supplier',
        'bill_date' => 'sb.bill_date',
        'delivery_date' => 'sb.delivery_date',
        'amount' => 'sb.amount',
        'submitted' => 'sb.created_at',
    ];

    /**
     * @param array<string, mixed> $raw
     * @param list<array{name: string, quantity: float|string, amount: float|string}> $lineItems
     * @return array{ok: true, id: int}|array{ok: false, errors: array<string, string>}
     */
    public static function create(array $raw, array $lineItems, int $submittedBy, ?array $file): array
    {
        if ($submittedBy < 1) {
            return ['ok' => false, 'errors' => ['_form' => __('error.server')]];
        }

        $data = self::sanitize($raw);
        $items = self::normalizeLineItems($lineItems);
        $errors = self::validate($data, $items, $file !== null);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $pdo = db();
        $pdo->beginTransaction();
        $billId = 0;

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO stock_bills (bill_number, register_number, supplier, bill_date, delivery_date, amount, submitted_by)
                 VALUES (:bill_number, :register_number, :supplier, :bill_date, :delivery_date, :amount, :submitted_by)'
            );
            $totalAmount = self::sumItemAmounts($items);
            $stmt->execute([
                'bill_number' => $data['bill_number'],
                'register_number' => $data['register_number'],
                'supplier' => $data['supplier'],
                'bill_date' => $data['bill_date'],
                'delivery_date' => $data['delivery_date'],
                'amount' => $totalAmount,
                'submitted_by' => $submittedBy,
            ]);
            $billId = (int) $pdo->lastInsertId();

            self::insertItems($billId, $items);

            if ($file !== null) {
                $upload = stock_bill_store_upload($file, $billId);
                if (!$upload['ok']) {
                    $pdo->rollBack();
                    stock_bill_delete_bill_files($billId);

                    return ['ok' => false, 'errors' => ['file' => $upload['error']]];
                }

                $upd = $pdo->prepare(
                    'UPDATE stock_bills
                     SET file_stored_name = :stored, file_original_name = :original,
                         file_mime = :mime, file_size = :size
                     WHERE id = :id'
                );
                $upd->execute([
                    'stored' => $upload['relative'],
                    'original' => $upload['original'],
                    'mime' => $upload['mime'],
                    'size' => $upload['size'],
                    'id' => $billId,
                ]);
            }

            $pdo->commit();

            return ['ok' => true, 'id' => $billId];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (isset($billId) && $billId > 0) {
                stock_bill_delete_bill_files($billId);
            }

            // If a duplicate slips through due to a race condition, map it to field errors.
            if ($e instanceof PDOException && ($e->getCode() === '23000' || $e->getCode() === 23000)) {
                $dupe = self::findDuplicateNumbers($data['bill_number'], $data['register_number']);
                $errors = [];
                if ($dupe['bill_number']) {
                    $errors['bill_number'] = __('stock.error.duplicate_bill_number');
                }
                if ($dupe['register_number']) {
                    $errors['register_number'] = __('stock.error.duplicate_register_number');
                }
                if ($errors !== []) {
                    return ['ok' => false, 'errors' => $errors];
                }
            }

            return ['ok' => false, 'errors' => ['_form' => __('error.server')]];
        }
    }

    /**
     * @param array{q?: string, date_from?: string, date_to?: string, page?: int} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function listPaginated(
        array $filters,
        string $sort,
        string $dir,
        int $page,
        int $perPage,
        int $viewerId,
        string $viewerRole
    ): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'bill_date');
        $ctx = self::buildListWhere($filters, $viewerId, $viewerRole);
        $offset = ($page - 1) * $perPage;

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $ctx['where']);

        $countSql = 'SELECT COUNT(*) AS total
                     FROM stock_bills sb
                     WHERE ' . $whereSql;
        $countStmt = db()->prepare($countSql);
        foreach ($ctx['bind'] as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $sql = 'SELECT sb.*, u.name AS submitted_by_name,
                    (
                        SELECT GROUP_CONCAT(sbi.item_name ORDER BY sbi.sort_order SEPARATOR \', \')
                        FROM stock_bill_items sbi
                        WHERE sbi.bill_id = sb.id
                    ) AS items_summary
                FROM stock_bills sb
                INNER JOIN users u ON u.id = sb.submitted_by
                WHERE ' . $whereSql . "
                ORDER BY {$orderSql}, sb.id DESC
                LIMIT :lim OFFSET :off";

        $stmt = db()->prepare($sql);
        foreach ($ctx['bind'] as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = self::mapListRow($row);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    public static function deleteById(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT file_stored_name FROM stock_bills WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row === false) {
                $pdo->rollBack();
                return false;
            }

            $del = $pdo->prepare('DELETE FROM stock_bills WHERE id = :id');
            $del->execute(['id' => $id]);

            $pdo->commit();

            // Attachment cleanup after commit (DB is source of truth).
            stock_bill_delete_bill_files($id);

            return true;
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $id, int $viewerId, string $viewerRole): ?array
    {
        if ($id < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT sb.*, u.name AS submitted_by_name
             FROM stock_bills sb
             INNER JOIN users u ON u.id = sb.submitted_by
             WHERE sb.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $bill = self::mapDetailRow($row);
        if (!self::canView($bill, $viewerId, $viewerRole)) {
            return null;
        }

        $bill['items'] = self::itemsForBill($id);

        return $bill;
    }

    public static function canView(array $bill, int $viewerId, string $viewerRole): bool
    {
        if (in_array($viewerRole, ['manager', 'admin'], true)) {
            return true;
        }

        return (int) ($bill['submitted_by'] ?? 0) === $viewerId;
    }

    /**
     * @return array{sort: string, dir: string}
     */
    public static function normalizeSort(string $sort, string $dir): array
    {
        $sort = array_key_exists($sort, self::SORT_COLUMNS) ? $sort : 'bill_date';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        return ['sort' => $sort, 'dir' => $dir];
    }

    public static function formatAmount(float $amount): string
    {
        return '₹' . number_format($amount, 2);
    }

    public static function formatQuantity(float $quantity): string
    {
        $formatted = number_format($quantity, 3, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    public static function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $dt !== false ? $dt->format('d M Y') : $date;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{bill_number: string, register_number: string, supplier: string, bill_date: string, delivery_date: string|null}
     */
    private static function sanitize(array $raw): array
    {
        $delivery = patient_normalize_filter_date($raw['delivery_date'] ?? null);

        return [
            'bill_number' => mb_substr(trim((string) ($raw['bill_number'] ?? '')), 0, 64),
            'register_number' => mb_substr(trim((string) ($raw['register_number'] ?? '')), 0, 64),
            'supplier' => mb_substr(trim((string) ($raw['supplier'] ?? '')), 0, 255),
            'bill_date' => patient_normalize_filter_date($raw['bill_date'] ?? null),
            'delivery_date' => $delivery !== '' ? $delivery : null,
        ];
    }

    /**
     * @param list<array{name: string, quantity: float|string, amount: float|string}> $lineItems
     * @return list<array{name: string, quantity: float, amount: float}>
     */
    private static function normalizeLineItems(array $lineItems): array
    {
        $items = [];
        foreach ($lineItems as $line) {
            $name = mb_substr(trim((string) ($line['name'] ?? '')), 0, 255);
            $quantity = round(max(0, (float) ($line['quantity'] ?? 0)), 3);
            $amount = round(max(0, (float) ($line['amount'] ?? 0)), 2);
            if ($name === '') {
                continue;
            }
            $items[] = ['name' => $name, 'quantity' => $quantity, 'amount' => $amount];
        }

        return $items;
    }

    /**
     * @param list<array{name: string, quantity: float, amount: float}> $items
     */
    private static function sumItemAmounts(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['amount'];
        }

        return round($total, 2);
    }

    /**
     * @param list<array{name: string, quantity: float, amount: float}> $items
     * @return array<string, string>
     */
    private static function validate(array $data, array $items, bool $hasFile): array
    {
        $errors = [];

        $required = __('validation.required');
        if ($data['bill_number'] === '') {
            $errors['bill_number'] = $required;
        }
        if ($data['register_number'] === '') {
            $errors['register_number'] = $required;
        }
        if ($data['supplier'] === '') {
            $errors['supplier'] = $required;
        }
        if ($data['bill_date'] === '') {
            $errors['bill_date'] = $required;
        }

        // Bill number and register number must be unique.
        if (!isset($errors['bill_number']) && !isset($errors['register_number'])
            && $data['bill_number'] !== '' && $data['register_number'] !== '') {
            $dupe = self::findDuplicateNumbers($data['bill_number'], $data['register_number']);
            if ($dupe['bill_number']) {
                $errors['bill_number'] = __('stock.error.duplicate_bill_number');
            }
            if ($dupe['register_number']) {
                $errors['register_number'] = __('stock.error.duplicate_register_number');
            }
        }
        if ($items === []) {
            $errors['items'] = __('stock.error.items');
        } else {
            foreach ($items as $item) {
                if ($item['quantity'] <= 0) {
                    $errors['items'] = __('stock.error.item_quantity');
                    break;
                }
                if ($item['amount'] <= 0) {
                    $errors['items'] = __('stock.error.item_amount');
                    break;
                }
            }
            if (!isset($errors['items']) && self::sumItemAmounts($items) <= 0) {
                $errors['items'] = __('stock.error.amount');
            }
        }
        if (!$hasFile) {
            $errors['file'] = $required;
        }

        return $errors;
    }

    /**
     * @return array{bill_number: bool, register_number: bool}
     */
    private static function findDuplicateNumbers(string $billNumber, string $registerNumber): array
    {
        $billNumber = trim($billNumber);
        $registerNumber = trim($registerNumber);
        if ($billNumber === '' && $registerNumber === '') {
            return ['bill_number' => false, 'register_number' => false];
        }

        $stmt = db()->prepare(
            'SELECT bill_number, register_number
             FROM stock_bills
             WHERE bill_number = :bill OR register_number = :reg
             LIMIT 10'
        );
        $stmt->execute(['bill' => $billNumber, 'reg' => $registerNumber]);

        $hasBill = false;
        $hasReg = false;
        foreach ($stmt->fetchAll() as $row) {
            if ((string) ($row['bill_number'] ?? '') === $billNumber) {
                $hasBill = true;
            }
            if ((string) ($row['register_number'] ?? '') === $registerNumber) {
                $hasReg = true;
            }
        }

        return ['bill_number' => $hasBill, 'register_number' => $hasReg];
    }

    /**
     * @param list<array{name: string, quantity: float, amount: float}> $items
     */
    private static function insertItems(int $billId, array $items): void
    {
        if ($items === []) {
            return;
        }

        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO stock_bill_items (bill_id, item_name, quantity, amount, sort_order)
             VALUES (:bill_id, :name, :quantity, :amount, :ord)'
        );
        foreach ($items as $index => $item) {
            $stmt->execute([
                'bill_id' => $billId,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'ord' => $index,
            ]);
        }
    }

    /**
     * @return list<array{id: int, item_name: string, quantity: float, amount: float}>
     */
    private static function itemsForBill(int $billId): array
    {
        $stmt = db()->prepare(
            'SELECT id, item_name, quantity, amount FROM stock_bill_items
             WHERE bill_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $billId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'item_name' => (string) $row['item_name'],
                'quantity' => round((float) $row['quantity'], 3),
                'amount' => round((float) $row['amount'], 2),
            ];
        }

        return $rows;
    }

    /**
     * @param array{q?: string, date_from?: string, date_to?: string} $filters
     * @return array{where: list<string>, bind: array<string, string|int>}
     */
    private static function buildListWhere(array $filters, int $viewerId, string $viewerRole): array
    {
        $where = ['1=1'];
        $bind = [];

        if (!in_array($viewerRole, ['manager', 'admin'], true)) {
            $where[] = 'sb.submitted_by = :viewer_id';
            $bind['viewer_id'] = $viewerId;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // MySQL PDO (native prepares) does not support reusing the same named placeholder multiple times.
            $where[] = '(sb.bill_number LIKE :q_bill OR sb.register_number LIKE :q_reg OR sb.supplier LIKE :q_sup OR EXISTS (
                SELECT 1 FROM stock_bill_items sbi
                WHERE sbi.bill_id = sb.id AND sbi.item_name LIKE :q_item
            ))';
            $like = db_like_contains($q);
            $bind['q_bill'] = $like;
            $bind['q_reg'] = $like;
            $bind['q_sup'] = $like;
            $bind['q_item'] = $like;
        }

        $dateFrom = patient_normalize_filter_date($filters['date_from'] ?? null);
        $dateTo = patient_normalize_filter_date($filters['date_to'] ?? null);
        if ($dateFrom !== '' && $dateTo !== '') {
            // Range filter when both ends are provided.
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $where[] = 'sb.bill_date >= :date_from';
            $bind['date_from'] = $dateFrom;
            $where[] = 'sb.bill_date <= :date_to';
            $bind['date_to'] = $dateTo;
        } elseif ($dateFrom !== '') {
            // Single date acts as exact-date filter.
            $where[] = 'sb.bill_date = :date_from';
            $bind['date_from'] = $dateFrom;
        } elseif ($dateTo !== '') {
            // If only "to" date is provided, treat it as exact-date filter.
            $where[] = 'sb.bill_date = :date_to';
            $bind['date_to'] = $dateTo;
        }

        return ['where' => $where, 'bind' => $bind];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function mapListRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'bill_number' => (string) $row['bill_number'],
            'register_number' => (string) ($row['register_number'] ?? ''),
            'supplier' => (string) ($row['supplier'] ?? ''),
            'bill_date' => (string) $row['bill_date'],
            'delivery_date' => $row['delivery_date'] !== null ? (string) $row['delivery_date'] : null,
            'amount' => round((float) $row['amount'], 2),
            'submitted_by' => (int) $row['submitted_by'],
            'submitted_by_name' => (string) $row['submitted_by_name'],
            'items_summary' => (string) ($row['items_summary'] ?? ''),
            'has_file' => !empty($row['file_stored_name']),
            'file_original_name' => (string) ($row['file_original_name'] ?? ''),
            'created_at' => (string) $row['created_at'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function mapDetailRow(array $row): array
    {
        $mapped = self::mapListRow($row);
        $mapped['file_mime'] = (string) ($row['file_mime'] ?? '');
        $mapped['file_size'] = isset($row['file_size']) ? (int) $row['file_size'] : 0;
        $mapped['file_stored_name'] = (string) ($row['file_stored_name'] ?? '');

        return $mapped;
    }
}

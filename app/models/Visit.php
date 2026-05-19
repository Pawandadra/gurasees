<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/GstSettings.php';
require_once APP_PATH . '/models/VisitSettings.php';

final class Visit
{
    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'date' => 'v.visited_at',
        'patient_id' => 'p.patient_code',
        'patient' => 'p.name',
        'visit_charge' => 'v.visit_charge',
        'medicine_total' => 'v.medicine_total',
        'total' => 'v.grand_total',
        'recorded_by' => 'u.name',
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

        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        return ['sort' => $sort, 'dir' => $dir];
    }

    /**
     * @param array{q?: string, visit_date?: string, medicine_id?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function listFiltered(
        array $filters,
        string $sort = 'date',
        string $dir = 'desc',
        int $page = 1,
        int $perPage = 50
    ): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $sortParams = self::normalizeSort($sort, $dir);
        $column = self::SORT_COLUMNS[$sortParams['sort']];
        $direction = $sortParams['dir'] === 'asc' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;

        $where = self::buildListWhere($filters);
        $fromSql = '
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN users u ON u.id = v.recorded_by';

        $pdo = db();
        $countStmt = $pdo->prepare('SELECT COUNT(*) ' . $fromSql . ' WHERE ' . $where['sql']);
        $countStmt->execute($where['bind']);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT v.id, v.visited_at, v.notes, v.visit_charge, v.visit_gst,
                    v.medicine_total, v.medicine_gst, v.grand_total,
                    p.patient_code, p.name AS patient_name,
                    u.name AS recorded_by_name '
            . $fromSql
            . " WHERE {$where['sql']}
             ORDER BY {$column} {$direction}, v.id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($where['bind'] as $key => $value) {
            $type = in_array($key, ['has_phone_digits', 'medicine_id'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return ['rows' => [], 'total' => $total];
        }

        $visitIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $linesByVisit = self::medicineLinesForVisits($visitIds);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['medicine_lines'] = $linesByVisit[$id] ?? [];
        }
        unset($row);

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array{q?: string, visit_date?: string, medicine_id?: string} $filters
     * @return array{sql: string, bind: array<string, mixed>}
     */
    private static function buildListWhere(array $filters): array
    {
        $parts = ['p.patient_code IS NOT NULL'];
        $bind = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $phoneDigits = preg_replace('/\D+/', '', $q) ?? '';
            $parts[] = '(
                p.patient_code LIKE :code
                OR p.name LIKE :name
                OR p.phone LIKE :phone
                OR (:has_phone_digits = 1 AND REPLACE(REPLACE(REPLACE(p.phone, \' \', \'\'), \'-\', \'\'), \'+\', \'\') LIKE :phone_digits)
            )';
            $bind['code'] = '%' . strtoupper(str_replace(' ', '', $q)) . '%';
            $bind['name'] = '%' . $q . '%';
            $bind['phone'] = '%' . $q . '%';
            $bind['has_phone_digits'] = $phoneDigits !== '' ? 1 : 0;
            $bind['phone_digits'] = $phoneDigits !== '' ? '%' . $phoneDigits . '%' : '0';
        }

        $visitDate = (string) ($filters['visit_date'] ?? '');
        if ($visitDate !== '') {
            $parts[] = 'v.visited_at >= :visit_date_start AND v.visited_at <= :visit_date_end';
            $bind['visit_date_start'] = $visitDate . ' 00:00:00';
            $bind['visit_date_end'] = $visitDate . ' 23:59:59';
        }

        $medicineId = filter_var($filters['medicine_id'] ?? '', FILTER_VALIDATE_INT);
        if ($medicineId !== false && $medicineId > 0) {
            $parts[] = 'EXISTS (
                SELECT 1 FROM visit_medicines vm_filter
                WHERE vm_filter.visit_id = v.id AND vm_filter.medicine_id = :medicine_id
            )';
            $bind['medicine_id'] = (int) $medicineId;
        }

        return ['sql' => implode(' AND ', $parts), 'bind' => $bind];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForPatient(int $patientId): array
    {
        if ($patientId < 1) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT v.id, v.visited_at, v.notes, v.visit_charge, v.visit_gst,
                    v.medicine_total, v.medicine_gst, v.grand_total, v.created_at,
                    u.name AS recorded_by_name
             FROM visits v
             LEFT JOIN users u ON u.id = v.recorded_by
             WHERE v.patient_id = :pid
             ORDER BY v.visited_at DESC, v.id DESC'
        );
        $stmt->execute(['pid' => $patientId]);
        $visits = $stmt->fetchAll();

        if ($visits === []) {
            return [];
        }

        $visitIds = array_map(static fn (array $v): int => (int) $v['id'], $visits);
        $linesByVisit = self::medicineLinesForVisits($visitIds);

        foreach ($visits as &$visit) {
            $id = (int) $visit['id'];
            $visit['medicine_lines'] = $linesByVisit[$id] ?? [];
        }
        unset($visit);

        return $visits;
    }

    /**
     * @param list<int> $visitIds
     * @return array<int, list<array{name: string, quantity: int, unit_price: string, line_total: string}>>
     */
    public static function medicineLinesForVisits(array $visitIds): array
    {
        $visitIds = array_values(array_filter(array_map('intval', $visitIds), static fn (int $id): bool => $id > 0));
        if ($visitIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($visitIds), '?'));
        $stmt = db()->prepare(
            "SELECT vm.visit_id, m.name, vm.quantity, vm.unit_price, vm.line_total
             FROM visit_medicines vm
             INNER JOIN medicines m ON m.id = vm.medicine_id
             WHERE vm.visit_id IN ($placeholders)
             ORDER BY m.name ASC"
        );
        $stmt->execute($visitIds);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $vid = (int) $row['visit_id'];
            $map[$vid] ??= [];
            $map[$vid][] = [
                'name' => (string) $row['name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => Medicine::formatPrice((float) $row['unit_price']),
                'line_total' => Medicine::formatPrice((float) $row['line_total']),
            ];
        }

        return $map;
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function create(int $patientId, array $raw, ?int $recordedBy = null): array
    {
        if ($patientId < 1) {
            return ['ok' => false, 'errors' => ['_form' => __('patient.error.not_found')]];
        }

        $visitedAt = self::parseVisitedAt((string) ($raw['visited_at'] ?? ''));
        if ($visitedAt === null) {
            return ['ok' => false, 'errors' => ['visited_at' => __('visit.error.datetime')]];
        }

        $visitCharge = self::parseVisitCharge($raw);
        if ($visitCharge === null) {
            return ['ok' => false, 'errors' => ['visit_charge' => __('visit.error.charge')]];
        }

        $notes = input_string($raw['notes'] ?? '', 500);
        $lines = self::parseMedicineLines($raw);
        $validation = Medicine::validateVisitLines($lines);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors']];
        }

        $visitGst = GstSettings::amountOnBase($visitCharge, GstSettings::visitChargePercent());
        $medicineSubtotal = $validation['total'];
        $medicineGst = GstSettings::amountOnBase($medicineSubtotal, GstSettings::medicinePercent());
        $grandTotal = round($visitCharge + $visitGst + $medicineSubtotal + $medicineGst, 2);

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO visits (
                    patient_id, visited_at, notes, visit_charge, visit_gst,
                    medicine_total, medicine_gst, grand_total, recorded_by
                 ) VALUES (
                    :patient_id, :visited_at, :notes, :visit_charge, :visit_gst,
                    :medicine_total, :medicine_gst, :grand_total, :recorded_by
                 )'
            );
            $stmt->execute([
                'patient_id' => $patientId,
                'visited_at' => $visitedAt->format('Y-m-d H:i:s'),
                'notes' => $notes !== '' ? $notes : null,
                'visit_charge' => $visitCharge,
                'visit_gst' => $visitGst,
                'medicine_total' => $medicineSubtotal,
                'medicine_gst' => $medicineGst,
                'grand_total' => $grandTotal,
                'recorded_by' => $recordedBy !== null && $recordedBy > 0 ? $recordedBy : null,
            ]);

            $visitId = (int) $pdo->lastInsertId();
            if ($lines !== []) {
                Medicine::attachToVisit($visitId, $lines);
            }

            $pdo->commit();
        } catch (Throwable) {
            $pdo->rollBack();

            return ['ok' => false, 'errors' => ['_form' => __('reception.error.database')]];
        }

        return ['ok' => true];
    }

    /**
     * @return list<array{medicine_id: int, quantity: int}>
     */
    public static function parseMedicineLines(array $raw): array
    {
        $ids = $raw['medicine_id'] ?? [];
        $qtys = $raw['medicine_qty'] ?? [];

        if (!is_array($ids) || !is_array($qtys)) {
            return [];
        }

        $lines = [];
        $count = max(count($ids), count($qtys));
        for ($i = 0; $i < $count; $i++) {
            $lines[] = [
                'medicine_id' => (int) ($ids[$i] ?? 0),
                'quantity' => (int) ($qtys[$i] ?? 0),
            ];
        }

        return $lines;
    }

    public static function formatVisitedAt(string $datetime): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($dt === false) {
            return $datetime;
        }

        return $dt->format('d M Y, h:i A');
    }

    /**
     * @param list<array{name: string, quantity: int}> $lines
     */
    public static function formatMedicineSummary(array $lines): string
    {
        if ($lines === []) {
            return '—';
        }

        $parts = [];
        foreach ($lines as $line) {
            $parts[] = $line['name'] . ' ×' . (int) $line['quantity'];
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<string, string>
     */
    public static function billingDefaults(): array
    {
        return [
            'visit_charge' => VisitSettings::formatCharge(VisitSettings::defaultCharge()),
            'gst_visit_percent' => GstSettings::formatPercent(GstSettings::visitChargePercent()),
            'gst_medicine_percent' => GstSettings::formatPercent(GstSettings::medicinePercent()),
        ];
    }

    private static function parseVisitCharge(array $raw): ?float
    {
        $value = trim((string) ($raw['visit_charge'] ?? ''));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return max(0.0, round((float) $value, 2));
    }

    private static function parseVisitedAt(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt !== false) {
                return $dt;
            }
        }

        return null;
    }
}

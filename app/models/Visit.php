<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/GstSettings.php';
require_once APP_PATH . '/models/VisitSettings.php';
require_once APP_PATH . '/models/PaymentSettings.php';
require_once APP_PATH . '/models/CourierSettings.php';

final class Visit
{
    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'date' => 'v.visited_at',
        'patient_id' => 'p.patient_code',
        'patient' => 'p.name',
        'age' => 'p.age',
        'gender' => 'p.gender',
        'phone' => 'p.phone',
        'total' => 'v.grand_total',
        'payment_method' => 'v.payment_method',
        'payment_status' => 'v.payment_status',
        'recorded_by' => 'u.name',
    ];

    /**
     * @return array{sort: string, dir: string}
     */
    public static function normalizeSort(string $sort, string $dir): array
    {
        $sort = strtolower($sort);
        if ($sort === 'time') {
            $sort = 'date';
        }
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
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'date');
        $offset = ($page - 1) * $perPage;

        $where = self::buildListWhere($filters);
        $fromSql = '
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN users u ON u.id = v.recorded_by';

        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT v.id, v.visited_at, v.notes, v.grand_total,
                    v.payment_method, v.payment_status, v.payment_paid_amount,
                    p.patient_code, p.name AS patient_name, p.age, p.gender, p.phone,
                    u.name AS recorded_by_name,
                    COUNT(*) OVER() AS _list_total '
            . $fromSql
            . " WHERE {$where['sql']}
             ORDER BY {$orderSql}, v.id DESC
             LIMIT :lim OFFSET :off"
        );
        db_bind_named($stmt, $where['bind'], ['has_phone_digits', 'medicine_id']);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $listed = db_strip_list_total($stmt->fetchAll());
        $rows = $listed['rows'];
        $total = $listed['total'];
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

    public static function countToday(): int
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE p.patient_code IS NOT NULL
               AND v.visited_at >= :visit_date_start
               AND v.visited_at <= :visit_date_end'
        );
        $stmt->execute([
            'visit_date_start' => $today . ' 00:00:00',
            'visit_date_end' => $today . ' 23:59:59',
        ]);

        return (int) $stmt->fetchColumn();
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
            $search = db_patient_search_clause('p', $q);
            $parts[] = $search['sql'];
            $bind = array_merge($bind, $search['bind']);
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
                    v.medicine_total, v.medicine_gst, v.grand_total,
                    v.payment_method, v.payment_status, v.payment_paid_amount,
                    v.created_at, u.name AS recorded_by_name
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
        $visitIds = array_values(array_unique(array_filter(
            array_map('intval', $visitIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($visitIds === []) {
            return [];
        }

        $map = [];
        $sql = 'SELECT vm.visit_id, m.name, vm.quantity, vm.courier_quantity, vm.unit_price, vm.line_total
                FROM visit_medicines vm
                INNER JOIN medicines m ON m.id = vm.medicine_id
                WHERE vm.visit_id IN (%s)
                ORDER BY vm.visit_id ASC, m.name ASC';

        foreach (array_chunk($visitIds, 250) as $chunk) {
            $stmt = db()->prepare(sprintf($sql, db_sql_in_placeholders(count($chunk))));
            $stmt->execute($chunk);

            foreach ($stmt->fetchAll() as $row) {
                $vid = (int) $row['visit_id'];
                $map[$vid] ??= [];
                $map[$vid][] = [
                    'name' => (string) $row['name'],
                    'quantity' => (int) $row['quantity'],
                    'courier_quantity' => (int) $row['courier_quantity'],
                    'unit_price' => Medicine::formatPrice((float) $row['unit_price']),
                    'line_total' => Medicine::formatPrice((float) $row['line_total']),
                ];
            }
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
        $courierBilling = CourierSettings::billingForLines($lines);
        $courierCharge = $courierBilling['charge'];
        $courierGst = $courierBilling['gst'];
        $grandTotal = round(
            $visitCharge + $visitGst + $medicineSubtotal + $medicineGst + $courierCharge + $courierGst,
            2
        );

        $payment = PaymentSettings::sanitizeVisitPayment($raw, $grandTotal);
        $paymentErrors = PaymentSettings::validateVisitPayment($payment, $grandTotal);
        if ($paymentErrors !== []) {
            return ['ok' => false, 'errors' => $paymentErrors];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO visits (
                    patient_id, visited_at, notes, visit_charge, visit_gst,
                    medicine_total, medicine_gst, courier_charge, courier_gst, grand_total,
                    payment_method, payment_status, payment_paid_amount, recorded_by
                 ) VALUES (
                    :patient_id, :visited_at, :notes, :visit_charge, :visit_gst,
                    :medicine_total, :medicine_gst, :courier_charge, :courier_gst, :grand_total,
                    :payment_method, :payment_status, :payment_paid_amount, :recorded_by
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
                'courier_charge' => $courierCharge,
                'courier_gst' => $courierGst,
                'grand_total' => $grandTotal,
                'payment_method' => $payment['payment_method'],
                'payment_status' => $payment['payment_status'],
                'payment_paid_amount' => $payment['payment_status'] === 'partial'
                    ? $payment['payment_paid_amount']
                    : null,
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
     * @return list<array{medicine_id: int, quantity: int, courier_quantity: int}>
     */
    public static function parseMedicineLines(array $raw): array
    {
        $ids = $raw['medicine_id'] ?? [];
        $qtys = $raw['medicine_qty'] ?? [];
        $courierFlags = $raw['medicine_courier'] ?? [];

        if (!is_array($ids) || !is_array($qtys)) {
            return [];
        }

        $lines = [];
        $count = max(count($ids), count($qtys));
        for ($i = 0; $i < $count; $i++) {
            $qty = (int) ($qtys[$i] ?? 0);
            $forCourier = !empty($courierFlags[$i]) && (string) $courierFlags[$i] === '1';
            $lines[] = [
                'medicine_id' => (int) ($ids[$i] ?? 0),
                'quantity' => $qty,
                'courier_quantity' => $forCourier ? $qty : 0,
            ];
        }

        return $lines;
    }

    public static function formatVisitedAt(string $datetime): string
    {
        $dt = self::parseVisitedAt($datetime);
        if ($dt === null) {
            return $datetime;
        }

        return $dt->format('d M Y, h:i A');
    }

    public static function formatVisitedTime(string $datetime): string
    {
        $dt = self::parseVisitedAt($datetime);
        if ($dt === null) {
            return $datetime;
        }

        return $dt->format('h:i A');
    }

    public static function visitedDateKey(string $datetime): string
    {
        $dt = self::parseVisitedAt($datetime);

        return $dt !== null ? $dt->format('Y-m-d') : $datetime;
    }

    public static function formatDateGroupLabel(string $dateYmd): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false) {
            return $dateYmd;
        }

        $today = new DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');
        $key = $dt->format('Y-m-d');

        if ($key === $today->format('Y-m-d')) {
            return __('date.today');
        }
        if ($key === $yesterday->format('Y-m-d')) {
            return __('date.yesterday');
        }

        $currentYear = (int) $today->format('Y');

        return (int) $dt->format('Y') === $currentYear
            ? $dt->format('d M')
            : $dt->format('d M Y');
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

    /**
     * @return array<string, string>
     */
    public static function billingDefaults(): array
    {
        return array_merge(
            [
                'visit_charge' => VisitSettings::formatCharge(VisitSettings::defaultCharge()),
                'courier_charge' => CourierSettings::formatCharge(CourierSettings::defaultCharge()),
                'gst_visit_percent' => GstSettings::formatPercent(GstSettings::visitChargePercent()),
                'gst_medicine_percent' => GstSettings::formatPercent(GstSettings::medicinePercent()),
                'gst_courier_percent' => GstSettings::formatPercent(GstSettings::courierPercent()),
            ],
            PaymentSettings::visitDefaults()
        );
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

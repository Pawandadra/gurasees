<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/GstSettings.php';
require_once APP_PATH . '/models/VisitSettings.php';
require_once APP_PATH . '/models/PaymentSettings.php';
require_once APP_PATH . '/models/CourierSettings.php';

final class Visit
{
    public const DELIVERY_SELF = 'self';
    public const DELIVERY_BY_BUS = 'by_bus';
    public const DELIVERY_COURIER = 'courier';

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
     * @param array{q?: string, visit_date?: string, medicine_id?: string, delivery_method?: string} $filters
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
        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) AS total ' . $fromSql . " WHERE {$where['sql']}"
        );
        db_bind_named($countStmt, $where['bind'], ['has_phone_digits', 'medicine_id']);
        $countStmt->execute();
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = $pdo->prepare(
            'SELECT v.id, v.visited_at, v.notes, v.grand_total, v.delivery_method,
                    v.payment_method, v.payment_status, v.payment_paid_amount,
                    p.patient_code, p.name AS patient_name, p.age, p.gender, p.phone, p.additional_phone,
                    u.name AS recorded_by_name '
            . $fromSql
            . " WHERE {$where['sql']}
             ORDER BY {$orderSql}, v.id DESC
             LIMIT :lim OFFSET :off"
        );
        db_bind_named($stmt, $where['bind'], ['has_phone_digits', 'medicine_id']);
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
     * @param array{q?: string, visit_date?: string, medicine_id?: string, delivery_method?: string} $filters
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

        $deliveryMethod = self::normalizeDeliveryMethodFilter((string) ($filters['delivery_method'] ?? ''));
        if ($deliveryMethod !== '') {
            $parts[] = 'v.delivery_method = :delivery_method';
            $bind['delivery_method'] = $deliveryMethod;
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
     * @return array<int, list<array{name: string, quantity: int, courier_quantity: int}>>
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
        $sql = 'SELECT vm.visit_id, vm.medicine_id, m.name, vm.quantity, vm.courier_quantity
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
                    'medicine_id' => (int) $row['medicine_id'],
                    'name' => (string) $row['name'],
                    'quantity' => (int) $row['quantity'],
                    'courier_quantity' => (int) $row['courier_quantity'],
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
        $deliveryMethod = self::parseDeliveryMethod($raw);
        $lines = self::parseMedicineLines($raw);
        $medicineSubtotal = self::parseMedicineTotal($raw);
        if ($medicineSubtotal === null) {
            return ['ok' => false, 'errors' => ['medicine_total' => __('visit.error.medicine_total')]];
        }

        $validation = Medicine::validateVisitLines($lines);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors']];
        }

        $visitSplit = GstSettings::splitInclusiveTotal($visitCharge, GstSettings::visitChargePercent());
        $medicineSplit = GstSettings::splitInclusiveTotal($medicineSubtotal, GstSettings::medicinePercent());
        $hasCourier = CourierSettings::appliesToLines($lines);
        $courierSplit = ['base' => 0.0, 'gst' => 0.0, 'total' => 0.0];
        if ($hasCourier) {
            $courierNet = self::parseCourierTotal($raw);
            if ($courierNet === null) {
                return ['ok' => false, 'errors' => ['courier_charge' => __('visit.error.courier_total')]];
            }
            $courierSplit = GstSettings::splitInclusiveTotal($courierNet, GstSettings::courierPercent());
        }
        $grandTotal = round(
            $visitSplit['total'] + $medicineSplit['total'] + $courierSplit['total'],
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
                    patient_id, visited_at, notes, delivery_method, visit_charge, visit_gst,
                    medicine_total, medicine_gst, courier_charge, courier_gst, grand_total,
                    payment_method, payment_status, payment_paid_amount, recorded_by
                 ) VALUES (
                    :patient_id, :visited_at, :notes, :delivery_method, :visit_charge, :visit_gst,
                    :medicine_total, :medicine_gst, :courier_charge, :courier_gst, :grand_total,
                    :payment_method, :payment_status, :payment_paid_amount, :recorded_by
                 )'
            );
            $stmt->execute([
                'patient_id' => $patientId,
                'visited_at' => $visitedAt->format('Y-m-d H:i:s'),
                'notes' => $notes !== '' ? $notes : null,
                'delivery_method' => $deliveryMethod,
                'visit_charge' => $visitSplit['base'],
                'visit_gst' => $visitSplit['gst'],
                'medicine_total' => $medicineSplit['base'],
                'medicine_gst' => $medicineSplit['gst'],
                'courier_charge' => $courierSplit['base'],
                'courier_gst' => $courierSplit['gst'],
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

            if (self::shouldTrackDeliveryStatus($deliveryMethod, $lines)) {
                $statusStmt = $pdo->prepare(
                    'UPDATE visits SET courier_status = :status WHERE id = :id'
                );
                $statusStmt->execute([
                    'status' => 'pending',
                    'id' => $visitId,
                ]);
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

    public static function formatVisitedDate(string $datetime): string
    {
        $dt = self::parseVisitedAt($datetime);
        if ($dt === null) {
            return $datetime;
        }

        return $dt->format('d M Y');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findForPatient(int $visitId, int $patientId): ?array
    {
        if ($visitId < 1 || $patientId < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT v.*, u.name AS recorded_by_name, p.patient_code
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             LEFT JOIN users u ON u.id = v.recorded_by
             WHERE v.id = :vid AND v.patient_id = :pid
             LIMIT 1'
        );
        $stmt->execute(['vid' => $visitId, 'pid' => $patientId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $row['medicine_lines'] = self::medicineLinesForVisits([(int) $row['id']])[(int) $row['id']] ?? [];

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $visitId): ?array
    {
        if ($visitId < 1) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT v.*, u.name AS recorded_by_name, p.patient_code
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             LEFT JOIN users u ON u.id = v.recorded_by
             WHERE v.id = :vid AND p.patient_code IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute(['vid' => $visitId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $row['medicine_lines'] = self::medicineLinesForVisits([(int) $row['id']])[(int) $row['id']] ?? [];

        return $row;
    }

    /**
     * @param array<string, mixed> $visit
     */
    public static function canModify(array $visit): bool
    {
        return (string) ($visit['courier_status'] ?? '') !== 'sent';
    }

    /**
     * @param array<string, mixed> $visit
     * @return array<string, mixed>
     */
    public static function recordToFormState(array $visit): array
    {
        $dt = self::parseVisitedAt((string) ($visit['visited_at'] ?? ''));
        $visitInclusive = round((float) ($visit['visit_charge'] ?? 0) + (float) ($visit['visit_gst'] ?? 0), 2);
        $medicineInclusive = round((float) ($visit['medicine_total'] ?? 0) + (float) ($visit['medicine_gst'] ?? 0), 2);
        $courierInclusive = round((float) ($visit['courier_charge'] ?? 0) + (float) ($visit['courier_gst'] ?? 0), 2);

        $medicines = [];
        foreach ($visit['medicine_lines'] ?? [] as $line) {
            $medicines[] = [
                'medicine_id' => (int) ($line['medicine_id'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 0),
                'courier_quantity' => (int) ($line['courier_quantity'] ?? 0),
            ];
        }

        $status = (string) ($visit['payment_status'] ?? '');
        $paid = $visit['payment_paid_amount'] ?? null;

        return [
            'visited_at' => $dt !== null ? $dt->format('Y-m-d\TH:i') : '',
            'notes' => (string) ($visit['notes'] ?? ''),
            'delivery_method' => self::resolveDeliveryMethod($visit, $medicines),
            'visit_charge' => Medicine::formatPrice($visitInclusive),
            'medicine_total' => $medicineInclusive > 0 ? Medicine::formatPrice($medicineInclusive) : '',
            'courier_charge' => $courierInclusive > 0 ? Medicine::formatPrice($courierInclusive) : '',
            'payment_method' => (string) ($visit['payment_method'] ?? ''),
            'payment_status' => $status,
            'payment_paid_amount' => $status === 'partial' && $paid !== null
                ? Medicine::formatPrice((float) $paid)
                : '',
            'medicines' => $medicines,
        ];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function update(int $visitId, int $patientId, array $raw): array
    {
        $visit = self::findForPatient($visitId, $patientId);
        if ($visit === null) {
            return ['ok' => false, 'errors' => ['_form' => __('visit.error.not_found')]];
        }

        if (!self::canModify($visit)) {
            return ['ok' => false, 'errors' => ['_form' => __('visit.error.courier_sent')]];
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
        $deliveryMethod = self::parseDeliveryMethod($raw);
        $lines = self::parseMedicineLines($raw);
        $medicineSubtotal = self::parseMedicineTotal($raw);
        if ($medicineSubtotal === null) {
            return ['ok' => false, 'errors' => ['medicine_total' => __('visit.error.medicine_total')]];
        }

        $validation = Medicine::validateVisitLines($lines);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors']];
        }

        $visitSplit = GstSettings::splitInclusiveTotal($visitCharge, GstSettings::visitChargePercent());
        $medicineSplit = GstSettings::splitInclusiveTotal($medicineSubtotal, GstSettings::medicinePercent());
        $hasCourier = CourierSettings::appliesToLines($lines);
        $courierSplit = ['base' => 0.0, 'gst' => 0.0, 'total' => 0.0];
        if ($hasCourier) {
            $courierNet = self::parseCourierTotal($raw);
            if ($courierNet === null) {
                return ['ok' => false, 'errors' => ['courier_charge' => __('visit.error.courier_total')]];
            }
            $courierSplit = GstSettings::splitInclusiveTotal($courierNet, GstSettings::courierPercent());
        }
        $grandTotal = round(
            $visitSplit['total'] + $medicineSplit['total'] + $courierSplit['total'],
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
            Medicine::restoreVisitStock($visitId);

            $del = $pdo->prepare('DELETE FROM visit_medicines WHERE visit_id = :vid');
            $del->execute(['vid' => $visitId]);

            $stmt = $pdo->prepare(
                'UPDATE visits
                 SET visited_at = :visited_at, notes = :notes, delivery_method = :delivery_method,
                     visit_charge = :visit_charge, visit_gst = :visit_gst,
                     medicine_total = :medicine_total, medicine_gst = :medicine_gst,
                     courier_charge = :courier_charge, courier_gst = :courier_gst,
                     grand_total = :grand_total,
                     payment_method = :payment_method, payment_status = :payment_status,
                     payment_paid_amount = :payment_paid_amount,
                     courier_status = :courier_status,
                     courier_dispatched_at = NULL, courier_dispatched_by = NULL
                 WHERE id = :id AND patient_id = :pid'
            );
            $courierStatus = self::shouldTrackDeliveryStatus($deliveryMethod, $lines) ? 'pending' : null;
            $stmt->execute([
                'visited_at' => $visitedAt->format('Y-m-d H:i:s'),
                'notes' => $notes !== '' ? $notes : null,
                'delivery_method' => $deliveryMethod,
                'visit_charge' => $visitSplit['base'],
                'visit_gst' => $visitSplit['gst'],
                'medicine_total' => $medicineSplit['base'],
                'medicine_gst' => $medicineSplit['gst'],
                'courier_charge' => $courierSplit['base'],
                'courier_gst' => $courierSplit['gst'],
                'grand_total' => $grandTotal,
                'payment_method' => $payment['payment_method'],
                'payment_status' => $payment['payment_status'],
                'payment_paid_amount' => $payment['payment_status'] === 'partial'
                    ? $payment['payment_paid_amount']
                    : null,
                'courier_status' => $courierStatus,
                'id' => $visitId,
                'pid' => $patientId,
            ]);

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
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function delete(int $visitId, int $patientId): array
    {
        $visit = self::findForPatient($visitId, $patientId);
        if ($visit === null) {
            return ['ok' => false, 'errors' => ['_form' => __('visit.error.not_found')]];
        }

        if (!self::canModify($visit)) {
            return ['ok' => false, 'errors' => ['_form' => __('visit.error.courier_sent')]];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            Medicine::restoreVisitStock($visitId);

            $stmt = $pdo->prepare('DELETE FROM visits WHERE id = :id AND patient_id = :pid LIMIT 1');
            $stmt->execute(['id' => $visitId, 'pid' => $patientId]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();

                return ['ok' => false, 'errors' => ['_form' => __('visit.error.not_found')]];
            }

            $pdo->commit();
        } catch (Throwable) {
            $pdo->rollBack();

            return ['ok' => false, 'errors' => ['_form' => __('reception.error.database')]];
        }

        return ['ok' => true];
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
     * @param array<string, mixed> $visit
     * @param list<array{name: string, quantity: int, courier_quantity?: int, medicine_id?: int}> $lines
     * @return list<array{label: string, lines: list<array{name: string, quantity: int}>}>
     */
    public static function medicineDetailSections(array $visit, array $lines): array
    {
        if ($lines === []) {
            return [['label' => __('visit.field.medicines'), 'lines' => []]];
        }

        $deliveryMethod = self::resolveDeliveryMethod($visit, $lines);

        $self = [];
        $byBus = [];
        $courier = [];

        foreach ($lines as $line) {
            if ((int) ($line['courier_quantity'] ?? 0) > 0) {
                if ($deliveryMethod === self::DELIVERY_BY_BUS) {
                    $byBus[] = $line;
                } else {
                    $courier[] = $line;
                }
            } else {
                $self[] = $line;
            }
        }

        if ($byBus === [] && $courier === []) {
            return [['label' => __('visit.field.medicines'), 'lines' => $lines]];
        }

        $sections = [];
        if ($self !== []) {
            $sections[] = ['label' => __('visit.field.medicines'), 'lines' => $self];
        }
        if ($byBus !== []) {
            $sections[] = ['label' => __('visit.medicines.by_bus'), 'lines' => $byBus];
        }
        if ($courier !== []) {
            $sections[] = ['label' => __('visit.medicines.courier'), 'lines' => $courier];
        }

        return $sections;
    }

    /**
     * @param list<array<string, mixed>> $visits
     */
    public static function listHasPartialPayment(array $visits): bool
    {
        foreach ($visits as $visit) {
            if ((string) ($visit['payment_status'] ?? '') === 'partial') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $visit
     */
    public static function paymentPaidAmount(array $visit): float
    {
        $grandTotal = round((float) ($visit['grand_total'] ?? 0), 2);

        return match ((string) ($visit['payment_status'] ?? '')) {
            'paid' => $grandTotal,
            'partial' => round((float) ($visit['payment_paid_amount'] ?? 0), 2),
            default => 0.0,
        };
    }

    /**
     * @param array<string, mixed> $visit
     */
    public static function paymentBalance(array $visit): float
    {
        $grandTotal = (float) ($visit['grand_total'] ?? 0);
        if ($grandTotal <= 0) {
            return 0.0;
        }

        $status = (string) ($visit['payment_status'] ?? '');

        return match ($status) {
            'pending' => $grandTotal,
            'partial' => max(0.0, round($grandTotal - self::paymentPaidAmount($visit), 2)),
            default => 0.0,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function billingDefaults(): array
    {
        return array_merge(
            [
                'visit_charge' => VisitSettings::formatCharge(VisitSettings::defaultCharge()),
                'gst_visit_percent' => GstSettings::formatPercent(GstSettings::visitChargePercent()),
                'gst_medicine_percent' => GstSettings::formatPercent(GstSettings::medicinePercent()),
                'gst_courier_percent' => GstSettings::formatPercent(GstSettings::courierPercent()),
            ],
            PaymentSettings::visitDefaults()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function deliveryMethodOptions(): array
    {
        return [
            self::DELIVERY_SELF => 'visit.delivery.self',
            self::DELIVERY_BY_BUS => 'visit.delivery.by_bus',
            self::DELIVERY_COURIER => 'visit.delivery.courier',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function remoteDeliveryMethodOptions(): array
    {
        return array_intersect_key(
            self::deliveryMethodOptions(),
            array_flip([self::DELIVERY_BY_BUS, self::DELIVERY_COURIER])
        );
    }

    public static function normalizeDeliveryMethodFilter(string $method, bool $remoteOnly = false): string
    {
        $method = strtolower(trim($method));
        $options = $remoteOnly ? self::remoteDeliveryMethodOptions() : self::deliveryMethodOptions();

        return isset($options[$method]) ? $method : '';
    }

    public static function isRemoteDeliveryMethod(string $method): bool
    {
        return in_array($method, [self::DELIVERY_BY_BUS, self::DELIVERY_COURIER], true);
    }

    public static function remoteDeliveryPackageSql(string $visitAlias = 'v'): string
    {
        return "{$visitAlias}.delivery_method IN ('by_bus', 'courier')
            AND EXISTS (
                SELECT 1 FROM visit_medicines vm_pkg
                WHERE vm_pkg.visit_id = {$visitAlias}.id AND vm_pkg.courier_quantity > 0
            )";
    }

    /**
     * @param list<array{courier_quantity?: int}> $lines
     */
    public static function shouldTrackDeliveryStatus(string $deliveryMethod, array $lines): bool
    {
        return self::isRemoteDeliveryMethod($deliveryMethod) && CourierSettings::appliesToLines($lines);
    }

    public static function parseDeliveryMethod(array $raw): string
    {
        $method = self::normalizeDeliveryMethodFilter((string) ($raw['delivery_method'] ?? self::DELIVERY_SELF));

        return $method !== '' ? $method : self::DELIVERY_SELF;
    }

    public static function deliveryMethodLabel(string $method): string
    {
        $options = self::deliveryMethodOptions();

        return __($options[$method] ?? 'visit.delivery.self');
    }

    /**
     * @param list<array{medicine_id?: int, quantity?: int, courier_quantity?: int}> $medicines
     */
    private static function resolveDeliveryMethod(array $visit, array $medicines): string
    {
        $method = strtolower(trim((string) ($visit['delivery_method'] ?? '')));
        if ($method === self::DELIVERY_BY_BUS || $method === self::DELIVERY_COURIER) {
            return $method;
        }

        foreach ($medicines as $line) {
            if (($line['courier_quantity'] ?? 0) > 0) {
                return self::DELIVERY_COURIER;
            }
        }

        return self::DELIVERY_SELF;
    }

    private static function parseVisitCharge(array $raw): ?float
    {
        $value = trim((string) ($raw['visit_charge'] ?? ''));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return max(0.0, round((float) $value, 2));
    }

    private static function parseMedicineTotal(array $raw): ?float
    {
        $value = trim((string) ($raw['medicine_total'] ?? ''));
        if ($value === '') {
            return 0.0;
        }
        if (!is_numeric($value)) {
            return null;
        }

        return max(0.0, round((float) $value, 2));
    }

    private static function parseCourierTotal(array $raw): ?float
    {
        $value = trim((string) ($raw['courier_charge'] ?? ''));
        if ($value === '') {
            return 0.0;
        }
        if (!is_numeric($value)) {
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

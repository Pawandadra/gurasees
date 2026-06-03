<?php

declare(strict_types=1);

require_once APP_PATH . '/models/PaymentSettings.php';
require_once APP_PATH . '/models/Visit.php';

final class Payment
{
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_VISIT = 'visit';

    public const PERIOD_TODAY = 'today';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';
    public const PERIOD_ALL = 'all';

    /** @var list<string> */
    public const PERIODS = [
        self::PERIOD_TODAY,
        self::PERIOD_WEEK,
        self::PERIOD_MONTH,
        self::PERIOD_YEAR,
        self::PERIOD_ALL,
    ];

    /** @var array<string, string> */
    private const SORT_COLUMNS = [
        'date' => 'payment_date',
        'patient' => 'patient_name',
        'total' => 'total_amount',
        'without_gst' => 'amount_without_gst',
        'gst' => 'gst_amount',
        'status' => 'payment_status',
        'type' => 'payment_type',
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

    public static function normalizePeriod(string $period): string
    {
        $period = strtolower(trim($period));

        return in_array($period, self::PERIODS, true) ? $period : self::PERIOD_TODAY;
    }

    /**
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return [
            self::PERIOD_TODAY => __('payment.period.today'),
            self::PERIOD_WEEK => __('payment.period.week'),
            self::PERIOD_MONTH => __('payment.period.month'),
            self::PERIOD_YEAR => __('payment.period.year'),
            self::PERIOD_ALL => __('payment.period.all'),
        ];
    }

    public static function periodLabel(string $period): string
    {
        $options = self::periodOptions();

        return $options[self::normalizePeriod($period)] ?? $period;
    }

    /**
     * @return array{start: string, end: string}|null
     */
    public static function periodBounds(string $period): ?array
    {
        $period = self::normalizePeriod($period);
        if ($period === self::PERIOD_ALL) {
            return null;
        }

        $now = new DateTimeImmutable('now');

        return match ($period) {
            self::PERIOD_TODAY => [
                'start' => $now->format('Y-m-d 00:00:00'),
                'end' => $now->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
            self::PERIOD_WEEK => self::weekBounds($now),
            self::PERIOD_MONTH => [
                'start' => $now->modify('first day of this month')->format('Y-m-d 00:00:00'),
                'end' => $now->modify('first day of next month')->format('Y-m-d 00:00:00'),
            ],
            self::PERIOD_YEAR => [
                'start' => $now->modify('first day of january this year')->format('Y-m-d 00:00:00'),
                'end' => $now->modify('first day of january next year')->format('Y-m-d 00:00:00'),
            ],
            default => [
                'start' => $now->format('Y-m-d 00:00:00'),
                'end' => $now->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
        };
    }

    /**
     * @param array{q?: string, status?: string, type?: string, date_from?: string, date_to?: string} $filters
     * @return array{paid_total: float, pending_total: float}
     */
    public static function summary(array $filters = [], string $period = self::PERIOD_TODAY): array
    {
        $periodKey = self::normalizePeriod($period);
        $ctx = self::buildLedgerWhere($filters, $periodKey === self::PERIOD_ALL ? self::PERIOD_ALL : $periodKey);

        $sql = 'SELECT
                    SUM(CASE
                        WHEN payment_status = \'paid\' THEN total_amount
                        WHEN payment_status = \'partial\' THEN LEAST(
                            GREATEST(COALESCE(payment_paid_amount, 0), 0),
                            total_amount
                        )
                        ELSE 0
                    END) AS paid_total,
                    SUM(CASE
                        WHEN payment_status = \'pending\' THEN total_amount
                        WHEN payment_status = \'partial\' THEN GREATEST(
                            0,
                            total_amount - LEAST(
                                GREATEST(COALESCE(payment_paid_amount, 0), 0),
                                total_amount
                            )
                        )
                        ELSE 0
                    END) AS pending_total
                FROM (' . self::ledgerSubquerySql() . ') AS payments
                WHERE ' . implode(' AND ', $ctx['where']);

        $stmt = db()->prepare($sql);
        $stmt->execute($ctx['bind']);
        $row = $stmt->fetch() ?: [];

        return [
            'paid_total' => round((float) ($row['paid_total'] ?? 0), 2),
            'pending_total' => round((float) ($row['pending_total'] ?? 0), 2),
        ];
    }

    /**
     * Aggregated payment stats for reports (avoids loading the full ledger into memory).
     *
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{
     *   transaction_count: int,
     *   paid_total: float,
     *   pending_total: float,
     *   by_type: array<string, array{count: int, total: float, collected: float}>,
     *   by_method: array<string, array{count: int, collected: float}>,
     *   by_status: array<string, int>
     * }
     */
    public static function reportStats(array $filters, ?string $period): array
    {
        $ctx = self::buildLedgerWhere($filters, $period);
        $fromSql = ' FROM (' . self::ledgerSubquerySql() . ') AS payments WHERE ' . implode(' AND ', $ctx['where']);
        $pdo = db();

        $summaryStmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS transaction_count,
                SUM(CASE
                    WHEN payment_status = \'paid\' THEN total_amount
                    WHEN payment_status = \'partial\' THEN LEAST(
                        GREATEST(COALESCE(payment_paid_amount, 0), 0),
                        total_amount
                    )
                    ELSE 0
                END) AS paid_total,
                SUM(CASE
                    WHEN payment_status = \'pending\' THEN total_amount
                    WHEN payment_status = \'partial\' THEN GREATEST(
                        0,
                        total_amount - LEAST(
                            GREATEST(COALESCE(payment_paid_amount, 0), 0),
                            total_amount
                        )
                    )
                    ELSE 0
                END) AS pending_total' . $fromSql
        );
        $summaryStmt->execute($ctx['bind']);
        $summaryRow = $summaryStmt->fetch() ?: [];

        $collectedSql = 'LEAST(GREATEST(COALESCE(payment_paid_amount, 0), 0), total_amount)';

        $byType = [
            self::TYPE_REGISTRATION => ['count' => 0, 'total' => 0.0, 'collected' => 0.0],
            self::TYPE_VISIT => ['count' => 0, 'total' => 0.0, 'collected' => 0.0],
        ];
        $typeStmt = $pdo->prepare(
            "SELECT payment_type,
                    COUNT(*) AS row_count,
                    COALESCE(SUM(total_amount), 0) AS type_total,
                    COALESCE(SUM(CASE
                        WHEN payment_status = 'paid' THEN total_amount
                        WHEN payment_status = 'partial' THEN {$collectedSql}
                        ELSE 0
                    END), 0) AS type_collected" . $fromSql . '
             GROUP BY payment_type'
        );
        $typeStmt->execute($ctx['bind']);
        foreach ($typeStmt->fetchAll() as $row) {
            $type = (string) $row['payment_type'];
            if (!isset($byType[$type])) {
                continue;
            }
            $byType[$type] = [
                'count' => (int) $row['row_count'],
                'total' => round((float) $row['type_total'], 2),
                'collected' => round((float) $row['type_collected'], 2),
            ];
        }

        $byMethod = [];
        $methodStmt = $pdo->prepare(
            "SELECT payment_method,
                    COUNT(*) AS row_count,
                    COALESCE(SUM(CASE
                        WHEN payment_status = 'paid' THEN total_amount
                        WHEN payment_status = 'partial' THEN {$collectedSql}
                        ELSE 0
                    END), 0) AS method_collected" . $fromSql . "
             AND payment_method IS NOT NULL AND payment_method != ''
             GROUP BY payment_method
             ORDER BY method_collected DESC"
        );
        $methodStmt->execute($ctx['bind']);
        foreach ($methodStmt->fetchAll() as $row) {
            $method = (string) $row['payment_method'];
            $byMethod[$method] = [
                'count' => (int) $row['row_count'],
                'collected' => round((float) $row['method_collected'], 2),
            ];
        }

        $byStatus = ['paid' => 0, 'pending' => 0, 'partial' => 0];
        $statusStmt = $pdo->prepare(
            'SELECT payment_status, COUNT(*) AS row_count' . $fromSql . '
             GROUP BY payment_status'
        );
        $statusStmt->execute($ctx['bind']);
        foreach ($statusStmt->fetchAll() as $row) {
            $status = (string) $row['payment_status'];
            if (isset($byStatus[$status])) {
                $byStatus[$status] = (int) $row['row_count'];
            }
        }

        return [
            'transaction_count' => (int) ($summaryRow['transaction_count'] ?? 0),
            'paid_total' => round((float) ($summaryRow['paid_total'] ?? 0), 2),
            'pending_total' => round((float) ($summaryRow['pending_total'] ?? 0), 2),
            'by_type' => $byType,
            'by_method' => $byMethod,
            'by_status' => $byStatus,
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function listForReportDetail(
        array $filters,
        ?string $period,
        int $limit
    ): array {
        return self::fetchRows($filters, 'date', 'desc', $period, max(1, $limit));
    }

    /**
     * @param array{q?: string, status?: string, type?: string, date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function listForPeriodPaginated(
        array $filters = [],
        string $period = self::PERIOD_TODAY,
        string $sort = 'date',
        string $dir = 'desc',
        int $page = 1,
        int $perPage = 25
    ): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $periodKey = self::normalizePeriod($period);
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'payment_date');
        $ctx = self::buildLedgerWhere(
            $filters,
            $periodKey === self::PERIOD_ALL ? self::PERIOD_ALL : $periodKey
        );
        $offset = ($page - 1) * $perPage;

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $ctx['where']);

        $countSql = 'SELECT COUNT(*) AS total FROM (' . self::ledgerSubquerySql() . ') AS payments
                     WHERE ' . $whereSql;
        $countStmt = db()->prepare($countSql);
        foreach ($ctx['bind'] as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $sql = 'SELECT * FROM (' . self::ledgerSubquerySql() . ') AS payments
                WHERE ' . $whereSql . "
                ORDER BY {$orderSql}, payment_type ASC, source_id ASC
                LIMIT :lim OFFSET :off";

        $stmt = db()->prepare($sql);
        foreach ($ctx['bind'] as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = array_map(
            static fn (array $row): array => self::mapRow($row),
            $stmt->fetchAll()
        );
        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array{q?: string, status?: string, type?: string, date_from?: string, date_to?: string} $filters
     * @return list<array<string, mixed>>
     */
    private static function fetchRows(
        array $filters,
        string $sort,
        string $dir,
        ?string $period,
        ?int $limit = null
    ): array {
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'payment_date');
        $ctx = self::buildLedgerWhere($filters, $period);

        $sql = 'SELECT * FROM (' . self::ledgerSubquerySql() . ') AS payments
                WHERE ' . implode(' AND ', $ctx['where']) . "
                ORDER BY {$orderSql}, payment_type ASC, source_id ASC";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($ctx['bind']);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = self::mapRow($row);
        }

        return $rows;
    }

    /**
     * @param array{q?: string, status?: string, type?: string, date_from?: string, date_to?: string} $filters
     * @return array{where: list<string>, bind: array<string, string>}
     */
    private static function buildLedgerWhere(array $filters, ?string $period): array
    {
        $where = ['1=1'];
        $bind = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(patient_name LIKE :q OR patient_code LIKE :q OR phone LIKE :q)';
            $bind['q'] = '%' . $q . '%';
        }

        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        if (in_array($status, PaymentSettings::STATUSES, true)) {
            $where[] = 'payment_status = :status';
            $bind['status'] = $status;
        }

        $type = strtolower(trim((string) ($filters['type'] ?? '')));
        if ($type === self::TYPE_REGISTRATION || $type === self::TYPE_VISIT) {
            $where[] = 'payment_type = :type';
            $bind['type'] = $type;
        }

        self::applyDateFilters($where, $bind, $filters);

        if ($period !== null && $period !== self::PERIOD_ALL) {
            $bounds = self::periodBounds($period);
            if ($bounds !== null) {
                $where[] = 'payment_date >= :period_start AND payment_date < :period_end';
                $bind['period_start'] = $bounds['start'];
                $bind['period_end'] = $bounds['end'];
            }
        }

        return ['where' => $where, 'bind' => $bind];
    }

    private static function ledgerSubquerySql(): string
    {
        return "
            SELECT
                '" . self::TYPE_REGISTRATION . "' AS payment_type,
                p.id AS source_id,
                p.patient_code,
                p.name AS patient_name,
                p.phone,
                p.additional_phone,
                ROUND(p.payment_amount + p.payment_gst_amount, 2) AS total_amount,
                p.payment_amount AS amount_without_gst,
                p.payment_gst_amount AS gst_amount,
                p.payment_paid_amount,
                p.payment_method,
                p.payment_status,
                p.created_at AS payment_date
            FROM patients p
            WHERE p.payment_status IS NOT NULL
              AND (p.payment_amount + p.payment_gst_amount) > 0

            UNION ALL

            SELECT
                '" . self::TYPE_VISIT . "' AS payment_type,
                v.id AS source_id,
                p.patient_code,
                p.name AS patient_name,
                p.phone,
                p.additional_phone,
                v.grand_total AS total_amount,
                ROUND(
                    v.visit_charge + v.medicine_total + v.courier_charge,
                    2
                ) AS amount_without_gst,
                ROUND(v.visit_gst + v.medicine_gst + v.courier_gst, 2) AS gst_amount,
                v.payment_paid_amount,
                v.payment_method,
                v.payment_status,
                v.visited_at AS payment_date
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            WHERE v.payment_status IS NOT NULL
              AND v.grand_total > 0
        ";
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function mapRow(array $row): array
    {
        $total = round((float) $row['total_amount'], 2);
        $paid = self::paidAmount($row);
        $status = (string) $row['payment_status'];

        return [
            'payment_type' => (string) $row['payment_type'],
            'source_id' => (int) $row['source_id'],
            'patient_code' => (string) ($row['patient_code'] ?? ''),
            'patient_name' => (string) $row['patient_name'],
            'phone' => (string) $row['phone'],
            'additional_phone' => (string) ($row['additional_phone'] ?? ''),
            'total_amount' => $total,
            'amount_without_gst' => round((float) ($row['amount_without_gst'] ?? 0), 2),
            'gst_amount' => round((float) ($row['gst_amount'] ?? 0), 2),
            'paid_amount' => $paid,
            'balance_amount' => self::balanceAmount($status, $total, $paid),
            'payment_method' => $row['payment_method'] !== null ? (string) $row['payment_method'] : '',
            'payment_status' => $status,
            'payment_date' => (string) $row['payment_date'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function paidAmount(array $row): float
    {
        $status = (string) $row['payment_status'];
        $total = round((float) $row['total_amount'], 2);

        if ($status === 'paid') {
            return $total;
        }
        if ($status === 'pending') {
            return 0.0;
        }

        $paid = round((float) ($row['payment_paid_amount'] ?? 0), 2);

        return max(0.0, min($paid, $total));
    }

    private static function balanceAmount(string $status, float $total, float $paid): float
    {
        if ($status === 'paid') {
            return 0.0;
        }
        if ($status === 'pending') {
            return $total;
        }

        return max(0.0, round($total - $paid, 2));
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_REGISTRATION => __('payment.type.registration'),
            self::TYPE_VISIT => __('payment.type.visit'),
            default => $type,
        };
    }

    public static function formatDate(string $datetime): string
    {
        $dt = self::parseDateTime($datetime);
        if ($dt === false) {
            return $datetime;
        }

        return $dt->format('d M Y, g:i A');
    }

    public static function formatPaymentTime(string $datetime): string
    {
        $dt = self::parseDateTime($datetime);
        if ($dt === false) {
            return $datetime;
        }

        return $dt->format('g:i A');
    }

    public static function paymentDateKey(string $datetime): string
    {
        $dt = self::parseDateTime($datetime);

        return $dt !== false ? $dt->format('Y-m-d') : $datetime;
    }

    public static function formatDateGroupLabel(string $dateYmd): string
    {
        return Visit::formatDateGroupLabel($dateYmd);
    }

    private static function parseDateTime(string $datetime): DateTimeImmutable|false
    {
        $datetime = trim($datetime);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $datetime);
            if ($dt !== false) {
                return $dt;
            }
        }

        return false;
    }

    public static function patientUrl(string $patientCode): string
    {
        return base_url('/patient_view.php?' . http_build_query(['code' => $patientCode]));
    }

    /**
     * @param list<string> $where
     * @param array<string, string> $bind
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function applyDateFilters(array &$where, array &$bind, array $filters): void
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($dateFrom === '' && $dateTo === '') {
            return;
        }

        if ($dateFrom !== '' && $dateTo === '') {
            $where[] = 'payment_date >= :date_from AND payment_date < :date_from_end';
            $bind['date_from'] = $dateFrom . ' 00:00:00';
            $bind['date_from_end'] = self::nextDayStart($dateFrom);

            return;
        }

        if ($dateFrom !== '') {
            $where[] = 'payment_date >= :date_from';
            $bind['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[] = 'payment_date < :date_to_end';
            $bind['date_to_end'] = self::nextDayStart($dateTo);
        }
    }

    private static function nextDayStart(string $ymd): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);

        return $dt !== false
            ? $dt->modify('+1 day')->format('Y-m-d 00:00:00')
            : $ymd . ' 23:59:59';
    }

    /**
     * @return array{start: string, end: string}
     */
    private static function weekBounds(DateTimeImmutable $now): array
    {
        $dayOfWeek = (int) $now->format('N');
        $start = $now->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
        $end = $start->modify('+7 days');

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
    }
}

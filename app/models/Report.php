<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/PaymentSettings.php';
require_once APP_PATH . '/models/Courier.php';
require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/Patient.php';
require_once APP_PATH . '/models/Visit.php';
require_once APP_PATH . '/models/StockBill.php';

final class Report
{
    public const DETAIL_LIMIT_UI = 500;
    public const DETAIL_LIMIT_CSV = 5000;

    public const TYPE_OVERVIEW = 'overview';
    public const TYPE_PAYMENTS = 'payments';
    public const TYPE_VISITS = 'visits';
    public const TYPE_PATIENTS = 'patients';
    public const TYPE_MEDICINES = 'medicines';
    public const TYPE_COURIER = 'courier';
    public const TYPE_BILLS = 'bills';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_OVERVIEW,
        self::TYPE_PAYMENTS,
        self::TYPE_VISITS,
        self::TYPE_PATIENTS,
        self::TYPE_MEDICINES,
        self::TYPE_COURIER,
        self::TYPE_BILLS,
    ];

    public const PERIOD_TODAY = Payment::PERIOD_TODAY;
    public const PERIOD_WEEK = Payment::PERIOD_WEEK;
    public const PERIOD_MONTH = Payment::PERIOD_MONTH;
    public const PERIOD_YEAR = Payment::PERIOD_YEAR;
    public const PERIOD_ALL = Payment::PERIOD_ALL;

    public static function normalizePeriod(string $period): string
    {
        return Payment::normalizePeriod($period);
    }

    /**
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return Payment::periodOptions();
    }

    public static function periodLabel(string $period): string
    {
        return Payment::periodLabel($period);
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function build(string $type, array $filters, string $period): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : self::TYPE_OVERVIEW;
        $period = self::normalizePeriod($period);

        return match ($type) {
            self::TYPE_PAYMENTS => self::payments($filters, $period),
            self::TYPE_VISITS => self::visits($filters, $period),
            self::TYPE_PATIENTS => self::patients($filters, $period),
            self::TYPE_MEDICINES => self::medicines($filters, $period),
            self::TYPE_COURIER => self::courier($filters, $period),
            self::TYPE_BILLS => self::bills($filters, $period),
            default => self::overview($filters, $period),
        };
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function overview(array $filters, string $period): array
    {
        $payments = self::payments($filters, $period);
        $visits = self::visits($filters, $period);
        $patients = self::patients($filters, $period);
        $courier = self::courier($filters, $period);
        $medicines = self::medicines($filters, $period);
        $bills = self::bills($filters, $period);

        return [
            'type' => self::TYPE_OVERVIEW,
            'period' => $period,
            'payments' => $payments,
            'visits' => $visits,
            'patients' => $patients,
            'courier' => $courier,
            'medicines' => $medicines,
            'bills' => $bills,
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function payments(array $filters, string $period): array
    {
        $paymentFilters = self::paymentFilters($filters);
        $hasCustomDates = ($paymentFilters['date_from'] ?? '') !== ''
            || ($paymentFilters['date_to'] ?? '') !== '';
        $statsPeriod = $hasCustomDates ? null : $period;
        $stats = Payment::reportStats($paymentFilters, $statsPeriod);
        $rows = Payment::listForReportDetail($paymentFilters, $statsPeriod, self::DETAIL_LIMIT_UI);

        return [
            'type' => self::TYPE_PAYMENTS,
            'period' => $period,
            'transaction_count' => $stats['transaction_count'],
            'paid_total' => $stats['paid_total'],
            'pending_total' => $stats['pending_total'],
            'grand_total' => round($stats['paid_total'] + $stats['pending_total'], 2),
            'by_type' => $stats['by_type'],
            'by_method' => $stats['by_method'],
            'by_status' => $stats['by_status'],
            'rows' => $rows,
            'rows_total' => $stats['transaction_count'],
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function visits(array $filters, string $period): array
    {
        $aggregates = self::visitAggregates($filters, $period);
        $visitRows = self::visitRows($filters, $period, self::DETAIL_LIMIT_UI);

        return array_merge($aggregates, [
            'type' => self::TYPE_VISITS,
            'period' => $period,
            'rows' => $visitRows['rows'],
            'rows_total' => $visitRows['total'],
        ]);
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function patients(array $filters, string $period): array
    {
        $date = self::dateClause('p.created_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];

        $stmt = db()->prepare(
            'SELECT
                COUNT(*) AS registration_count,
                SUM(CASE WHEN p.gender = :male THEN 1 ELSE 0 END) AS male_count,
                SUM(CASE WHEN p.gender = :female THEN 1 ELSE 0 END) AS female_count,
                SUM(CASE WHEN p.gender = :other THEN 1 ELSE 0 END) AS other_count,
                SUM(CASE WHEN p.payment_status IS NOT NULL AND (p.payment_amount + p.payment_gst_amount) > 0 THEN 1 ELSE 0 END) AS with_fee_count,
                COALESCE(SUM(
                    CASE
                        WHEN p.payment_status = \'paid\' THEN p.payment_amount + p.payment_gst_amount
                        WHEN p.payment_status = \'partial\' THEN LEAST(
                            GREATEST(COALESCE(p.payment_paid_amount, 0), 0),
                            p.payment_amount + p.payment_gst_amount
                        )
                        ELSE 0
                    END
                ), 0) AS registration_revenue
             FROM patients p
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute(array_merge($bind, [
            'male' => 'male',
            'female' => 'female',
            'other' => 'other',
        ]));
        $row = $stmt->fetch() ?: [];

        $totalStmt = db()->query(
            'SELECT COUNT(*) FROM patients WHERE patient_code IS NOT NULL'
        );

        $patientRows = self::patientRows($filters, $period, self::DETAIL_LIMIT_UI);

        return [
            'type' => self::TYPE_PATIENTS,
            'period' => $period,
            'registration_count' => (int) ($row['registration_count'] ?? 0),
            'total_patients' => (int) $totalStmt->fetchColumn(),
            'male_count' => (int) ($row['male_count'] ?? 0),
            'female_count' => (int) ($row['female_count'] ?? 0),
            'other_count' => (int) ($row['other_count'] ?? 0),
            'with_fee_count' => (int) ($row['with_fee_count'] ?? 0),
            'registration_revenue' => round((float) ($row['registration_revenue'] ?? 0), 2),
            'rows' => $patientRows['rows'],
            'rows_total' => $patientRows['total'],
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function medicines(array $filters, string $period): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];

        $topStmt = db()->prepare(
            'SELECT m.id, m.name,
                    COALESCE(SUM(vm.quantity), 0) AS units_dispensed,
                    COUNT(DISTINCT v.id) AS visit_count
             FROM visit_medicines vm
             INNER JOIN visits v ON v.id = vm.visit_id
             INNER JOIN patients p ON p.id = v.patient_id
             INNER JOIN medicines m ON m.id = vm.medicine_id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY m.id, m.name
             ORDER BY units_dispensed DESC, m.name ASC
             LIMIT 20'
        );
        $topStmt->execute($bind);
        $dispensedUnits = self::medicineAggregates($filters, $period)['dispensed_units'];
        $topDispensed = [];
        foreach ($topStmt->fetchAll() as $row) {
            $topDispensed[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'units_dispensed' => (int) $row['units_dispensed'],
                'visit_count' => (int) $row['visit_count'],
            ];
        }

        $activeCount = (int) db()->query(
            'SELECT COUNT(*) FROM medicines WHERE is_active = 1'
        )->fetchColumn();

        $dispenseRows = self::dispenseRows($filters, $period, self::DETAIL_LIMIT_UI);

        return [
            'type' => self::TYPE_MEDICINES,
            'period' => $period,
            'active_medicines' => $activeCount,
            'top_dispensed' => $topDispensed,
            'dispensed_units' => $dispensedUnits,
            'rows' => $dispenseRows['rows'],
            'rows_total' => $dispenseRows['total'],
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function courier(array $filters, string $period): array
    {
        $aggregates = self::courierAggregates($filters, $period);
        $courierRows = self::courierRows($filters, $period, self::DETAIL_LIMIT_UI);

        return array_merge($aggregates, [
            'type' => self::TYPE_COURIER,
            'period' => $period,
            'rows' => $courierRows['rows'],
            'rows_total' => $courierRows['total'],
        ]);
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    public static function bills(array $filters, string $period): array
    {
        $aggregates = self::billAggregates($filters, $period);
        $billRows = self::billRows($filters, $period, self::DETAIL_LIMIT_UI);
        $bySupplier = self::billBySupplier($filters, $period);

        return array_merge($aggregates, [
            'type' => self::TYPE_BILLS,
            'period' => $period,
            'by_supplier' => $bySupplier,
            'rows' => $billRows['rows'],
            'rows_total' => $billRows['total'],
        ]);
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     */
    public static function sendCsvDownload(string $type, array $filters, string $period): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');

        $type = in_array($type, self::TYPES, true) ? $type : self::TYPE_OVERVIEW;
        $period = self::normalizePeriod($period);
        $filename = 'report-' . $type . '-' . date('Y-m-d-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fwrite($out, "\xEF\xBB\xBF");

        match ($type) {
            self::TYPE_PAYMENTS => self::csvPayments($out, $filters, $period),
            self::TYPE_VISITS => self::csvVisits($out, $filters, $period),
            self::TYPE_PATIENTS => self::csvPatients($out, $filters, $period),
            self::TYPE_MEDICINES => self::csvMedicines($out, $filters, $period),
            self::TYPE_COURIER => self::csvCourier($out, $filters, $period),
            self::TYPE_BILLS => self::csvBills($out, $filters, $period),
            default => self::csvOverview($out, $filters, $period),
        };

        fclose($out);
        exit;
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    private static function visitAggregates(array $filters, string $period): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $deliveryFilter = self::reportDeliveryMethodFilter($filters);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where'], $deliveryFilter['where']);
        $bind = array_merge($date['bind'], $deliveryFilter['bind']);
        $whereSql = implode(' AND ', $where);
        $fromSql = ' FROM visits v INNER JOIN patients p ON p.id = v.patient_id WHERE ' . $whereSql;

        $stmt = db()->prepare(
            'SELECT
                COUNT(*) AS visit_count,
                COALESCE(SUM(v.grand_total), 0) AS grand_total,
                COALESCE(SUM(v.visit_charge + v.visit_gst), 0) AS visit_charges,
                COALESCE(SUM(v.medicine_total + v.medicine_gst), 0) AS medicine_charges,
                COALESCE(SUM(v.courier_charge + v.courier_gst), 0) AS courier_charges,
                SUM(CASE WHEN ' . Visit::remoteDeliveryPackageSql('v') . ' THEN 1 ELSE 0 END) AS courier_visits,
                SUM(CASE WHEN v.payment_status = :paid THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN v.payment_status = :pending THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN v.payment_status = :partial THEN 1 ELSE 0 END) AS partial_count' . $fromSql
        );
        $stmt->execute(array_merge($bind, [
            'paid' => 'paid',
            'pending' => 'pending',
            'partial' => 'partial',
        ]));
        $row = $stmt->fetch() ?: [];

        $dailyStmt = db()->prepare(
            'SELECT DATE(v.visited_at) AS day_key, COUNT(*) AS visit_count,
                    COALESCE(SUM(v.grand_total), 0) AS day_total' . $fromSql . '
             GROUP BY DATE(v.visited_at)
             ORDER BY day_key DESC
             LIMIT 14'
        );
        $dailyStmt->execute($bind);
        $daily = [];
        foreach ($dailyStmt->fetchAll() as $dayRow) {
            $daily[] = [
                'date' => (string) $dayRow['day_key'],
                'label' => Visit::formatDateGroupLabel((string) $dayRow['day_key']),
                'visit_count' => (int) $dayRow['visit_count'],
                'total' => round((float) $dayRow['day_total'], 2),
            ];
        }

        return [
            'visit_count' => (int) ($row['visit_count'] ?? 0),
            'grand_total' => round((float) ($row['grand_total'] ?? 0), 2),
            'visit_charges' => round((float) ($row['visit_charges'] ?? 0), 2),
            'medicine_charges' => round((float) ($row['medicine_charges'] ?? 0), 2),
            'courier_charges' => round((float) ($row['courier_charges'] ?? 0), 2),
            'courier_visits' => (int) ($row['courier_visits'] ?? 0),
            'paid_count' => (int) ($row['paid_count'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'partial_count' => (int) ($row['partial_count'] ?? 0),
            'daily' => $daily,
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    private static function courierAggregates(array $filters, string $period): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $deliveryFilter = self::reportDeliveryMethodFilter($filters, true);
        $where = array_merge([
            'p.patient_code IS NOT NULL',
            Visit::remoteDeliveryPackageSql('v'),
        ], $date['where'], $deliveryFilter['where']);
        $bind = array_merge($date['bind'], $deliveryFilter['bind']);

        $stmt = db()->prepare(
            'SELECT
                COUNT(*) AS package_count,
                SUM(CASE WHEN v.courier_status = :pending OR v.courier_status IS NULL THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN v.courier_status = :sent THEN 1 ELSE 0 END) AS sent_count,
                SUM(CASE WHEN v.courier_status = :canceled THEN 1 ELSE 0 END) AS canceled_count,
                COALESCE(SUM(v.courier_charge + v.courier_gst), 0) AS courier_revenue
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute(array_merge($bind, [
            'pending' => Courier::STATUS_PENDING,
            'sent' => Courier::STATUS_SENT,
            'canceled' => Courier::STATUS_CANCELED,
        ]));
        $row = $stmt->fetch() ?: [];

        return [
            'package_count' => (int) ($row['package_count'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'sent_count' => (int) ($row['sent_count'] ?? 0),
            'canceled_count' => (int) ($row['canceled_count'] ?? 0),
            'courier_revenue' => round((float) ($row['courier_revenue'] ?? 0), 2),
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array<string, mixed>
     */
    private static function billAggregates(array $filters, string $period): array
    {
        $date = self::dateClause('sb.bill_date', $filters, $period);
        $where = $date['where'];
        $bind = $date['bind'];

        $stmt = db()->prepare(
            'SELECT
                COUNT(*) AS bill_count,
                COALESCE(SUM(sb.amount), 0) AS total_amount,
                COUNT(DISTINCT sb.supplier) AS supplier_count
             FROM stock_bills sb
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($bind);
        $row = $stmt->fetch() ?: [];

        $billCount = (int) ($row['bill_count'] ?? 0);
        $totalAmount = round((float) ($row['total_amount'] ?? 0), 2);

        return [
            'bill_count' => $billCount,
            'total_amount' => $totalAmount,
            'supplier_count' => (int) ($row['supplier_count'] ?? 0),
            'avg_amount' => $billCount > 0 ? round($totalAmount / $billCount, 2) : 0.0,
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return list<array{supplier: string, bill_count: int, total_amount: float}>
     */
    private static function billBySupplier(array $filters, string $period): array
    {
        $date = self::dateClause('sb.bill_date', $filters, $period);
        $where = $date['where'];
        $bind = $date['bind'];

        $stmt = db()->prepare(
            'SELECT sb.supplier,
                    COUNT(*) AS bill_count,
                    COALESCE(SUM(sb.amount), 0) AS total_amount
             FROM stock_bills sb
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY sb.supplier
             ORDER BY total_amount DESC, bill_count DESC, sb.supplier ASC
             LIMIT 20'
        );
        $stmt->execute($bind);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'supplier' => (string) ($row['supplier'] ?? ''),
                'bill_count' => (int) ($row['bill_count'] ?? 0),
                'total_amount' => round((float) ($row['total_amount'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private static function billRows(array $filters, string $period, int $limit): array
    {
        $date = self::dateClause('sb.bill_date', $filters, $period);
        $where = $date['where'];
        $bind = $date['bind'];
        $lim = max(1, min($limit, self::DETAIL_LIMIT_CSV));
        $whereSql = implode(' AND ', $where);

        // Total count without window functions.
        $countStmt = db()->prepare('SELECT COUNT(*) AS total FROM stock_bills sb WHERE ' . $whereSql);
        $countStmt->execute($bind);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = db()->prepare(
            'SELECT sb.bill_number, sb.register_number, sb.supplier, sb.bill_date, sb.delivery_date, sb.amount,
                    u.name AS submitted_by_name,
                    (
                        SELECT GROUP_CONCAT(sbi.item_name ORDER BY sbi.sort_order SEPARATOR \', \')
                        FROM stock_bill_items sbi
                        WHERE sbi.bill_id = sb.id
                    ) AS items_summary
             FROM stock_bills sb
             INNER JOIN users u ON u.id = sb.submitted_by
             WHERE ' . $whereSql . '
             ORDER BY sb.bill_date DESC, sb.id DESC
             LIMIT ' . $lim
        );
        $stmt->execute($bind);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'bill_number' => (string) $row['bill_number'],
                'register_number' => (string) ($row['register_number'] ?? ''),
                'supplier' => (string) ($row['supplier'] ?? ''),
                'bill_date' => (string) $row['bill_date'],
                'delivery_date' => $row['delivery_date'] !== null ? (string) $row['delivery_date'] : null,
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'submitted_by_name' => (string) ($row['submitted_by_name'] ?? ''),
                'items_summary' => trim((string) ($row['items_summary'] ?? '')),
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvBills($out, array $filters, string $period): void
    {
        csv_put_row($out, [
            'Bill number',
            'Register number',
            'Supplier',
            'Purchase date',
            'Delivery date',
            'Total amount',
            'Item names',
            'Submitted by',
        ]);

        $rows = self::billRows($filters, $period, self::DETAIL_LIMIT_CSV)['rows'];
        foreach ($rows as $row) {
            csv_put_row($out, [
                (string) ($row['bill_number'] ?? ''),
                (string) ($row['register_number'] ?? ''),
                (string) ($row['supplier'] ?? ''),
                (string) ($row['bill_date'] ?? ''),
                (string) ($row['delivery_date'] ?? ''),
                (string) ($row['amount'] ?? ''),
                (string) ($row['items_summary'] ?? ''),
                (string) ($row['submitted_by_name'] ?? ''),
            ]);
        }
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private static function visitRows(array $filters, string $period, int $limit): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $deliveryFilter = self::reportDeliveryMethodFilter($filters);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where'], $deliveryFilter['where']);
        $bind = array_merge($date['bind'], $deliveryFilter['bind']);
        $lim = max(1, min($limit, self::DETAIL_LIMIT_CSV));

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare(
            'SELECT COUNT(*) AS total
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . $whereSql
        );
        $countStmt->execute($bind);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = db()->prepare(
            'SELECT v.id, v.visited_at, v.notes, v.delivery_method, p.patient_code, p.name AS patient_name, p.phone,
                    p.age, p.gender,
                    v.grand_total, v.visit_charge, v.visit_gst,
                    v.medicine_total, v.medicine_gst,
                    v.courier_charge, v.courier_gst,
                    v.payment_method, v.payment_status, v.payment_paid_amount
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . $whereSql . '
             ORDER BY v.visited_at DESC, v.id DESC
             LIMIT ' . $lim
        );
        $stmt->execute($bind);

        $fetched = $stmt->fetchAll();
        $visitIds = array_map(static fn (array $row): int => (int) $row['id'], $fetched);
        $linesByVisit = Visit::medicineLinesForVisits($visitIds);

        $rows = [];
        foreach ($fetched as $row) {
            $visitId = (int) $row['id'];
            $grandTotal = round((float) $row['grand_total'], 2);
            $paid = Visit::paymentPaidAmount($row);
            $deliveryFields = self::deliveryMethodRowFields($row);
            $rows[] = [
                'visited_at' => (string) $row['visited_at'],
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) $row['patient_name'],
                'phone' => (string) $row['phone'],
                'age' => (int) $row['age'],
                'gender' => (string) $row['gender'],
                'delivery_method' => $deliveryFields['delivery_method'],
                'delivery_method_label' => $deliveryFields['delivery_method_label'],
                'grand_total' => $grandTotal,
                'visit_charges' => round((float) $row['visit_charge'] + (float) $row['visit_gst'], 2),
                'medicine_charges' => round((float) $row['medicine_total'] + (float) $row['medicine_gst'], 2),
                'courier_charges' => round((float) $row['courier_charge'] + (float) $row['courier_gst'], 2),
                'paid_amount' => $paid,
                'balance_amount' => Visit::paymentBalance($row),
                'payment_method' => (string) ($row['payment_method'] ?? ''),
                'payment_status' => (string) ($row['payment_status'] ?? ''),
                'medicines_summary' => Visit::formatMedicineSummary($linesByVisit[$visitId] ?? []),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private static function patientRows(array $filters, string $period, int $limit): array
    {
        $date = self::dateClause('p.created_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];
        $lim = max(1, min($limit, self::DETAIL_LIMIT_CSV));

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare('SELECT COUNT(*) AS total FROM patients p WHERE ' . $whereSql);
        $countStmt->execute($bind);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = db()->prepare(
            'SELECT p.patient_code, p.name, p.phone, p.age, p.gender, p.created_at,
                    p.payment_amount, p.payment_gst_amount,
                    p.payment_method, p.payment_status, p.payment_paid_amount
             FROM patients p
             WHERE ' . $whereSql . '
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ' . $lim
        );
        $stmt->execute($bind);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $totalFee = round((float) $row['payment_amount'] + (float) $row['payment_gst_amount'], 2);
            $rows[] = [
                'created_at' => (string) $row['created_at'],
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'name' => (string) $row['name'],
                'phone' => (string) $row['phone'],
                'age' => (int) $row['age'],
                'gender' => (string) $row['gender'],
                'registration_fee' => $totalFee,
                'payment_method' => (string) ($row['payment_method'] ?? ''),
                'payment_status' => (string) ($row['payment_status'] ?? ''),
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private static function dispenseRows(array $filters, string $period, int $limit): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];
        $lim = max(1, min($limit, self::DETAIL_LIMIT_CSV));

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare(
            'SELECT COUNT(*) AS total
             FROM visit_medicines vm
             INNER JOIN visits v ON v.id = vm.visit_id
             INNER JOIN patients p ON p.id = v.patient_id
             INNER JOIN medicines m ON m.id = vm.medicine_id
             WHERE ' . $whereSql
        );
        $countStmt->execute($bind);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = db()->prepare(
            'SELECT v.visited_at, p.patient_code, p.name AS patient_name,
                    m.name AS medicine_name, vm.quantity
             FROM visit_medicines vm
             INNER JOIN visits v ON v.id = vm.visit_id
             INNER JOIN patients p ON p.id = v.patient_id
             INNER JOIN medicines m ON m.id = vm.medicine_id
             WHERE ' . $whereSql . '
             ORDER BY v.visited_at DESC, v.id DESC, m.name ASC
             LIMIT ' . $lim
        );
        $stmt->execute($bind);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'visited_at' => (string) $row['visited_at'],
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) $row['patient_name'],
                'medicine_name' => (string) $row['medicine_name'],
                'quantity' => (int) $row['quantity'],
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private static function courierRows(array $filters, string $period, int $limit): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $deliveryFilter = self::reportDeliveryMethodFilter($filters, true);
        $where = array_merge([
            'p.patient_code IS NOT NULL',
            Visit::remoteDeliveryPackageSql('v'),
        ], $date['where'], $deliveryFilter['where']);
        $bind = array_merge($date['bind'], $deliveryFilter['bind']);
        $lim = max(1, min($limit, self::DETAIL_LIMIT_CSV));

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare(
            'SELECT COUNT(*) AS total
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . $whereSql
        );
        $countStmt->execute($bind);
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = db()->prepare(
            'SELECT v.id, v.visited_at, v.delivery_method, v.courier_status, v.courier_dispatched_at,
                    v.courier_charge, v.courier_gst,
                    p.patient_code, p.name AS patient_name, p.phone,
                    p.delivery_address
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . $whereSql . '
             ORDER BY v.visited_at DESC, v.id DESC
             LIMIT ' . $lim
        );
        $stmt->execute($bind);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $deliveryFields = self::deliveryMethodRowFields($row, Visit::DELIVERY_COURIER);
            $rows[] = [
                'visited_at' => (string) $row['visited_at'],
                'patient_code' => (string) ($row['patient_code'] ?? ''),
                'patient_name' => (string) $row['patient_name'],
                'phone' => (string) $row['phone'],
                'delivery_address' => (string) ($row['delivery_address'] ?? ''),
                'delivery_method' => $deliveryFields['delivery_method'],
                'delivery_method_label' => $deliveryFields['delivery_method_label'],
                'courier_status' => (string) ($row['courier_status'] ?? ''),
                'courier_dispatched_at' => (string) ($row['courier_dispatched_at'] ?? ''),
                'courier_charge' => round((float) $row['courier_charge'] + (float) $row['courier_gst'], 2),
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvPayments($out, array $filters, string $period): void
    {
        $paymentFilters = self::paymentFilters($filters);
        $hasCustomDates = ($paymentFilters['date_from'] ?? '') !== ''
            || ($paymentFilters['date_to'] ?? '') !== '';
        $statsPeriod = $hasCustomDates ? null : $period;
        $rows = Payment::listForReportDetail($paymentFilters, $statsPeriod, self::DETAIL_LIMIT_CSV);

        csv_put_row($out, [
            __('report.col.datetime'),
            __('patient.field.id'),
            __('patient.field.name'),
            __('patient.field.phone'),
            __('payment.field.type'),
            __('payment.field.total'),
            __('payment.field.without_gst_col'),
            __('payment.field.gst_col'),
            __('payment.field.paid'),
            __('payment.field.balance'),
            __('payment.field.method'),
            __('payment.field.status'),
        ]);

        foreach ($rows as $row) {
            csv_put_row($out, [
                Payment::formatDate((string) $row['payment_date']),
                $row['patient_code'],
                $row['patient_name'],
                phone_format_display((string) $row['phone']),
                Payment::typeLabel((string) $row['payment_type']),
                $row['total_amount'],
                $row['amount_without_gst'],
                $row['gst_amount'],
                $row['paid_amount'],
                $row['balance_amount'],
                ($row['payment_method'] ?? '') !== ''
                    ? PaymentSettings::methodLabel((string) $row['payment_method'])
                    : '',
                PaymentSettings::statusLabel((string) $row['payment_status']),
            ]);
        }
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvVisits($out, array $filters, string $period): void
    {
        $data = self::visitRows($filters, $period, self::DETAIL_LIMIT_CSV);

        csv_put_row($out, [
            __('report.col.datetime'),
            __('patient.field.id'),
            __('patient.field.name'),
            __('patient.field.phone'),
            __('patient.field.age'),
            __('patient.field.gender'),
            __('visit.form.delivery_method'),
            __('report.col.total'),
            __('report.metric.visit_charges'),
            __('report.metric.medicine_charges'),
            __('report.metric.courier_charges'),
            __('payment.field.paid'),
            __('payment.field.balance'),
            __('payment.field.method'),
            __('payment.field.status'),
            __('visit.field.medicines'),
            __('visit.field.notes'),
        ]);

        foreach ($data['rows'] as $row) {
            csv_put_row($out, [
                Visit::formatVisitedAt((string) $row['visited_at']),
                $row['patient_code'],
                $row['patient_name'],
                phone_format_display((string) $row['phone']),
                $row['age'],
                Patient::genderLabel($row['gender']),
                $row['delivery_method_label'],
                $row['grand_total'],
                $row['visit_charges'],
                $row['medicine_charges'],
                $row['courier_charges'],
                $row['paid_amount'],
                $row['balance_amount'],
                $row['payment_method'] !== ''
                    ? PaymentSettings::methodLabel($row['payment_method'])
                    : '',
                $row['payment_status'] !== ''
                    ? PaymentSettings::statusLabel($row['payment_status'])
                    : '',
                $row['medicines_summary'],
                $row['notes'],
            ]);
        }
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvPatients($out, array $filters, string $period): void
    {
        $data = self::patientRows($filters, $period, self::DETAIL_LIMIT_CSV);

        csv_put_row($out, [
            __('report.col.datetime'),
            __('patient.field.id'),
            __('patient.field.name'),
            __('patient.field.phone'),
            __('patient.field.age'),
            __('patient.field.gender'),
            __('report.col.registration_fee'),
            __('payment.field.method'),
            __('payment.field.status'),
        ]);

        foreach ($data['rows'] as $row) {
            csv_put_row($out, [
                Patient::formatRegisteredAt((string) $row['created_at']),
                $row['patient_code'],
                $row['name'],
                phone_format_display((string) $row['phone']),
                $row['age'],
                Patient::genderLabel($row['gender']),
                $row['registration_fee'],
                $row['payment_method'] !== ''
                    ? PaymentSettings::methodLabel($row['payment_method'])
                    : '',
                $row['payment_status'] !== ''
                    ? PaymentSettings::statusLabel($row['payment_status'])
                    : '',
            ]);
        }
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvMedicines($out, array $filters, string $period): void
    {
        $data = self::dispenseRows($filters, $period, self::DETAIL_LIMIT_CSV);

        csv_put_row($out, [
            __('report.col.datetime'),
            __('patient.field.id'),
            __('report.col.patient_name'),
            __('report.col.medicine_name'),
            __('report.col.quantity'),
        ]);

        foreach ($data['rows'] as $row) {
            csv_put_row($out, [
                Visit::formatVisitedAt((string) $row['visited_at']),
                $row['patient_code'],
                $row['patient_name'],
                $row['medicine_name'],
                $row['quantity'],
            ]);
        }
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvCourier($out, array $filters, string $period): void
    {
        $data = self::courierRows($filters, $period, self::DETAIL_LIMIT_CSV);

        csv_put_row($out, [
            __('report.col.datetime'),
            __('patient.field.id'),
            __('patient.field.name'),
            __('patient.field.phone'),
            __('patient.field.delivery_address'),
            __('visit.form.delivery_method'),
            __('courier.field.status'),
            __('courier.field.dispatched_at'),
            __('report.metric.courier_charges'),
        ]);

        foreach ($data['rows'] as $row) {
            csv_put_row($out, [
                Visit::formatVisitedAt((string) $row['visited_at']),
                $row['patient_code'],
                $row['patient_name'],
                phone_format_display((string) $row['phone']),
                $row['delivery_address'],
                $row['delivery_method_label'],
                Courier::statusLabel($row['courier_status']),
                $row['courier_dispatched_at'] !== ''
                    ? Visit::formatVisitedAt((string) $row['courier_dispatched_at'])
                    : '',
                $row['courier_charge'],
            ]);
        }
    }

    /**
     * @param resource $out
     * @param array{date_from?: string, date_to?: string} $filters
     */
    private static function csvOverview($out, array $filters, string $period): void
    {
        $paymentFilters = self::paymentFilters($filters);
        $hasCustomDates = ($paymentFilters['date_from'] ?? '') !== ''
            || ($paymentFilters['date_to'] ?? '') !== '';
        $statsPeriod = $hasCustomDates ? null : $period;
        $payStats = Payment::reportStats($paymentFilters, $statsPeriod);
        $visitAgg = self::visitAggregates($filters, $period);
        $patientAgg = self::patientAggregates($filters, $period);
        $courierAgg = self::courierAggregates($filters, $period);
        $medicineAgg = self::medicineAggregates($filters, $period);

        csv_put_row($out, [__('report.overview.summary')]);
        csv_put_row($out, [__('report.metric.label'), __('report.metric.value')]);
        csv_put_row($out, [__('report.metric.collected'), $payStats['paid_total']]);
        csv_put_row($out, [__('report.metric.outstanding'), $payStats['pending_total']]);
        csv_put_row($out, [__('report.metric.visits'), $visitAgg['visit_count']]);
        csv_put_row($out, [__('report.metric.registrations'), $patientAgg['registration_count']]);
        csv_put_row($out, [__('report.metric.visit_revenue'), $visitAgg['grand_total']]);
        csv_put_row($out, [__('report.metric.courier_packages'), $courierAgg['package_count']]);
        csv_put_row($out, [__('report.metric.medicines_dispensed'), $medicineAgg['dispensed_units']]);
        csv_put_row($out, []);

        csv_put_row($out, [__('report.payments.detail')]);
        self::csvPayments($out, $filters, $period);
        csv_put_row($out, []);
        csv_put_row($out, [__('report.visits.detail')]);
        self::csvVisits($out, $filters, $period);
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{registration_count: int, dispensed_units: int}
     */
    private static function patientAggregates(array $filters, string $period): array
    {
        $date = self::dateClause('p.created_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];

        $stmt = db()->prepare(
            'SELECT COUNT(*) AS registration_count
             FROM patients p
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($bind);
        $row = $stmt->fetch() ?: [];

        return [
            'registration_count' => (int) ($row['registration_count'] ?? 0),
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{dispensed_units: int}
     */
    private static function medicineAggregates(array $filters, string $period): array
    {
        $date = self::dateClause('v.visited_at', $filters, $period);
        $where = array_merge(['p.patient_code IS NOT NULL'], $date['where']);
        $bind = $date['bind'];

        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(vm.quantity), 0) AS dispensed_units
             FROM visit_medicines vm
             INNER JOIN visits v ON v.id = vm.visit_id
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($bind);

        return [
            'dispensed_units' => (int) $stmt->fetchColumn(),
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{date_from: string, date_to: string}
     */
    private static function paymentFilters(array $filters): array
    {
        return [
            'date_from' => (string) ($filters['date_from'] ?? ''),
            'date_to' => (string) ($filters['date_to'] ?? ''),
        ];
    }

    /**
     * @param array{date_from?: string, date_to?: string} $filters
     * @return array{where: list<string>, bind: array<string, string>}
     */
    private static function dateClause(string $column, array $filters, string $period): array
    {
        $where = [];
        $bind = [];
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($dateFrom !== '' || $dateTo !== '') {
            if ($dateFrom !== '' && $dateTo === '') {
                $where[] = "{$column} >= :date_from AND {$column} < :date_from_end";
                $bind['date_from'] = $dateFrom . ' 00:00:00';
                $bind['date_from_end'] = self::nextDayStart($dateFrom);
            } else {
                if ($dateFrom !== '') {
                    $where[] = "{$column} >= :date_from";
                    $bind['date_from'] = $dateFrom . ' 00:00:00';
                }
                if ($dateTo !== '') {
                    $where[] = "{$column} < :date_to_end";
                    $bind['date_to_end'] = self::nextDayStart($dateTo);
                }
            }

            return ['where' => $where, 'bind' => $bind];
        }

        $period = self::normalizePeriod($period);
        if ($period === self::PERIOD_ALL) {
            return ['where' => $where, 'bind' => $bind];
        }

        $bounds = Payment::periodBounds($period);
        if ($bounds !== null) {
            $where[] = "{$column} >= :period_start AND {$column} < :period_end";
            $bind['period_start'] = $bounds['start'];
            $bind['period_end'] = $bounds['end'];
        }

        return ['where' => $where, 'bind' => $bind];
    }

    /**
     * @param array{delivery_method?: string} $filters
     * @return array{where: list<string>, bind: array<string, string>}
     */
    private static function reportDeliveryMethodFilter(array $filters, bool $remoteOnly = false): array
    {
        $method = Visit::normalizeDeliveryMethodFilter((string) ($filters['delivery_method'] ?? ''), $remoteOnly);
        if ($method === '') {
            return ['where' => [], 'bind' => []];
        }

        return [
            'where' => ['v.delivery_method = :delivery_method'],
            'bind' => ['delivery_method' => $method],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{delivery_method: string, delivery_method_label: string}
     */
    private static function deliveryMethodRowFields(array $row, string $default = Visit::DELIVERY_SELF): array
    {
        $deliveryMethod = (string) ($row['delivery_method'] ?? $default);

        return [
            'delivery_method' => $deliveryMethod,
            'delivery_method_label' => Visit::deliveryMethodLabel($deliveryMethod),
        ];
    }

    private static function nextDayStart(string $ymd): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);

        return $dt !== false
            ? $dt->modify('+1 day')->format('Y-m-d 00:00:00')
            : $ymd . ' 23:59:59';
    }
}

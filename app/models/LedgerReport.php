<?php

declare(strict_types=1);

require_once APP_PATH . '/models/PaymentSettings.php';

final class LedgerReport
{
    public static function rows(array $filters): array
    {
        $rows = array_merge(
            self::registrationRows($filters),
            self::visitRows($filters)
        );

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at'])
        );

        return $rows;
    }

    public static function totals(array $rows): array
    {
        $totals = [
            'registration_bill' => 0.0,
            'registration_gst' => 0.0,
            'visit_bill' => 0.0,
            'visit_gst' => 0.0,
            'pharmacy_bill' => 0.0,
            'medicine_gst' => 0.0,
            'total_gst' => 0.0,
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'pending_amount' => 0.0,
        ];

        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                $totals[$key] += (float) ($row[$key] ?? 0);
            }
        }

        return array_map(static fn($value) => round((float) $value, 2), $totals);
    }

    public static function money(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'registration' => __('report.ledger.type.registration'),
            'visit_pharmacy' => __('report.ledger.type.visit_pharmacy'),
            default => $type,
        };
    }

    public static function methodLabel(?string $method): string
    {
        if ($method === null || $method === '') {
            return '—';
        }

        return PaymentSettings::methodLabel($method);
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        return PaymentSettings::statusLabel($status);
    }

    public static function downloadCsv(array $rows): never
    {
        $filename = 'gur_asees_ledger_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Ledger ID',
            'Patient ID',
            'Patient Name',
            'Phone',
            'Type',
            'Registration Bill',
            'Registration GST',
            'Visit Bill',
            'Visit GST',
            'Pharmacy Bill',
            'Medicine GST',
            'Total GST',
            'Total Amount',
            'Paid Amount',
            'Pending Amount',
            'Payment Mode',
            'Due Date',
            'Status',
            'Remarks',
            'Created At',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['ledger_id'],
                $row['patient_code'],
                $row['patient_name'],
                $row['phone'],
                self::typeLabel((string) $row['type']),
                self::money($row['registration_bill']),
                self::money($row['registration_gst']),
                self::money($row['visit_bill']),
                self::money($row['visit_gst']),
                self::money($row['pharmacy_bill']),
                self::money($row['medicine_gst']),
                self::money($row['total_gst']),
                self::money($row['total_amount']),
                self::money($row['paid_amount']),
                self::money($row['pending_amount']),
                self::methodLabel($row['payment_method'] ?? null),
                $row['due_date'],
                self::statusLabel($row['status'] ?? null),
                $row['remarks'],
                $row['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    private static function registrationRows(array $filters): array
    {
        $where = self::whereFor('p', 'p.created_at', $filters);
        $where['sql'][] = '(p.payment_amount > 0 OR p.payment_gst_amount > 0 OR p.payment_paid_amount IS NOT NULL)';

        $stmt = db()->prepare(
            'SELECT p.patient_code, p.name AS patient_name, p.phone,
                    p.payment_amount, p.payment_gst_amount, p.payment_method,
                    p.payment_status, p.payment_paid_amount, p.created_at
             FROM patients p
             WHERE ' . implode(' AND ', $where['sql']) . '
             ORDER BY p.created_at DESC'
        );

        $stmt->execute($where['bind']);

        $rows = [];

        foreach ($stmt->fetchAll() as $row) {
            $registrationBill = (float) ($row['payment_amount'] ?? 0);
            $registrationGst = (float) ($row['payment_gst_amount'] ?? 0);
            $totalAmount = round($registrationBill + $registrationGst, 2);
            $paidAmount = (float) ($row['payment_paid_amount'] ?? 0);
            $pendingAmount = max(0.0, round($totalAmount - $paidAmount, 2));

            $status = (string) ($row['payment_status'] ?? '');

            if ($status === '') {
                $status = $pendingAmount <= 0 && $totalAmount > 0 ? 'paid' : 'pending';
            }

            $createdAt = (string) $row['created_at'];

            $rows[] = [
                'ledger_id' => 'REG-' . (string) $row['patient_code'],
                'patient_code' => (string) $row['patient_code'],
                'patient_name' => (string) $row['patient_name'],
                'phone' => (string) $row['phone'],
                'type' => 'registration',
                'registration_bill' => $registrationBill,
                'registration_gst' => $registrationGst,
                'visit_bill' => 0.0,
                'visit_gst' => 0.0,
                'pharmacy_bill' => 0.0,
                'medicine_gst' => 0.0,
                'total_gst' => $registrationGst,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'payment_method' => $row['payment_method'] ?? null,
                'due_date' => substr($createdAt, 0, 10),
                'status' => $status,
                'remarks' => '',
                'created_at' => $createdAt,
            ];
        }

        return $rows;
    }

    private static function visitRows(array $filters): array
    {
        $where = self::whereFor('p', 'v.visited_at', $filters);

        $stmt = db()->prepare(
            'SELECT v.id, v.visited_at, v.visit_charge, v.visit_gst,
                    v.medicine_total, v.medicine_gst, v.grand_total, v.notes, v.created_at,
                    p.patient_code, p.name AS patient_name, p.phone
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE ' . implode(' AND ', $where['sql']) . '
             ORDER BY v.visited_at DESC'
        );

        $stmt->execute($where['bind']);

        $rows = [];

        foreach ($stmt->fetchAll() as $row) {
            $visitBill = (float) ($row['visit_charge'] ?? 0);
            $visitGst = (float) ($row['visit_gst'] ?? 0);
            $pharmacyBill = (float) ($row['medicine_total'] ?? 0);
            $medicineGst = (float) ($row['medicine_gst'] ?? 0);
            $totalGst = round($visitGst + $medicineGst, 2);
            $totalAmount = (float) ($row['grand_total'] ?? 0);

            $rows[] = [
                'ledger_id' => 'VIS-' . str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT),
                'patient_code' => (string) $row['patient_code'],
                'patient_name' => (string) $row['patient_name'],
                'phone' => (string) $row['phone'],
                'type' => 'visit_pharmacy',
                'registration_bill' => 0.0,
                'registration_gst' => 0.0,
                'visit_bill' => $visitBill,
                'visit_gst' => $visitGst,
                'pharmacy_bill' => $pharmacyBill,
                'medicine_gst' => $medicineGst,
                'total_gst' => $totalGst,
                'total_amount' => $totalAmount,
                'paid_amount' => $totalAmount,
                'pending_amount' => 0.0,
                'payment_method' => null,
                'due_date' => substr((string) $row['visited_at'], 0, 10),
                'status' => 'paid',
                'remarks' => (string) ($row['notes'] ?? ''),
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $rows;
    }

    private static function whereFor(string $patientAlias, string $dateColumn, array $filters): array
    {
        $sql = [$patientAlias . '.patient_code IS NOT NULL'];
        $bind = [];

        $q = trim((string) ($filters['q'] ?? ''));

        if ($q !== '') {
            $sql[] = "({$patientAlias}.patient_code LIKE :q_code OR {$patientAlias}.name LIKE :q_name OR {$patientAlias}.phone LIKE :q_phone)";
            $bind['q_code'] = '%' . $q . '%';
            $bind['q_name'] = '%' . $q . '%';
            $bind['q_phone'] = '%' . $q . '%';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));

        if ($dateFrom !== '') {
            $sql[] = $dateColumn . ' >= :date_from';
            $bind['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($dateTo !== '') {
            $sql[] = $dateColumn . ' <= :date_to';
            $bind['date_to'] = $dateTo . ' 23:59:59';
        }

        return [
            'sql' => $sql,
            'bind' => $bind,
        ];
    }
}
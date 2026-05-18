<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/GstSettings.php';
require_once APP_PATH . '/models/VisitSettings.php';

final class Visit
{
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

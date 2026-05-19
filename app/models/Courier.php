<?php

declare(strict_types=1);

final class Courier
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listPending(): array
    {
        $stmt = db()->query(
            'SELECT v.id AS visit_id, v.visited_at, v.courier_dispatched_at,
                    p.patient_code, p.name AS patient_name, p.phone,
                    p.address, p.delivery_address
             FROM visits v
             INNER JOIN patients p ON p.id = v.patient_id
             WHERE p.patient_code IS NOT NULL
               AND v.courier_dispatched_at IS NULL
               AND EXISTS (
                   SELECT 1 FROM visit_medicines vm
                   WHERE vm.visit_id = v.id AND vm.courier_quantity > 0
               )
             ORDER BY v.visited_at ASC, v.id ASC'
        );

        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $visitIds = array_map(static fn (array $row): int => (int) $row['visit_id'], $rows);
        $linesByVisit = self::courierLinesForVisits($visitIds);

        foreach ($rows as &$row) {
            $id = (int) $row['visit_id'];
            $row['courier_lines'] = $linesByVisit[$id] ?? [];
            $row['delivery_display'] = self::formatDeliveryAddress($row);
        }
        unset($row);

        return $rows;
    }

    public static function dispatch(int $visitId, int $userId): bool
    {
        if ($visitId < 1 || $userId < 1) {
            return false;
        }

        $check = db()->prepare(
            'SELECT v.id
             FROM visits v
             WHERE v.id = :id
               AND v.courier_dispatched_at IS NULL
               AND EXISTS (
                   SELECT 1 FROM visit_medicines vm
                   WHERE vm.visit_id = v.id AND vm.courier_quantity > 0
               )'
        );
        $check->execute(['id' => $visitId]);
        if ($check->fetch() === false) {
            return false;
        }

        $stmt = db()->prepare(
            'UPDATE visits
             SET courier_dispatched_at = NOW(), courier_dispatched_by = :uid
             WHERE id = :id AND courier_dispatched_at IS NULL'
        );
        $stmt->execute(['id' => $visitId, 'uid' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param list<int> $visitIds
     * @return array<int, list<array{name: string, quantity: int}>>
     */
    private static function courierLinesForVisits(array $visitIds): array
    {
        $visitIds = array_values(array_unique(array_filter(
            array_map('intval', $visitIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($visitIds === []) {
            return [];
        }

        $map = [];
        $sql = 'SELECT vm.visit_id, m.name, vm.courier_quantity
                FROM visit_medicines vm
                INNER JOIN medicines m ON m.id = vm.medicine_id
                WHERE vm.visit_id IN (%s) AND vm.courier_quantity > 0
                ORDER BY vm.visit_id ASC, m.name ASC';

        foreach (array_chunk($visitIds, 250) as $chunk) {
            $stmt = db()->prepare(sprintf($sql, db_sql_in_placeholders(count($chunk))));
            $stmt->execute($chunk);

            foreach ($stmt->fetchAll() as $row) {
                $vid = (int) $row['visit_id'];
                $map[$vid] ??= [];
                $map[$vid][] = [
                    'name' => (string) $row['name'],
                    'quantity' => (int) $row['courier_quantity'],
                ];
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function formatDeliveryAddress(array $row): string
    {
        $delivery = trim((string) ($row['delivery_address'] ?? ''));
        if ($delivery !== '') {
            return $delivery;
        }

        return trim((string) ($row['address'] ?? ''));
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
}

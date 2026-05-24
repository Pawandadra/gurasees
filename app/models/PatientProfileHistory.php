<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Patient.php';

final class PatientProfileHistory
{
    /**
     * @param array<string, mixed> $patient Row from patients table.
     * @return array<string, mixed>
     */
    public static function buildSnapshot(array $patient, int $patientId): array
    {
        return [
            'name' => (string) $patient['name'],
            'age' => (int) $patient['age'],
            'gender' => (string) $patient['gender'],
            'phone' => (string) $patient['phone'],
            'address' => (string) $patient['address'],
            'delivery_address' => $patient['delivery_address'] ?? null,
            'remarks' => $patient['remarks'] ?? null,
            'symptoms' => self::symptomsForPatient($patientId),
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public static function symptomsForPatient(int $patientId): array
    {
        if ($patientId < 1) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT s.id, s.label
             FROM patient_symptoms ps
             INNER JOIN symptoms s ON s.id = ps.symptom_id
             WHERE ps.patient_id = :pid
             ORDER BY s.sort_order ASC, s.id ASC'
        );
        $stmt->execute(['pid' => $patientId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function record(int $patientId, ?int $editedBy, array $snapshot): void
    {
        if ($patientId < 1) {
            return;
        }

        $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $stmt = db()->prepare(
            'INSERT INTO patient_profile_history (patient_id, edited_by, snapshot)
             VALUES (:patient_id, :edited_by, :snapshot)'
        );
        $stmt->execute([
            'patient_id' => $patientId,
            'edited_by' => $editedBy !== null && $editedBy > 0 ? $editedBy : null,
            'snapshot' => $json,
        ]);
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
            'SELECT h.id, h.edited_at, h.edited_by, u.name AS edited_by_name, h.snapshot
             FROM patient_profile_history h
             LEFT JOIN users u ON u.id = h.edited_by
             WHERE h.patient_id = :pid
             ORDER BY h.edited_at DESC, h.id DESC'
        );
        $stmt->execute(['pid' => $patientId]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $decoded = json_decode((string) $row['snapshot'], true);
            if (!is_array($decoded)) {
                continue;
            }
            $rows[] = [
                'id' => (int) $row['id'],
                'edited_at' => (string) $row['edited_at'],
                'edited_by' => $row['edited_by'] !== null ? (int) $row['edited_by'] : null,
                'edited_by_name' => trim((string) ($row['edited_by_name'] ?? '')),
                'snapshot' => $decoded,
            ];
        }

        return $rows;
    }

    public static function formatEditedAt(string $editedAt): string
    {
        $ts = strtotime($editedAt);

        return $ts !== false ? date('d M Y, g:i A', $ts) : $editedAt;
    }

    /**
     * @param array<string, mixed> $patient Current patient row from database.
     * @return list<array<string, mixed>>
     */
    public static function listChangesForPatient(int $patientId, array $patient): array
    {
        $entries = self::listForPatient($patientId);
        if ($entries === []) {
            return [];
        }

        $currentSnapshot = self::buildSnapshot($patient, $patientId);
        $enriched = [];

        foreach ($entries as $index => $entry) {
            $previous = is_array($entry['snapshot'] ?? null) ? $entry['snapshot'] : [];
            $new = $index === 0
                ? $currentSnapshot
                : (is_array($entries[$index - 1]['snapshot'] ?? null) ? $entries[$index - 1]['snapshot'] : []);

            $changes = self::buildChangeRows($previous, $new);
            if ($changes === []) {
                continue;
            }

            $enriched[] = array_merge($entry, ['changes' => $changes]);
        }

        return $enriched;
    }

    /**
     * @param array<string, mixed> $previous
     * @param array<string, mixed> $new
     * @return list<array{label: string, previous: string, new: string}>
     */
    public static function buildChangeRows(array $previous, array $new): array
    {
        $fields = self::fieldValues($previous);
        $newFields = self::fieldValues($new);
        $changes = [];

        foreach ($fields as $key => $previousValue) {
            $newValue = $newFields[$key] ?? '';
            if ($previousValue === $newValue) {
                continue;
            }

            $changes[] = [
                'label' => self::fieldLabel($key),
                'previous' => $previousValue,
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, string>
     */
    private static function fieldValues(array $snapshot): array
    {
        $symptomText = self::formatSymptomsText($snapshot['symptoms'] ?? []);
        $delivery = Patient::formatDeliveryAddress(
            (string) ($snapshot['address'] ?? ''),
            isset($snapshot['delivery_address']) ? (string) $snapshot['delivery_address'] : null
        );
        $remarks = trim((string) ($snapshot['remarks'] ?? ''));

        return [
            'name' => self::displayValue((string) ($snapshot['name'] ?? '')),
            'age' => self::displayValue((string) ($snapshot['age'] ?? '')),
            'gender' => self::displayValue(Patient::genderLabel((string) ($snapshot['gender'] ?? ''))),
            'phone' => self::displayValue(phone_format_display((string) ($snapshot['phone'] ?? ''))),
            'address' => self::displayValue((string) ($snapshot['address'] ?? '')),
            'delivery_address' => self::displayValue($delivery),
            'symptoms' => $symptomText,
            'remarks' => self::displayValue($remarks),
        ];
    }

    private static function displayValue(string $value): string
    {
        return trim($value) !== '' ? trim($value) : '—';
    }

    private static function fieldLabel(string $key): string
    {
        return match ($key) {
            'name' => __('patient.field.name'),
            'age' => __('patient.field.age'),
            'gender' => __('patient.field.gender'),
            'phone' => __('patient.field.phone'),
            'address' => __('patient.field.address'),
            'delivery_address' => __('patient.field.delivery_address'),
            'symptoms' => __('patient.field.symptoms'),
            'remarks' => __('patient.field.remarks'),
            default => $key,
        };
    }

    /**
     * @param mixed $symptoms
     */
    private static function formatSymptomsText(mixed $symptoms): string
    {
        if (!is_array($symptoms) || $symptoms === []) {
            return __('patient.symptoms.none');
        }

        $labels = [];
        foreach ($symptoms as $item) {
            if (is_array($item) && isset($item['label'])) {
                $labels[] = (string) $item['label'];
            }
        }

        return $labels !== [] ? implode(', ', $labels) : __('patient.symptoms.none');
    }
}

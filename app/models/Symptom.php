<?php

declare(strict_types=1);

final class Symptom
{
    /**
     * @return list<array{id: int, label: string}>
     */
    public static function listActive(): array
    {
        $stmt = db()->query(
            'SELECT id, label
             FROM symptoms
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );

        return self::normalizeRows($stmt->fetchAll());
    }

    /**
     * @return list<array{id: int, label: string, in_use: bool}>
     */
    public static function listForManage(): array
    {
        $stmt = db()->query(
            'SELECT s.id, s.label,
                    EXISTS(
                        SELECT 1 FROM patient_symptoms ps WHERE ps.symptom_id = s.id
                    ) AS in_use
             FROM symptoms s
             WHERE s.is_active = 1
             ORDER BY s.sort_order ASC, s.id ASC'
        );

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
                'in_use' => (bool) $row['in_use'],
            ];
        }

        return $out;
    }

    public static function isAssignedToPatient(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $stmt = db()->prepare(
            'SELECT 1 FROM patient_symptoms WHERE symptom_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    /**
     * @return list<int>
     */
    public static function activeIds(): array
    {
        $stmt = db()->query('SELECT id FROM symptoms WHERE is_active = 1');
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    public static function filterActiveIds(array $ids): array
    {
        static $allowed = null;
        if ($allowed === null) {
            $allowed = array_flip(self::activeIds());
        }
        $valid = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (isset($allowed[$id])) {
                $valid[] = $id;
            }
        }

        return array_values(array_unique($valid));
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function create(string $rawLabel): array
    {
        $label = self::normalizeLabel($rawLabel);
        $errors = self::validateLabel($label);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $existing = self::findByLabel($label);
        if ($existing !== null) {
            if ((int) $existing['is_active'] === 1) {
                return ['ok' => false, 'errors' => ['label' => __('symptom.error.duplicate')]];
            }

            self::reactivate((int) $existing['id']);

            return ['ok' => true];
        }

        $stmt = db()->prepare(
            'INSERT INTO symptoms (label, sort_order) VALUES (:label, :sort_order)'
        );
        $stmt->execute(['label' => $label, 'sort_order' => self::nextSortOrder()]);

        return ['ok' => true];
    }

    public static function deactivate(int $id): bool
    {
        if ($id < 1 || self::isAssignedToPatient($id)) {
            return false;
        }

        $stmt = db()->prepare(
            'UPDATE symptoms SET is_active = 0 WHERE id = :id AND is_active = 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return list<string>
     */
    public static function labelsForPatientCode(string $code): array
    {
        if (!self::isValidPatientCode($code)) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT s.label
             FROM symptoms s
             INNER JOIN patient_symptoms ps ON ps.symptom_id = s.id
             INNER JOIN patients p ON p.id = ps.patient_id
             WHERE p.patient_code = :code
             ORDER BY s.sort_order ASC, s.id ASC'
        );
        $stmt->execute(['code' => $code]);

        $labels = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $label) {
            $labels[] = (string) $label;
        }

        return $labels;
    }

    /**
     * @return list<int>
     */
    public static function idsForPatientCode(string $code): array
    {
        if (!self::isValidPatientCode($code)) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT ps.symptom_id
             FROM patient_symptoms ps
             INNER JOIN patients p ON p.id = ps.patient_id
             INNER JOIN symptoms s ON s.id = ps.symptom_id
             WHERE p.patient_code = :code AND s.is_active = 1
             ORDER BY s.sort_order ASC, s.id ASC'
        );
        $stmt->execute(['code' => $code]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    public static function inactiveIdsForPatient(int $patientId): array
    {
        if ($patientId < 1) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT ps.symptom_id
             FROM patient_symptoms ps
             INNER JOIN symptoms s ON s.id = ps.symptom_id
             WHERE ps.patient_id = :pid AND s.is_active = 0'
        );
        $stmt->execute(['pid' => $patientId]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    private static function isValidPatientCode(string $code): bool
    {
        return (bool) preg_match('/^GAA-\d{6}$/', $code);
    }

    /**
     * @return array{id: int, is_active: int}|null
     */
    private static function findByLabel(string $label): ?array
    {
        $stmt = db()->prepare('SELECT id, is_active FROM symptoms WHERE label = :label LIMIT 1');
        $stmt->execute(['label' => $label]);
        $row = $stmt->fetch();

        return $row !== false ? ['id' => (int) $row['id'], 'is_active' => (int) $row['is_active']] : null;
    }

    private static function reactivate(int $id): void
    {
        $stmt = db()->prepare(
            'UPDATE symptoms SET is_active = 1, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'sort_order' => self::nextSortOrder()]);
    }

    private static function normalizeLabel(string $label): string
    {
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? '';

        return mb_substr($label, 0, 120);
    }

    /**
     * @return array<string, string>
     */
    private static function validateLabel(string $label): array
    {
        if (mb_strlen($label) < 2) {
            return ['label' => __('symptom.error.label')];
        }

        return [];
    }

    private static function nextSortOrder(): int
    {
        $max = db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM symptoms')->fetchColumn();

        return ((int) $max) + 1;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: int, label: string}>
     */
    private static function normalizeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
            ];
        }

        return $out;
    }
}

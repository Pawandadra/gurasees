<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Symptom.php';
require_once APP_PATH . '/models/PaymentSettings.php';
require_once APP_PATH . '/models/Visit.php';

final class Patient
{
    /**
     * @return array{ok: true, patient_code: string}|array{ok: false, errors: array<string, string>, existing_patient_code?: string}
     */
    public static function register(array $raw): array
    {
        $data = self::sanitize($raw);
        $payment = PaymentSettings::sanitizeRegistration($raw);
        $errors = self::validate($data, true);
        if ($payment !== null) {
            $errors = array_merge($errors, PaymentSettings::validateRegistration($payment));
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $existing = self::findDuplicateByNameAndPhone($data);
        if ($existing !== null) {
            return [
                'ok' => false,
                'errors' => ['_duplicate' => __('patient.register.duplicate')],
                'existing_patient_code' => $existing['patient_code'],
            ];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $registeredAt = self::registrationTimestamp($data['registered_at']);

            $stmt = $pdo->prepare(
                'INSERT INTO patients (
                    name, age, gender, phone, address, delivery_address, remarks,
                    payment_amount, payment_gst_amount, payment_method, payment_status, payment_paid_amount,
                    created_at
                 ) VALUES (
                    :name, :age, :gender, :phone, :address, :delivery_address, :remarks,
                    :payment_amount, :payment_gst_amount, :payment_method, :payment_status, :payment_paid_amount,
                    :created_at
                 )'
            );
            $stmt->execute([
                'name' => $data['name'],
                'age' => $data['age'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'delivery_address' => $data['delivery_address'] !== '' ? $data['delivery_address'] : null,
                'remarks' => $data['remarks'] !== '' ? $data['remarks'] : null,
                'payment_amount' => $payment['payment_amount'] ?? 0,
                'payment_gst_amount' => $payment['payment_gst_amount'] ?? 0,
                'payment_method' => $payment['payment_method'] ?? null,
                'payment_status' => $payment['payment_status'] ?? null,
                'payment_paid_amount' => $payment !== null && $payment['payment_status'] !== null
                    ? $payment['payment_paid_amount']
                    : null,
                'created_at' => $registeredAt,
            ]);

            $id = (int) $pdo->lastInsertId();
            $code = sprintf('GAA-%06d', $id);

            $upd = $pdo->prepare('UPDATE patients SET patient_code = :code WHERE id = :id');
            $upd->execute(['code' => $code, 'id' => $id]);

            self::syncSymptoms($id, self::parseSymptomIds($raw));

            $pdo->commit();

            return ['ok' => true, 'patient_code' => $code];
        } catch (Throwable) {
            $pdo->rollBack();

            return ['ok' => false, 'errors' => ['_form' => __('error.server')]];
        }
    }

    /** @var array<string, string> sort key => SQL order expression */
    private const SORT_COLUMNS = [
        'id' => 'p.id',
        'name' => 'p.name',
        'age' => 'p.age',
        'gender' => 'p.gender',
        'phone' => 'p.phone',
        'address' => 'p.address',
        'date' => 'COALESCE(lv.last_visited_at, p.created_at)',
    ];

    private const LIST_FROM_SQL = '
        FROM patients p
        LEFT JOIN (
            SELECT patient_id, MAX(visited_at) AS last_visited_at
            FROM visits
            GROUP BY patient_id
        ) lv ON lv.patient_id = p.id';

    private const LIST_SELECT_SQL = '
        p.patient_code, p.name, p.age, p.gender, p.phone, p.address, p.created_at,
        lv.last_visited_at';

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
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 8, string $sort = 'date', string $dir = 'desc'): array
    {
        $limit = max(1, min($limit, 20));
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'date');

        $stmt = db()->prepare(
            'SELECT ' . self::LIST_SELECT_SQL . self::LIST_FROM_SQL . "
             WHERE p.patient_code IS NOT NULL
             ORDER BY {$orderSql}, p.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Most recently registered patient (single row).
     *
     * @return list<array<string, mixed>>
     */
    public static function lastRegistered(): array
    {
        $stmt = db()->query(
            'SELECT ' . self::LIST_SELECT_SQL . self::LIST_FROM_SQL . '
             WHERE p.patient_code IS NOT NULL
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 1'
        );

        $row = $stmt->fetch();

        return $row !== false ? [$row] : [];
    }

    /**
     * @param array{q?: string, gender?: string, age_min?: string, age_max?: string, date_from?: string, date_to?: string} $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public static function listFiltered(
        array $filters,
        string $sort = 'date',
        string $dir = 'desc',
        int $page = 1,
        int $perPage = 25
    ): array {
        $perPage = max(1, min($perPage, 100));
        $page = max(1, $page);
        $sortParams = self::normalizeSort($sort, $dir);
        $orderSql = db_order_sql(self::SORT_COLUMNS, $sortParams['sort'], $sortParams['dir'], 'date');
        $offset = ($page - 1) * $perPage;

        $where = self::buildListWhere($filters);
        $pdo = db();

        // Avoid window functions (COUNT(*) OVER()) for older MySQL versions.
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) AS total ' . self::LIST_FROM_SQL . " WHERE {$where['sql']}"
        );
        db_bind_named($countStmt, $where['bind'], ['has_phone_digits', 'age_min', 'age_max']);
        $countStmt->execute();
        $total = (int) (($countStmt->fetch()['total'] ?? 0));

        $stmt = $pdo->prepare(
            'SELECT ' . self::LIST_SELECT_SQL . ' ' . self::LIST_FROM_SQL . "
             WHERE {$where['sql']}
             ORDER BY {$orderSql}, p.id DESC
             LIMIT :lim OFFSET :off"
        );
        db_bind_named($stmt, $where['bind'], ['has_phone_digits', 'age_min', 'age_max']);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * @param array{q?: string, gender?: string, age_min?: string, age_max?: string, date_from?: string, date_to?: string} $filters
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

        $gender = (string) ($filters['gender'] ?? '');
        if (in_array($gender, ['male', 'female', 'other'], true)) {
            $parts[] = 'p.gender = :gender';
            $bind['gender'] = $gender;
        }

        $ageMin = (string) ($filters['age_min'] ?? '');
        if ($ageMin !== '') {
            $parts[] = 'p.age >= :age_min';
            $bind['age_min'] = (int) $ageMin;
        }

        $ageMax = (string) ($filters['age_max'] ?? '');
        if ($ageMax !== '') {
            $parts[] = 'p.age <= :age_max';
            $bind['age_max'] = (int) $ageMax;
        }

        $activityAt = 'COALESCE(lv.last_visited_at, p.created_at)';
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($dateFrom !== '' || $dateTo !== '') {
            if ($dateFrom !== '' && $dateTo === '') {
                $parts[] = "{$activityAt} >= :date_from AND {$activityAt} < :date_from_end";
                $bind['date_from'] = $dateFrom . ' 00:00:00';
                $bind['date_from_end'] = self::nextDayStart($dateFrom);
            } else {
                if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
                    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
                }
                if ($dateFrom !== '') {
                    $parts[] = "{$activityAt} >= :date_from";
                    $bind['date_from'] = $dateFrom . ' 00:00:00';
                }
                if ($dateTo !== '') {
                    $parts[] = "{$activityAt} < :date_to_end";
                    $bind['date_to_end'] = self::nextDayStart($dateTo);
                }
            }
        }

        return ['sql' => implode(' AND ', $parts), 'bind' => $bind];
    }

    public static function genderLabel(string $gender): string
    {
        return match ($gender) {
            'male', 'female', 'other' => __('patient.gender.' . $gender),
            default => $gender,
        };
    }

    public static function genderToLetter(string $gender): string
    {
        return match ($gender) {
            'male' => 'M',
            'female' => 'F',
            'other' => 'O',
            default => strtoupper(substr(trim($gender), 0, 1)),
        };
    }

    public static function normalizeGenderInput(string $input): ?string
    {
        return match (strtoupper(trim($input))) {
            'M' => 'male',
            'F' => 'female',
            'O' => 'other',
            default => null,
        };
    }

    public static function formatRegisteredAt(string $datetime): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($dt === false) {
            return $datetime;
        }

        return $dt->format('d M Y');
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function formatListLastVisited(array $row): string
    {
        $lastVisited = $row['last_visited_at'] ?? null;
        if ($lastVisited !== null && $lastVisited !== '') {
            return self::formatRegisteredAt((string) $lastVisited);
        }

        return self::formatRegisteredAt((string) $row['created_at']);
    }

    public static function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^GAA-\d{6}$/', $code);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        if (!self::isValidCode($code)) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, patient_code, name, age, gender, phone, address, delivery_address, remarks,
                    payment_amount, payment_gst_amount, payment_method, payment_status, payment_paid_amount, created_at
             FROM patients
             WHERE patient_code = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return list<array{code: string, name: string, phone: string, url: string}>
     */
    public static function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $limit = max(1, min($limit, 15));
        $search = db_patient_search_clause('', $query);
        $codeLike = $search['bind']['code'];

        $stmt = db()->prepare(
            'SELECT patient_code, name, phone
             FROM patients
             WHERE patient_code IS NOT NULL
               AND ' . $search['sql'] . '
             ORDER BY
               CASE WHEN patient_code LIKE :code_order THEN 0 ELSE 1 END,
               name ASC
             LIMIT :lim'
        );
        db_bind_named($stmt, array_merge($search['bind'], ['code_order' => $codeLike]), $search['int_keys']);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $code = (string) $row['patient_code'];
            $results[] = [
                'code' => $code,
                'name' => (string) $row['name'],
                'phone' => phone_format_display((string) $row['phone']),
                'url' => base_url('/patient_view.php?' . http_build_query(['code' => $code])),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $patient
     * @return array<string, string>
     */
    public static function recordToForm(array $patient): array
    {
        $parsed = phone_parse_stored((string) $patient['phone']);

        $code = (string) $patient['patient_code'];
        $symptomIds = Symptom::idsForPatientCode($code);

        return [
            'name' => (string) $patient['name'],
            'age' => (string) $patient['age'],
            'gender' => self::genderToLetter((string) $patient['gender']),
            'phone_iso' => $parsed['iso'],
            'phone_local' => $parsed['local'],
            'address' => (string) $patient['address'],
            'delivery_address' => (string) ($patient['delivery_address'] ?? ''),
            'remarks' => (string) ($patient['remarks'] ?? ''),
            'delivery_same_as_address' => self::deliveryMatchesPrimary(
                (string) $patient['address'],
                isset($patient['delivery_address']) ? (string) $patient['delivery_address'] : null
            ) ? '1' : '',
            'symptoms' => array_map(static fn(int $id): string => (string) $id, $symptomIds),
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return list<int>
     */
    public static function parseSymptomIds(array $raw): array
    {
        $ids = [];
        foreach ((array) ($raw['symptoms'] ?? []) as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id !== false && (int) $id > 0) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, string>
     */
    public static function formStateFromRaw(array $raw): array
    {
        $data = self::sanitize($raw);
        $data['age'] = $data['age'] > 0 ? (string) $data['age'] : '';
        if (array_key_exists('registered_at', $raw) && ($data['registered_at'] ?? '') === '') {
            $data['registered_at'] = (new DateTimeImmutable('today'))->format('Y-m-d');
        }
        $data['symptoms'] = array_map(
            static fn(int $id): string => (string) $id,
            self::parseSymptomIds($raw)
        );
        $data['delivery_same_as_address'] = !empty($raw['delivery_same_as_address']) ? '1' : '';

        $rawGender = strtoupper(trim((string) ($raw['gender'] ?? '')));
        $data['gender'] = $rawGender !== ''
            ? substr($rawGender, 0, 1)
            : self::genderToLetter($data['gender']);

        $defaultAmount = PaymentSettings::defaultAmount();
        $data = array_merge($data, [
            'payment_amount' => (string) ($raw['payment_amount'] ?? ($defaultAmount > 0
                ? PaymentSettings::formatAmount($defaultAmount)
                : '')),
            'payment_method' => (string) ($raw['payment_method'] ?? PaymentSettings::defaultMethod()),
            'payment_status' => (string) ($raw['payment_status'] ?? PaymentSettings::defaultStatus()),
            'payment_paid_amount' => (string) ($raw['payment_paid_amount'] ?? ''),
        ]);

        return $data;
    }

    public static function formatPaymentSummary(array $patient): ?string
    {
        $amount = (float) ($patient['payment_amount'] ?? 0);
        $gst = (float) ($patient['payment_gst_amount'] ?? 0);
        $total = PaymentSettings::registrationTotal($amount, $gst);
        if ($total <= 0) {
            return null;
        }
        $status = (string) ($patient['payment_status'] ?? '');
        $method = (string) ($patient['payment_method'] ?? '');
        $paid = (float) ($patient['payment_paid_amount'] ?? 0);

        $parts = [
            __('payment.summary.total', ['amount' => PaymentSettings::formatAmount($total)]),
            __('payment.summary.gst', ['amount' => PaymentSettings::formatAmount($gst)]),
            __('payment.summary.without_gst', ['amount' => PaymentSettings::formatAmount($amount)]),
        ];
        if ($method !== '') {
            $parts[] = PaymentSettings::methodLabel($method);
        }
        if ($status !== '') {
            $parts[] = PaymentSettings::statusLabel($status);
        }
        if ($status === 'partial') {
            $parts[] = __('payment.summary.paid_of', [
                'paid' => PaymentSettings::formatAmount($paid),
                'total' => PaymentSettings::formatAmount($total),
            ]);
        }

        return implode(' · ', $parts);
    }

    /**
     * Registration fee still owed (pending or partial).
     *
     * @param array<string, mixed> $patient
     */
    public static function registrationBalance(array $patient): float
    {
        $total = PaymentSettings::registrationTotal(
            (float) ($patient['payment_amount'] ?? 0),
            (float) ($patient['payment_gst_amount'] ?? 0)
        );
        if ($total <= 0) {
            return 0.0;
        }

        return Visit::paymentBalance([
            'grand_total' => $total,
            'payment_status' => (string) ($patient['payment_status'] ?? ''),
            'payment_paid_amount' => (float) ($patient['payment_paid_amount'] ?? 0),
        ]);
    }

    /**
     * Total amount still to collect: registration balance + unpaid visit balances.
     *
     * @param array<string, mixed> $patient
     * @param list<array<string, mixed>> $visits
     */
    public static function totalOutstandingBalance(array $patient, array $visits = []): float
    {
        $total = self::registrationBalance($patient);

        foreach ($visits as $visit) {
            $total += Visit::paymentBalance($visit);
        }

        return round($total, 2);
    }

    public static function deliveryMatchesPrimary(string $address, ?string $delivery): bool
    {
        $address = trim($address);
        $delivery = trim((string) $delivery);

        return $address !== '' && $delivery === $address;
    }

    public static function formatDeliveryAddress(string $primary, ?string $delivery): string
    {
        $delivery = trim((string) $delivery);
        if ($delivery !== '') {
            return $delivery;
        }

        return '';
    }

    /**
     * @return list<string>
     */
    public static function symptomLabelsForCode(string $code): array
    {
        return Symptom::labelsForPatientCode($code);
    }

    /**
     * @param array<string, mixed> $patient
     * @param array{name: string, age: int, gender: string, phone: string, address: string, delivery_address: string, remarks: string} $data
     * @param array<string, mixed> $raw
     */
    private static function profileWouldChange(array $patient, int $patientId, array $data, array $raw): bool
    {
        $newDelivery = $data['delivery_address'] !== '' ? $data['delivery_address'] : null;
        $newRemarks = $data['remarks'] !== '' ? $data['remarks'] : null;

        if ($data['name'] !== (string) $patient['name']
            || $data['age'] !== (int) $patient['age']
            || $data['gender'] !== (string) $patient['gender']
            || $data['phone'] !== (string) $patient['phone']
            || $data['address'] !== (string) $patient['address']
            || self::nullableText($patient['delivery_address'] ?? null) !== self::nullableText($newDelivery)
            || self::nullableText($patient['remarks'] ?? null) !== self::nullableText($newRemarks)
        ) {
            return true;
        }

        $newActive = Symptom::filterActiveIds(self::parseSymptomIds($raw));
        $preservedInactive = Symptom::inactiveIdsForPatient($patientId);
        $newSymptomIds = array_values(array_unique(array_merge($newActive, $preservedInactive)));
        sort($newSymptomIds);

        $oldSymptomIds = self::symptomIdsForPatient($patientId);
        sort($oldSymptomIds);

        return $newSymptomIds !== $oldSymptomIds;
    }

    private static function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @return list<int>
     */
    private static function symptomIdsForPatient(int $patientId): array
    {
        if ($patientId < 1) {
            return [];
        }

        $stmt = db()->prepare(
            'SELECT symptom_id FROM patient_symptoms WHERE patient_id = :pid ORDER BY symptom_id ASC'
        );
        $stmt->execute(['pid' => $patientId]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @param list<int> $symptomIds
     */
    private static function syncSymptoms(int $patientId, array $symptomIds): void
    {
        $activeIds = Symptom::filterActiveIds($symptomIds);
        $preservedInactive = Symptom::inactiveIdsForPatient($patientId);
        $finalIds = array_values(array_unique(array_merge($activeIds, $preservedInactive)));

        $pdo = db();
        $del = $pdo->prepare('DELETE FROM patient_symptoms WHERE patient_id = :pid');
        $del->execute(['pid' => $patientId]);

        if ($finalIds === []) {
            return;
        }

        $valueParts = [];
        $params = [];
        foreach ($finalIds as $index => $sid) {
            $pidKey = 'pid' . $index;
            $sidKey = 'sid' . $index;
            $valueParts[] = '(:' . $pidKey . ', :' . $sidKey . ')';
            $params[$pidKey] = $patientId;
            $params[$sidKey] = $sid;
        }
        $ins = $pdo->prepare(
            'INSERT INTO patient_symptoms (patient_id, symptom_id) VALUES ' . implode(', ', $valueParts)
        );
        $ins->execute($params);
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function update(string $code, array $raw, ?int $editedBy = null): array
    {
        $patient = self::findByCode($code);
        if ($patient === null) {
            return ['ok' => false, 'errors' => ['_form' => __('patient.error.not_found')]];
        }

        $data = self::sanitize($raw);
        $errors = self::validate($data, false);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $duplicate = self::findDuplicateByNameAndPhone($data, $code);
        if ($duplicate !== null) {
            return [
                'ok' => false,
                'errors' => ['_duplicate' => __('patient.register.duplicate')],
                'existing_patient_code' => $duplicate['patient_code'],
            ];
        }

        $patientId = (int) $patient['id'];
        $pdo = db();
        $pdo->beginTransaction();

        try {
            if (self::profileWouldChange($patient, $patientId, $data, $raw)) {
                load_model('PatientProfileHistory');
                PatientProfileHistory::record(
                    $patientId,
                    $editedBy,
                    PatientProfileHistory::buildSnapshot($patient, $patientId)
                );
            }

            $stmt = $pdo->prepare(
                'UPDATE patients
                 SET name = :name, age = :age, gender = :gender, phone = :phone,
                     address = :address, delivery_address = :delivery_address, remarks = :remarks
                 WHERE patient_code = :code'
            );
            $stmt->execute([
                'name' => $data['name'],
                'age' => $data['age'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'delivery_address' => $data['delivery_address'] !== '' ? $data['delivery_address'] : null,
                'remarks' => $data['remarks'] !== '' ? $data['remarks'] : null,
                'code' => $code,
            ]);

            self::syncSymptoms($patientId, self::parseSymptomIds($raw));

            $pdo->commit();

            return ['ok' => true];
        } catch (Throwable) {
            $pdo->rollBack();

            return ['ok' => false, 'errors' => ['_form' => __('error.server')]];
        }
    }

    public const DELETE_OK = 'ok';
    public const DELETE_NOT_FOUND = 'not_found';
    public const DELETE_ERROR = 'error';

    /**
     * @return self::DELETE_OK|self::DELETE_NOT_FOUND|self::DELETE_ERROR
     */
    public static function delete(string $code): string
    {
        if (!self::isValidCode($code)) {
            return self::DELETE_NOT_FOUND;
        }

        $patient = self::findByCode($code);
        if ($patient === null) {
            return self::DELETE_NOT_FOUND;
        }

        load_model('Medicine');
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $visitStmt = $pdo->prepare('SELECT id FROM visits WHERE patient_id = :patient_id');
            $visitStmt->execute(['patient_id' => (int) $patient['id']]);
            foreach ($visitStmt->fetchAll(PDO::FETCH_COLUMN) as $visitId) {
                Medicine::restoreVisitStock((int) $visitId);
            }

            $stmt = $pdo->prepare('DELETE FROM patients WHERE patient_code = :code LIMIT 1');
            $stmt->execute(['code' => $code]);
            if ($stmt->rowCount() < 1) {
                $pdo->rollBack();

                return self::DELETE_NOT_FOUND;
            }

            $pdo->commit();

            return self::DELETE_OK;
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return self::DELETE_ERROR;
        }
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{name: string, age: int, gender: string, phone: string, phone_iso: string, phone_local: string, address: string, delivery_address: string, remarks: string, registered_at: string}
     */
    public static function sanitize(array $raw): array
    {
        $iso = phone_sanitize_iso((string) ($raw['phone_iso'] ?? 'IN'));

        $local = preg_replace('/\D+/', '', (string) ($raw['phone'] ?? ''));
        if ($iso === 'IN' && str_starts_with($local, '0')) {
            $local = substr($local, 1);
        }

        $address = input_string($raw['address'] ?? '', 500);
        $deliverySame = !empty($raw['delivery_same_as_address']);
        $deliveryAddress = $deliverySame
            ? $address
            : input_string($raw['delivery_address'] ?? '', 500);

        $gender = self::normalizeGenderInput((string) ($raw['gender'] ?? '')) ?? '';

        return [
            'name' => input_string($raw['name'] ?? '', 120),
            'age' => (int) filter_var($raw['age'] ?? '', FILTER_VALIDATE_INT),
            'gender' => $gender,
            'phone_iso' => $iso,
            'phone_local' => $local,
            'phone' => phone_build($iso, $local),
            'address' => $address,
            'delivery_address' => $deliveryAddress,
            'remarks' => input_string($raw['remarks'] ?? '', 1000),
            'registered_at' => patient_normalize_filter_date($raw['registered_at'] ?? null),
        ];
    }

    /**
     * @param array{name: string, age: int, gender: string, phone: string, phone_iso: string, phone_local: string, address: string, delivery_address: string, remarks: string, registered_at?: string} $data
     * @return array<string, string>
     */
    public static function validate(array $data, bool $forRegistration = false): array
    {
        $errors = [];
        $required = __('validation.required');

        if (mb_strlen($data['name']) < 2) {
            $errors['name'] = $required;
        }

        if ($data['age'] < 1 || $data['age'] > 120) {
            $errors['age'] = $required;
        }

        if (!in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors['gender'] = $required;
        }

        if (!phone_validate_local($data['phone_iso'], $data['phone_local'])) {
            $errors['phone'] = $required;
        }

        if (mb_strlen($data['address']) < 5) {
            $errors['address'] = __('patient.error.address');
        }

        if ($forRegistration) {
            $registeredAt = (string) ($data['registered_at'] ?? '');
            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
            if ($registeredAt === '') {
                $errors['registered_at'] = __('patient.error.registered_at');
            } elseif ($registeredAt > $today) {
                $errors['registered_at'] = __('patient.error.registered_at_future');
            }
        }

        return $errors;
    }

    private static function registrationTimestamp(string $date): string
    {
        $date = patient_normalize_filter_date($date);
        if ($date === '') {
            return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        return $date . ' 00:00:00';
    }

    /**
     * @param array{name: string, phone: string} $data
     * @return array{patient_code: string}|null
     */
    public static function findDuplicateByNameAndPhone(array $data, ?string $excludeCode = null): ?array
    {
        if ($data['name'] === '' || $data['phone'] === '') {
            return null;
        }

        $excludeCode = $excludeCode !== null ? strtoupper(trim($excludeCode)) : null;

        $stmt = db()->prepare(
            'SELECT patient_code, name
             FROM patients
             WHERE phone = :phone AND patient_code IS NOT NULL'
        );
        $stmt->execute(['phone' => $data['phone']]);

        $needle = self::normalizeNameForCompare($data['name']);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row)) {
                continue;
            }
            $patientCode = (string) $row['patient_code'];
            if ($excludeCode !== null && $patientCode === $excludeCode) {
                continue;
            }
            if (self::normalizeNameForCompare((string) $row['name']) === $needle) {
                return ['patient_code' => $patientCode];
            }
        }

        return null;
    }

    public static function normalizeNameForCompare(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return mb_strtolower($name, 'UTF-8');
    }

    private static function nextDayStart(string $ymd): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);

        return $dt !== false
            ? $dt->modify('+1 day')->format('Y-m-d 00:00:00')
            : $ymd . ' 23:59:59';
    }
}

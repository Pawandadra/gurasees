<?php

declare(strict_types=1);

require_once APP_PATH . '/models/Symptom.php';
require_once APP_PATH . '/models/PaymentSettings.php';

final class Patient
{
    /**
     * @return array{ok: true, patient_code: string}|array{ok: false, errors: array<string, string>}
     */
    public static function register(array $raw): array
    {
        $data = self::sanitize($raw);
        $payment = PaymentSettings::sanitizeRegistration($raw);
        $errors = self::validate($data);
        if ($payment !== null) {
            $errors = array_merge($errors, PaymentSettings::validateRegistration($payment));
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO patients (
                    name, age, gender, phone, address, delivery_address,
                    payment_amount, payment_gst_amount, payment_method, payment_status, payment_paid_amount
                 ) VALUES (
                    :name, :age, :gender, :phone, :address, :delivery_address,
                    :payment_amount, :payment_gst_amount, :payment_method, :payment_status, :payment_paid_amount
                 )'
            );
            $stmt->execute([
                'name' => $data['name'],
                'age' => $data['age'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'delivery_address' => $data['delivery_address'] !== '' ? $data['delivery_address'] : null,
                'payment_amount' => $payment['payment_amount'] ?? 0,
                'payment_gst_amount' => $payment['payment_gst_amount'] ?? 0,
                'payment_method' => $payment['payment_method'] ?? null,
                'payment_status' => $payment['payment_status'] ?? null,
                'payment_paid_amount' => $payment !== null && $payment['payment_status'] !== null
                    ? $payment['payment_paid_amount']
                    : null,
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

        $stmt = $pdo->prepare(
            'SELECT ' . self::LIST_SELECT_SQL . ', COUNT(*) OVER() AS _list_total ' . self::LIST_FROM_SQL . "
             WHERE {$where['sql']}
             ORDER BY {$orderSql}, p.id DESC
             LIMIT :lim OFFSET :off"
        );
        db_bind_named($stmt, $where['bind'], ['has_phone_digits', 'age_min', 'age_max']);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return db_strip_list_total($stmt->fetchAll());
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
        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $parts[] = "{$activityAt} >= :date_from";
            $bind['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $parts[] = "{$activityAt} <= :date_to";
            $bind['date_to'] = $dateTo . ' 23:59:59';
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
            'SELECT id, patient_code, name, age, gender, phone, address, delivery_address,
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
            'gender' => (string) $patient['gender'],
            'phone_iso' => $parsed['iso'],
            'phone_local' => $parsed['local'],
            'address' => (string) $patient['address'],
            'delivery_address' => (string) ($patient['delivery_address'] ?? ''),
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
        $data['symptoms'] = array_map(
            static fn(int $id): string => (string) $id,
            self::parseSymptomIds($raw)
        );
        $data['delivery_same_as_address'] = !empty($raw['delivery_same_as_address']) ? '1' : '';

        if (PaymentSettings::isEnabled()) {
            $data = array_merge($data, [
                'payment_amount' => (string) ($raw['payment_amount'] ?? PaymentSettings::formatAmount(PaymentSettings::defaultAmount())),
                'payment_method' => (string) ($raw['payment_method'] ?? PaymentSettings::defaultMethod()),
                'payment_status' => (string) ($raw['payment_status'] ?? PaymentSettings::defaultStatus()),
                'payment_paid_amount' => (string) ($raw['payment_paid_amount'] ?? ''),
            ]);
        }

        return $data;
    }

    public static function formatPaymentSummary(array $patient): ?string
    {
        $amount = (float) ($patient['payment_amount'] ?? 0);
        if ($amount <= 0) {
            return null;
        }

        $gst = (float) ($patient['payment_gst_amount'] ?? 0);
        $total = PaymentSettings::registrationTotal($amount, $gst);
        $status = (string) ($patient['payment_status'] ?? '');
        $method = (string) ($patient['payment_method'] ?? '');
        $paid = (float) ($patient['payment_paid_amount'] ?? 0);

        $parts = [
            PaymentSettings::formatAmount($amount),
            __('payment.summary.gst', ['amount' => PaymentSettings::formatAmount($gst)]),
            __('payment.summary.total', ['amount' => PaymentSettings::formatAmount($total)]),
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
        $params = ['pid' => $patientId];
        foreach ($finalIds as $index => $sid) {
            $key = 'sid' . $index;
            $valueParts[] = '(:pid, :' . $key . ')';
            $params[$key] = $sid;
        }
        $ins = $pdo->prepare(
            'INSERT INTO patient_symptoms (patient_id, symptom_id) VALUES ' . implode(', ', $valueParts)
        );
        $ins->execute($params);
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function update(string $code, array $raw): array
    {
        $patient = self::findByCode($code);
        if ($patient === null) {
            return ['ok' => false, 'errors' => ['_form' => __('patient.error.not_found')]];
        }

        $data = self::sanitize($raw);
        $errors = self::validate($data);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $patientId = (int) $patient['id'];
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE patients
                 SET name = :name, age = :age, gender = :gender, phone = :phone,
                     address = :address, delivery_address = :delivery_address
                 WHERE patient_code = :code'
            );
            $stmt->execute([
                'name' => $data['name'],
                'age' => $data['age'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'delivery_address' => $data['delivery_address'] !== '' ? $data['delivery_address'] : null,
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

    public static function delete(string $code): bool
    {
        if (!self::isValidCode($code)) {
            return false;
        }

        $stmt = db()->prepare('DELETE FROM patients WHERE patient_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{name: string, age: int, gender: string, phone: string, phone_iso: string, phone_local: string, address: string, delivery_address: string}
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

        return [
            'name' => input_string($raw['name'] ?? '', 120),
            'age' => (int) filter_var($raw['age'] ?? '', FILTER_VALIDATE_INT),
            'gender' => input_string($raw['gender'] ?? '', 10),
            'phone_iso' => $iso,
            'phone_local' => $local,
            'phone' => phone_build($iso, $local),
            'address' => $address,
            'delivery_address' => $deliveryAddress,
        ];
    }

    /**
     * @param array{name: string, age: int, gender: string, phone: string, phone_iso: string, phone_local: string, address: string, delivery_address: string} $data
     * @return array<string, string>
     */
    public static function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['name']) < 2) {
            $errors['name'] = __('patient.error.name');
        }

        if ($data['age'] < 1 || $data['age'] > 120) {
            $errors['age'] = __('patient.error.age');
        }

        if (!in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors['gender'] = __('patient.error.gender');
        }

        if (!phone_validate_local($data['phone_iso'], $data['phone_local'])) {
            $errors['phone'] = $data['phone_iso'] === 'IN'
                ? __('patient.error.phone_in')
                : __('patient.error.phone');
        }

        if (mb_strlen($data['address']) < 5) {
            $errors['address'] = __('patient.error.address');
        }

        return $errors;
    }
}

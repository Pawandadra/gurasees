<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');
load_model('Visit');
load_model('Medicine');
load_model('PaymentSettings');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$code = strtoupper(input_string($_GET['code'] ?? $_POST['code'] ?? '', 12));
$patient = Patient::findByCode($code);
if ($patient === null) {
    http_response_code(404);
    exit(__('patient.error.not_found'));
}

$patientId = (int) $patient['id'];
$sortParams = Patient::normalizeSort(
    (string) ($_GET['sort'] ?? $_POST['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? $_POST['dir'] ?? 'desc')
);
$return = patient_return_from_request();
$listFilters = $return === 'visits'
    ? visit_list_filters_from_request()
    : patient_list_filters_from_request();

$visitErrors = [];
$visitBilling = Visit::billingDefaults();
$visitOld = array_merge(
    [
        'visited_at' => (new DateTimeImmutable('now'))->format('Y-m-d\TH:i'),
        'notes' => '',
        'visit_charge' => $visitBilling['visit_charge'],
        'medicines' => [],
    ],
    PaymentSettings::visitDefaults()
);

try {
    $catalogMedicines = Medicine::listForReception();
} catch (Throwable) {
    $catalogMedicines = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_visit') {
    csrf_require();
    $user = auth_user();
    $recordedBy = $user !== null ? (int) $user['id'] : null;
    $result = Visit::create($patientId, $_POST, $recordedBy);

    if ($result['ok']) {
        flash_set('success', __('visit.add.success'));
        redirect(base_url('/patient_view.php?' . http_build_query(['code' => $code])));
    }

    $visitErrors = $result['errors'];
    $parsedLines = Visit::parseMedicineLines($_POST);
    $visitOld = array_merge($visitOld, [
        'visited_at' => (string) ($_POST['visited_at'] ?? $visitOld['visited_at']),
        'notes' => input_string($_POST['notes'] ?? '', 500),
        'visit_charge' => trim((string) ($_POST['visit_charge'] ?? $visitOld['visit_charge'])),
        'payment_method' => input_string($_POST['payment_method'] ?? '', 10),
        'payment_status' => input_string($_POST['payment_status'] ?? '', 10),
        'payment_paid_amount' => trim((string) ($_POST['payment_paid_amount'] ?? '')),
        'medicines' => array_values(array_filter(
            array_map(
                static fn (array $line): array => [
                    'medicine_id' => (int) $line['medicine_id'],
                    'quantity' => (int) $line['quantity'],
                    'courier_quantity' => (int) ($line['courier_quantity'] ?? 0),
                ],
                $parsedLines
            ),
            static fn (array $line): bool => $line['medicine_id'] > 0 && $line['quantity'] > 0
        )),
    ]);
    $visitBilling['payment_method'] = $visitOld['payment_method'];
    $visitBilling['payment_status'] = $visitOld['payment_status'];
    $visitBilling['payment_paid_amount'] = $visitOld['payment_paid_amount'];
}

$pageTitle = __('patient.view.title');
$activeNav = match ($return) {
    'patients' => 'patients',
    'visits' => 'visits',
    default => 'dashboard',
};
$symptomLabels = Patient::symptomLabelsForCode($code);
$successMessage = flash_get('success');
$visits = Visit::listForPatient($patientId);

view('patient/view', array_merge(
    compact('patient', 'code', 'symptomLabels', 'visits', 'visitErrors', 'visitOld', 'visitBilling', 'successMessage', 'catalogMedicines', 'return', 'listFilters'),
    $sortParams
));

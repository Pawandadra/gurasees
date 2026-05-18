<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');
load_model('Visit');
load_model('Medicine');

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

$visitErrors = [];
$visitBilling = Visit::billingDefaults();
$visitOld = [
    'visited_at' => (new DateTimeImmutable('now'))->format('Y-m-d\TH:i'),
    'notes' => '',
    'visit_charge' => $visitBilling['visit_charge'],
    'medicines' => [],
];

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
    $visitOld = [
        'visited_at' => (string) ($_POST['visited_at'] ?? $visitOld['visited_at']),
        'notes' => input_string($_POST['notes'] ?? '', 500),
        'visit_charge' => trim((string) ($_POST['visit_charge'] ?? $visitOld['visit_charge'])),
        'medicines' => array_values(array_filter(
            $parsedLines,
            static fn (array $line): bool => (int) $line['medicine_id'] > 0 && (int) $line['quantity'] > 0
        )),
    ];
}

$pageTitle = __('patient.view.title');
$activeNav = 'dashboard';
$symptomLabels = Patient::symptomLabelsForCode($code);
$successMessage = flash_get('success');
$visits = Visit::listForPatient($patientId);

view('patient/view', array_merge(
    compact('patient', 'code', 'symptomLabels', 'visits', 'visitErrors', 'visitOld', 'visitBilling', 'successMessage', 'catalogMedicines'),
    $sortParams
));

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');
load_model('PatientProfileHistory');
load_model('Visit');
load_model('Medicine');
load_model('PaymentSettings');
load_model('Courier');

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
        'medicine_total' => '',
        'courier_charge' => '',
        'medicines' => [],
    ],
    PaymentSettings::visitDefaults()
);
$editVisitId = filter_var($_GET['edit_visit'] ?? '', FILTER_VALIDATE_INT);
$editVisitId = $editVisitId !== false && $editVisitId > 0 ? (int) $editVisitId : 0;

if ($editVisitId > 0) {
    $editVisit = Visit::findForPatient($editVisitId, $patientId);
    if ($editVisit !== null) {
        $visitOld = Visit::recordToFormState($editVisit);
        $visitBilling = array_merge($visitBilling, [
            'payment_method' => $visitOld['payment_method'],
            'payment_status' => $visitOld['payment_status'],
            'payment_paid_amount' => $visitOld['payment_paid_amount'],
        ]);
    } else {
        $editVisitId = 0;
    }
}

try {
    $catalogMedicines = Medicine::listForReception();
} catch (Throwable) {
    $catalogMedicines = [];
}

if (($_GET['action'] ?? '') === 'visit_detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $visitId = filter_var($_GET['visit_id'] ?? '', FILTER_VALIDATE_INT);
    $visit = $visitId !== false && $visitId > 0
        ? Visit::findForPatient((int) $visitId, $patientId)
        : null;

    header('Content-Type: application/json; charset=utf-8');

    if ($visit === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => __('visit.error.not_found')], JSON_THROW_ON_ERROR);

        exit;
    }

    $canModify = Visit::canModify($visit);
    ob_start();
    require dirname(__DIR__) . '/views/partials/visit_detail_body.php';
    $html = ob_get_clean();

    echo json_encode([
        'ok' => true,
        'html' => $html,
        'canEdit' => $canModify,
        'canDelete' => $canModify,
        'editUrl' => base_url('/patient_view.php?' . http_build_query([
            'code' => $code,
            'edit_visit' => (int) $visit['id'],
        ])),
    ], JSON_THROW_ON_ERROR);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_visit') {
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
            'medicine_total' => trim((string) ($_POST['medicine_total'] ?? '')),
            'courier_charge' => trim((string) ($_POST['courier_charge'] ?? '')),
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
        $editVisitId = 0;
    } elseif ($action === 'update_visit') {
        $visitId = filter_var($_POST['visit_id'] ?? '', FILTER_VALIDATE_INT);
        $visitId = $visitId !== false ? (int) $visitId : 0;
        $result = Visit::update($visitId, $patientId, $_POST);

        if ($result['ok']) {
            flash_set('success', __('visit.edit.success'));
            redirect(base_url('/patient_view.php?' . http_build_query(['code' => $code])));
        }

        $visitErrors = $result['errors'];
        $editVisitId = $visitId;
        $parsedLines = Visit::parseMedicineLines($_POST);
        $visitOld = array_merge($visitOld, [
            'visited_at' => (string) ($_POST['visited_at'] ?? $visitOld['visited_at']),
            'notes' => input_string($_POST['notes'] ?? '', 500),
            'visit_charge' => trim((string) ($_POST['visit_charge'] ?? $visitOld['visit_charge'])),
            'medicine_total' => trim((string) ($_POST['medicine_total'] ?? '')),
            'courier_charge' => trim((string) ($_POST['courier_charge'] ?? '')),
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
        $visitBilling = array_merge($visitBilling, [
            'payment_method' => $visitOld['payment_method'],
            'payment_status' => $visitOld['payment_status'],
            'payment_paid_amount' => $visitOld['payment_paid_amount'],
        ]);
    } elseif ($action === 'delete_visit') {
        $visitId = filter_var($_POST['visit_id'] ?? '', FILTER_VALIDATE_INT);
        $visitId = $visitId !== false ? (int) $visitId : 0;
        $result = Visit::delete($visitId, $patientId);

        if ($result['ok']) {
            flash_set('success', __('visit.delete.success'));
        } else {
            flash_set('error', $result['errors']['_form'] ?? __('error.server'));
        }

        redirect(base_url('/patient_view.php?' . http_build_query(['code' => $code])));
    }
}

$pageTitle = __('patient.view.title');
$activeNav = match ($return) {
    'patients' => 'patients',
    'visits' => 'visits',
    default => 'dashboard',
};
$symptomLabels = Patient::symptomLabelsForCode($code);
$profileHistory = PatientProfileHistory::listChangesForPatient($patientId, $patient);
$successMessage = flash_get('success');
$errorMessage = flash_get('error');
$visits = Visit::listForPatient($patientId);
$totalBalance = Patient::totalOutstandingBalance($patient, $visits);

view('patient/view', array_merge(
    compact(
        'patient',
        'code',
        'symptomLabels',
        'profileHistory',
        'visits',
        'visitErrors',
        'visitOld',
        'visitBilling',
        'successMessage',
        'errorMessage',
        'catalogMedicines',
        'return',
        'listFilters',
        'editVisitId',
        'totalBalance'
    ),
    $sortParams
));

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

header('Content-Type: application/json; charset=UTF-8');

$query = input_string($_GET['q'] ?? '', 120);

try {
    $results = Patient::search($query);
} catch (Throwable $e) {
    app_log_error($e, 'patient_search');
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => production_error_message(), 'results' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$query = input_string($_GET['q'] ?? '', 120);
$results = Patient::search($query);

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);

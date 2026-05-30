<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Visit');
load_model('Medicine');
load_model('Patient');
load_model('PaymentSettings');
load_model('Courier');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

if (($_GET['action'] ?? '') === 'visit_detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $visitId = filter_var($_GET['visit_id'] ?? '', FILTER_VALIDATE_INT);
    $visit = $visitId !== false && $visitId > 0 ? Visit::findById((int) $visitId) : null;

    header('Content-Type: application/json; charset=utf-8');

    if ($visit === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => __('visit.error.not_found')], JSON_THROW_ON_ERROR);

        exit;
    }

    echo json_encode(visit_detail_response($visit), JSON_THROW_ON_ERROR);

    exit;
}

$pageTitle = __('visits.list.title');
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$sortParams = Visit::normalizeSort(
    (string) ($_GET['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? 'desc')
);
$listFilters = visit_list_filters_from_request();
$perPage = 50;

try {
    $filterMedicines = Medicine::listForFilter();
} catch (Throwable) {
    $filterMedicines = [];
}

try {
    $paginated = list_paginate(
        static fn (int $p): array => Visit::listFiltered(
            $listFilters,
            $sortParams['sort'],
            $sortParams['dir'],
            $p,
            $perPage
        ),
        $listFilters['page'],
        $perPage
    );
    $listResult = $paginated['result'];
    $listFilters['page'] = $paginated['page'];
    $visitRows = $listResult['rows'];
    $totalVisits = $listResult['total'];
    $todayVisits = Visit::countToday();
    $dbError = false;
} catch (Throwable) {
    $visitRows = [];
    $totalVisits = 0;
    $todayVisits = 0;
    $dbError = true;
}

$totalPages = max(1, (int) ceil($totalVisits / $perPage));
$page = $listFilters['page'];

view('visits/index', array_merge(
    compact(
        'visitRows',
        'totalVisits',
        'todayVisits',
        'totalPages',
        'page',
        'perPage',
        'listFilters',
        'dbError',
        'successMessage',
        'errorMessage',
        'pageTitle',
        'filterMedicines'
    ),
    $sortParams
));

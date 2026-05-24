<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Patient');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$pageTitle = __('patients.list.title');
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$sortParams = Patient::normalizeSort(
    (string) ($_GET['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? 'desc')
);
$listFilters = patient_list_filters_from_request();
$perPage = 25;

try {
    $paginated = list_paginate(
        static fn (int $p): array => Patient::listFiltered(
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
    $patientRows = $listResult['rows'];
    $totalPatients = $listResult['total'];
    $dbError = false;
} catch (Throwable) {
    $patientRows = [];
    $totalPatients = 0;
    $dbError = true;
}

$totalPages = max(1, (int) ceil($totalPatients / $perPage));
$page = $listFilters['page'];

view('patients/index', array_merge(
    compact(
        'patientRows',
        'totalPatients',
        'totalPages',
        'page',
        'perPage',
        'listFilters',
        'dbError',
        'successMessage',
        'errorMessage',
        'pageTitle'
    ),
    $sortParams
));

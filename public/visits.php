<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Visit');
load_model('Medicine');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

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
    $listResult = Visit::listFiltered(
        $listFilters,
        $sortParams['sort'],
        $sortParams['dir'],
        $listFilters['page'],
        $perPage
    );
    $visitRows = $listResult['rows'];
    $totalVisits = $listResult['total'];
    $dbError = false;
} catch (Throwable) {
    $visitRows = [];
    $totalVisits = 0;
    $dbError = true;
}

$totalPages = max(1, (int) ceil($totalVisits / $perPage));
$page = min($listFilters['page'], $totalPages);
$listFilters['page'] = $page;

view('visits/index', array_merge(
    compact(
        'visitRows',
        'totalVisits',
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

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Payment');

auth_require();
auth_require_role(['manager', 'admin']);

$pageTitle = __('payment.list.title');
$activeNav = 'payments';
$successMessage = flash_get('success');

$sortParams = Payment::normalizeSort(
    (string) ($_GET['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? 'desc')
);
$listFilters = payment_list_filters_from_request();
$summaryPeriod = payment_summary_period_from_request();
$sort = $sortParams['sort'];
$dir = $sortParams['dir'];
$perPage = 25;

try {
    $paginated = list_paginate(
        static fn (int $p): array => Payment::listForPeriodPaginated(
            $listFilters,
            $summaryPeriod,
            $sort,
            $dir,
            $p,
            $perPage
        ),
        $listFilters['page'],
        $perPage
    );
    $listResult = $paginated['result'];
    $listFilters['page'] = $paginated['page'];
    $paymentRows = $listResult['rows'];
    $totalPayments = $listResult['total'];
    $summary = Payment::summary($listFilters, $summaryPeriod);
    $dbError = false;
} catch (Throwable) {
    $paymentRows = [];
    $totalPayments = 0;
    $summary = [
        'paid_total' => 0.0,
        'pending_total' => 0.0,
    ];
    $dbError = true;
}

$totalPages = max(1, (int) ceil($totalPayments / $perPage));
$page = $listFilters['page'];
$hasFilters = payment_list_has_active_filters($listFilters);
$sortFilterQuery = payment_list_query_filters($listFilters, $summaryPeriod);

view('payment/index', compact(
    'pageTitle',
    'activeNav',
    'paymentRows',
    'summary',
    'summaryPeriod',
    'dbError',
    'successMessage',
    'sort',
    'dir',
    'listFilters',
    'sortFilterQuery',
    'hasFilters',
    'totalPayments',
    'totalPages',
    'page',
    'perPage'
));

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('StockBill');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$user = auth_user();
$viewerId = $user !== null ? (int) $user['id'] : 0;
$viewerRole = $user['role'] ?? '';

$pageTitle = __('stock.list.title');
$activeNav = 'stock';
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$sortParams = StockBill::normalizeSort(
    (string) ($_GET['sort'] ?? $_POST['sort'] ?? 'bill_date'),
    (string) ($_GET['dir'] ?? $_POST['dir'] ?? 'desc')
);
$listFilters = stock_list_filters_from_request();
$sort = $sortParams['sort'];
$dir = $sortParams['dir'];
$perPage = 25;

$errors = [];
$old = [
    'bill_number' => '',
    'register_number' => '',
    'supplier' => '',
    'bill_date' => (new DateTimeImmutable('today'))->format('Y-m-d'),
    'delivery_date' => '',
    'items' => [['name' => '', 'quantity' => '1', 'amount' => '']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    csrf_require();
    $itemNames = is_array($_POST['item_names'] ?? null) ? $_POST['item_names'] : [];
    $itemQuantities = is_array($_POST['item_quantities'] ?? null) ? $_POST['item_quantities'] : [];
    $itemAmounts = is_array($_POST['item_amounts'] ?? null) ? $_POST['item_amounts'] : [];
    $lineItems = [];
    $maxRows = max(count($itemNames), count($itemQuantities), count($itemAmounts));
    for ($i = 0; $i < $maxRows; $i++) {
        $lineItems[] = [
            'name' => (string) ($itemNames[$i] ?? ''),
            'quantity' => (string) ($itemQuantities[$i] ?? ''),
            'amount' => (string) ($itemAmounts[$i] ?? ''),
        ];
    }
    $file = isset($_FILES['bill_file']) && is_array($_FILES['bill_file']) ? $_FILES['bill_file'] : null;

    $result = StockBill::create($_POST, $lineItems, $viewerId, $file);

    if ($result['ok']) {
        flash_set('success', __('stock.create.success'));
        redirect(stock_view_url($result['id'], $listFilters, $sort, $dir));
    }

    $errors = $result['errors'];
    $old = [
        'bill_number' => trim((string) ($_POST['bill_number'] ?? '')),
        'register_number' => trim((string) ($_POST['register_number'] ?? '')),
        'supplier' => trim((string) ($_POST['supplier'] ?? '')),
        'bill_date' => trim((string) ($_POST['bill_date'] ?? '')),
        'delivery_date' => trim((string) ($_POST['delivery_date'] ?? '')),
        'items' => [],
    ];
    foreach ($lineItems as $line) {
        $old['items'][] = [
            'name' => trim($line['name']),
            'quantity' => trim((string) $line['quantity']),
            'amount' => trim((string) $line['amount']),
        ];
    }
    if ($old['items'] === []) {
        $old['items'] = [['name' => '', 'quantity' => '1', 'amount' => '']];
    }
}

try {
    $paginated = list_paginate(
        static fn (int $p): array => StockBill::listPaginated(
            $listFilters,
            $sort,
            $dir,
            $p,
            $perPage,
            $viewerId,
            $viewerRole
        ),
        $listFilters['page'],
        $perPage
    );
    $listResult = $paginated['result'];
    $listFilters['page'] = $paginated['page'];
    $stockRows = $listResult['rows'];
    $totalBills = $listResult['total'];
    $dbError = false;
} catch (Throwable) {
    $stockRows = [];
    $totalBills = 0;
    $dbError = true;
}

$totalPages = max(1, (int) ceil($totalBills / $perPage));
$page = $listFilters['page'];
$hasFilters = stock_list_has_active_filters($listFilters);
$sortFilterQuery = stock_list_query_filters($listFilters);
$canSeeAll = in_array($viewerRole, ['manager', 'admin'], true);

view('stock/index', compact(
    'pageTitle',
    'activeNav',
    'stockRows',
    'totalBills',
    'totalPages',
    'page',
    'perPage',
    'dbError',
    'successMessage',
    'errorMessage',
    'sort',
    'dir',
    'listFilters',
    'sortFilterQuery',
    'hasFilters',
    'errors',
    'old',
    'canSeeAll'
));

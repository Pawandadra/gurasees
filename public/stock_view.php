<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('StockBill');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$user = auth_user();
$viewerId = $user !== null ? (int) $user['id'] : 0;
$viewerRole = $user['role'] ?? '';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$id = $id !== false && $id > 0 ? (int) $id : 0;

$sortParams = StockBill::normalizeSort(
    (string) ($_GET['sort'] ?? 'bill_date'),
    (string) ($_GET['dir'] ?? 'desc')
);
$listFilters = stock_list_filters_from_request();

$bill = $id > 0 ? StockBill::findById($id, $viewerId, $viewerRole) : null;
if ($bill === null) {
    http_response_code(404);
    exit(__('stock.error.not_found'));
}

$pageTitle = __('stock.view.title', ['bill' => $bill['bill_number']]);
$activeNav = 'stock';
$successMessage = flash_get('success');
$backUrl = stock_list_url($sortParams['sort'], $sortParams['dir'], $listFilters);

view('stock/view', compact(
    'pageTitle',
    'activeNav',
    'bill',
    'successMessage',
    'backUrl'
));

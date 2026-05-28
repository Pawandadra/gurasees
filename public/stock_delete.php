<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('StockBill');

auth_require();
auth_require_role(['manager', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

csrf_require();

$user = auth_user();
$viewerRole = $user['role'] ?? '';
if (!in_array($viewerRole, ['manager', 'admin'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$id = $id !== false && $id > 0 ? (int) $id : 0;

$sortParams = StockBill::normalizeSort(
    (string) ($_POST['sort'] ?? 'bill_date'),
    (string) ($_POST['dir'] ?? 'desc')
);
$listFilters = stock_list_filters_from_request();

if ($id > 0 && StockBill::deleteById($id)) {
    flash_set('success', __('stock.delete.success'));
} else {
    flash_set('error', __('stock.delete.error'));
}

redirect(stock_list_url($sortParams['sort'], $sortParams['dir'], $listFilters));


<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Courier');
load_model('Visit');

auth_require();
auth_require_role(['manager', 'admin']);

$visitId = filter_var($_GET['visit_id'] ?? '', FILTER_VALIDATE_INT);
if ($visitId === false || $visitId < 1) {
    http_response_code(404);
    exit(__('courier.error.not_found'));
}

try {
    $package = Courier::findPackage((int) $visitId);
} catch (Throwable) {
    $package = null;
}

if ($package === null) {
    http_response_code(404);
    exit(__('courier.error.not_found'));
}

$pageTitle = __('courier.view.title');
$activeNav = 'courier';
$successMessage = flash_get('success');
$errorMessage = flash_get('error');
$sortParams = Courier::normalizeSort(
    (string) ($_GET['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? 'desc')
);
$listFilters = courier_list_filters_from_request();
$sort = $sortParams['sort'];
$dir = $sortParams['dir'];
$listUrl = courier_list_url($sort, $dir, courier_list_query_filters($listFilters));

view('courier/view', compact(
    'package',
    'pageTitle',
    'activeNav',
    'successMessage',
    'errorMessage',
    'listUrl',
    'sort',
    'dir',
    'listFilters'
));

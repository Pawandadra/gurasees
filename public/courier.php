<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Courier');
load_model('Visit');

auth_require();
auth_require_role(['manager', 'admin']);

$pageTitle = __('courier.list.title');
$activeNav = 'courier';
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

$sortParams = Courier::normalizeSort(
    (string) ($_GET['sort'] ?? $_POST['sort'] ?? 'date'),
    (string) ($_GET['dir'] ?? $_POST['dir'] ?? 'desc')
);
$listFilters = courier_list_filters_from_request();
$sort = $sortParams['sort'];
$dir = $sortParams['dir'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = input_string($_POST['action'] ?? '', 20);
    $visitId = filter_var($_POST['visit_id'] ?? '', FILTER_VALIDATE_INT);
    $user = auth_user();
    $userId = $user !== null ? (int) $user['id'] : 0;
    $redirectUrl = courier_redirect_url($sort, $dir, $listFilters);

    if ($action === 'dispatch') {
        if ($visitId !== false && Courier::dispatch((int) $visitId, $userId)) {
            flash_set('success', __('courier.dispatch.success'));
        } else {
            flash_set('error', __('courier.dispatch.error'));
        }
        redirect($redirectUrl);
    }

    if ($action === 'cancel') {
        if ($visitId !== false && Courier::cancel((int) $visitId)) {
            flash_set('success', __('courier.cancel.success'));
        } else {
            flash_set('error', __('courier.cancel.error'));
        }
        redirect($redirectUrl);
    }
}

try {
    $courierRows = Courier::listFiltered($listFilters, $sort, $dir);
    $dbError = false;
} catch (Throwable) {
    $courierRows = [];
    $dbError = true;
}

$totalPackages = count($courierRows);
$hasFilters = courier_list_has_active_filters($listFilters);
$sortFilterQuery = courier_list_query_filters($listFilters);

view('courier/index', compact(
    'pageTitle',
    'activeNav',
    'courierRows',
    'dbError',
    'successMessage',
    'errorMessage',
    'sort',
    'dir',
    'listFilters',
    'sortFilterQuery',
    'hasFilters',
    'totalPackages'
));

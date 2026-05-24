<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Medicine');

auth_require();
auth_require_role(['manager', 'admin']);

$sortParams = Medicine::normalizeSort(
    (string) ($_GET['sort'] ?? $_POST['sort'] ?? 'name'),
    (string) ($_GET['dir'] ?? $_POST['dir'] ?? 'asc')
);
$listFilters = medicine_list_filters_from_request();
$sort = $sortParams['sort'];
$dir = $sortParams['dir'];

$errors = [];
$editErrors = [];
$editId = null;
$successMessage = flash_get('success');
$errorMessage = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = input_string($_POST['action'] ?? '', 20);
    $redirectUrl = medicine_redirect_url($sort, $dir, $listFilters);

    if ($action === 'add') {
        $result = Medicine::create($_POST);
        if ($result['ok']) {
            flash_set('success', __('medicine.add.success'));
            redirect($redirectUrl);
        }
        $errors = $result['errors'];
    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            flash_set('error', __('medicine.error.not_found'));
            redirect($redirectUrl);
        }
        $result = Medicine::updateName((int) $id, $_POST);
        if ($result['ok']) {
            flash_set('success', __('medicine.edit.success'));
            redirect($redirectUrl);
        }
        $editErrors = $result['errors'];
        $editId = (int) $id;
    } elseif ($action === 'remove') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id !== false && Medicine::deactivate((int) $id)) {
            flash_set('success', __('medicine.delete.success'));
        } else {
            flash_set('error', __('medicine.error.not_found'));
        }
        redirect($redirectUrl);
    }
}

try {
    $medicines = Medicine::listForManage($listFilters, $sort, $dir);
    $dbError = false;
} catch (Throwable) {
    $medicines = [];
    $dbError = true;
}

$totalMedicines = count($medicines);
$hasFilters = medicine_list_has_active_filters($listFilters);
$sortFilterQuery = medicine_list_query_filters($listFilters);

$pageTitle = __('medicine.manage.title');
$activeNav = 'medicines';

view('medicine/index', compact(
    'errors',
    'editErrors',
    'editId',
    'medicines',
    'sort',
    'dir',
    'listFilters',
    'sortFilterQuery',
    'hasFilters',
    'totalMedicines',
    'successMessage',
    'errorMessage',
    'dbError'
));

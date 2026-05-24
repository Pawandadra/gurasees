<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Courier');
load_model('CourierSettings');

auth_require();
auth_require_role(['manager', 'admin']);

$visitId = filter_var($_GET['visit_id'] ?? '', FILTER_VALIDATE_INT);
if ($visitId === false || $visitId < 1) {
    http_response_code(404);
    exit(__('courier.error.not_found'));
}

try {
    $package = Courier::findPrintablePackage((int) $visitId);
} catch (Throwable) {
    $package = null;
}

if ($package === null) {
    http_response_code(404);
    exit(__('courier.error.not_found'));
}

$sender = CourierSettings::sender();
$labelDate = CourierSettings::formatLabelDate();

$listUrl = base_url('/courier.php');

view('courier/label', compact('package', 'sender', 'labelDate', 'listUrl'));

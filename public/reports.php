<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

auth_require();
auth_require_role(['manager', 'admin']);

$pageTitle = __('nav.reports');
$activeNav = 'reports';
$pageHeading = __('nav.reports');

require BASE_PATH . '/views/dashboard/coming_soon.php';

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

auth_require();
auth_require_role(['admin']);

$pageTitle = __('nav.users');
$activeNav = 'users';
$pageHeading = __('nav.users');

require BASE_PATH . '/views/dashboard/coming_soon.php';

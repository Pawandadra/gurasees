<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

auth_require();
auth_require_role(['manager', 'admin']);

$query = $_SERVER['QUERY_STRING'] ?? '';
$url = base_url('/ledger.php' . ($query !== '' ? '?' . $query : ''));

redirect($url);
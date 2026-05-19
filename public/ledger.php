<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('LedgerReport');

auth_require();
auth_require_role(['manager', 'admin']);

$filters = [
    'q' => input_string($_GET['q'] ?? '', 120),
    'date_from' => input_string($_GET['date_from'] ?? '', 10),
    'date_to' => input_string($_GET['date_to'] ?? '', 10),
];

$rows = LedgerReport::rows($filters);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    LedgerReport::downloadCsv($rows);
}

$totals = LedgerReport::totals($rows);

$pageTitle = __('nav.ledger');
$activeNav = 'ledger';

view('ledger/index', compact(
    'rows',
    'totals',
    'filters',
    'pageTitle',
    'activeNav'
));
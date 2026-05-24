<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Report');

auth_require();
auth_require_role(['manager', 'admin']);

$filters = report_filters_from_request();
$reportType = $filters['report'];
$period = $filters['period'];
$queryFilters = report_query_filters($filters);
$hasCustomDates = report_has_custom_dates($filters);

try {
    $reportData = Report::build($reportType, $filters, $period);
    $dbError = false;
} catch (Throwable $e) {
    app_log_error($e, 'Report::build');
    $reportData = ['type' => $reportType];
    $dbError = true;
}

$pageTitle = __('report.title');
$activeNav = 'reports';

view('reports/index', compact(
    'filters',
    'reportType',
    'period',
    'queryFilters',
    'hasCustomDates',
    'reportData',
    'dbError',
    'pageTitle',
    'activeNav'
));

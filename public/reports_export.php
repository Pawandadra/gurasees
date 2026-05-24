<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('Report');

auth_require();
auth_require_role(['manager', 'admin']);

$filters = report_filters_from_request();

try {
    Report::sendCsvDownload($filters['report'], $filters, $filters['period']);
} catch (Throwable $e) {
    app_log_error($e, 'Report CSV export');
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo production_error_message();
}

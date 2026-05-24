<?php

declare(strict_types=1);

/**
 * @return array{report: string, period: string, date_from: string, date_to: string}
 */
function report_filters_from_request(): array
{
    $report = strtolower(trim((string) ($_GET['report'] ?? $_POST['report'] ?? Report::TYPE_OVERVIEW)));
    if (!in_array($report, Report::TYPES, true)) {
        $report = Report::TYPE_OVERVIEW;
    }

    return [
        'report' => $report,
        'period' => Report::normalizePeriod((string) ($_GET['period'] ?? $_POST['period'] ?? Report::PERIOD_MONTH)),
        'date_from' => patient_normalize_filter_date($_GET['date_from'] ?? $_POST['date_from'] ?? null),
        'date_to' => patient_normalize_filter_date($_GET['date_to'] ?? $_POST['date_to'] ?? null),
    ];
}

/**
 * @param array<string, mixed> $filters
 */
function report_has_custom_dates(array $filters): bool
{
    return ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '';
}

/**
 * @param array<string, mixed> $filters
 * @return array<string, scalar|null>
 */
function report_query_filters(array $filters): array
{
    return array_filter(
        [
            'report' => (string) ($filters['report'] ?? Report::TYPE_OVERVIEW),
            'period' => (string) ($filters['period'] ?? Report::PERIOD_MONTH),
            'date_from' => (string) ($filters['date_from'] ?? ''),
            'date_to' => (string) ($filters['date_to'] ?? ''),
        ],
        static fn (string $value): bool => $value !== ''
    );
}

/**
 * @param array<string, mixed> $filters
 */
function report_url(array $filters = []): string
{
    $query = report_query_filters($filters);
    if ($query === []) {
        return base_url('/reports.php');
    }

    return base_url('/reports.php?' . http_build_query($query));
}

/**
 * @return array<string, string>
 */
function report_type_options(): array
{
    return [
        Report::TYPE_OVERVIEW => __('report.type.overview'),
        Report::TYPE_PAYMENTS => __('report.type.payments'),
        Report::TYPE_VISITS => __('report.type.visits'),
        Report::TYPE_PATIENTS => __('report.type.patients'),
        Report::TYPE_MEDICINES => __('report.type.medicines'),
        Report::TYPE_COURIER => __('report.type.courier'),
    ];
}

/**
 * @param array<string, mixed> $filters
 */
function report_export_url(array $filters): string
{
    $query = report_query_filters($filters);

    return base_url('/reports_export.php?' . http_build_query($query));
}

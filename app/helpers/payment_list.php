<?php

declare(strict_types=1);

function payment_summary_period_from_request(): string
{
    return Payment::normalizePeriod((string) ($_GET['period'] ?? $_POST['period'] ?? Payment::PERIOD_TODAY));
}

/**
 * @return array{q: string, status: string, type: string, date_from: string, date_to: string, page: int}
 */
function payment_list_filters_from_request(): array
{
    $status = strtolower(trim((string) ($_GET['status'] ?? $_POST['status'] ?? '')));
    if (!in_array($status, PaymentSettings::STATUSES, true)) {
        $status = '';
    }

    $type = strtolower(trim((string) ($_GET['type'] ?? $_POST['type'] ?? '')));
    if (!in_array($type, [Payment::TYPE_REGISTRATION, Payment::TYPE_VISIT], true)) {
        $type = '';
    }

    $page = filter_var($_GET['page'] ?? $_POST['page'] ?? 1, FILTER_VALIDATE_INT);
    $page = $page !== false && $page > 0 ? (int) $page : 1;

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'status' => $status,
        'type' => $type,
        'date_from' => patient_normalize_filter_date($_GET['date_from'] ?? $_POST['date_from'] ?? null),
        'date_to' => patient_normalize_filter_date($_GET['date_to'] ?? $_POST['date_to'] ?? null),
        'page' => $page,
    ];
}

/**
 * @param array<string, mixed> $listFilters
 */
function payment_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'status', 'type', 'date_from', 'date_to'] as $key) {
        if (($listFilters[$key] ?? '') !== '') {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, mixed> $listFilters
 * @return array<string, scalar|null>
 */
function payment_list_query_filters(array $listFilters, string $period = Payment::PERIOD_TODAY): array
{
    $query = array_filter(
        [
            'q' => (string) ($listFilters['q'] ?? ''),
            'status' => (string) ($listFilters['status'] ?? ''),
            'type' => (string) ($listFilters['type'] ?? ''),
            'date_from' => (string) ($listFilters['date_from'] ?? ''),
            'date_to' => (string) ($listFilters['date_to'] ?? ''),
            'page' => (int) ($listFilters['page'] ?? 1) > 1 ? (int) ($listFilters['page'] ?? 1) : null,
        ],
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );
    $query['period'] = Payment::normalizePeriod($period);

    return $query;
}

/**
 * @param array<string, scalar|null> $filters
 */
function payment_list_url(string $sort, string $dir, array $listFilters = [], string $period = Payment::PERIOD_TODAY): string
{
    return base_url('/payments.php?' . http_build_query(
        patient_build_list_query($sort, $dir, payment_list_query_filters($listFilters, $period))
    ));
}

function payment_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    array $listFilters = [],
    string $period = Payment::PERIOD_TODAY
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    return payment_list_url($column, $dir, $listFilters, $period);
}

function payment_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    return patient_sort_th_attr($column, $currentSort, $currentDir);
}

/**
 * @param array<string, scalar|null> $filters
 */
function payment_settings_url(
    array $listFilters = [],
    string $period = Payment::PERIOD_TODAY,
    string $sort = 'date',
    string $dir = 'desc'
): string {
    $query = http_build_query(patient_build_list_query($sort, $dir, payment_list_query_filters($listFilters, $period)));

    return base_url('/payment_settings.php?return=' . rawurlencode('/payments.php' . ($query !== '' ? '?' . $query : '')));
}

function payment_settings_return_url(): string
{
    $return = trim((string) ($_GET['return'] ?? $_POST['return'] ?? ''));
    $safe = safe_return_path($return, ['/payments.php', '/payment_settings.php']);

    return base_url($safe ?? '/payments.php');
}

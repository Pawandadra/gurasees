<?php

declare(strict_types=1);

/**
 * @return array{q: string, date_from: string, date_to: string, page: int}
 */
function stock_list_filters_from_request(): array
{
    $page = filter_var($_GET['page'] ?? $_POST['page'] ?? 1, FILTER_VALIDATE_INT);
    $page = $page !== false && $page > 0 ? (int) $page : 1;

    $dateFrom = patient_normalize_filter_date($_GET['date_from'] ?? $_POST['date_from'] ?? null);
    $dateTo = patient_normalize_filter_date($_GET['date_to'] ?? $_POST['date_to'] ?? null);
    if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
        [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    }

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'page' => $page,
    ];
}

/**
 * @param array<string, mixed> $listFilters
 */
function stock_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'date_from', 'date_to'] as $key) {
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
function stock_list_query_filters(array $listFilters): array
{
    return array_filter(
        [
            'q' => (string) ($listFilters['q'] ?? ''),
            'date_from' => (string) ($listFilters['date_from'] ?? ''),
            'date_to' => (string) ($listFilters['date_to'] ?? ''),
            'page' => (int) ($listFilters['page'] ?? 1) > 1 ? (int) ($listFilters['page'] ?? 1) : null,
        ],
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );
}

/**
 * @param array<string, scalar|null> $listFilters
 */
function stock_list_url(string $sort, string $dir, array $listFilters = []): string
{
    return base_url('/stock.php?' . http_build_query(
        patient_build_list_query($sort, $dir, stock_list_query_filters($listFilters))
    ));
}

function stock_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    array $listFilters = []
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    return stock_list_url($column, $dir, $listFilters);
}

function stock_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    return patient_sort_th_attr($column, $currentSort, $currentDir);
}

function stock_view_url(int $id, array $listFilters = [], string $sort = 'bill_date', string $dir = 'desc'): string
{
    $query = array_merge(
        ['id' => $id],
        patient_build_list_query($sort, $dir, stock_list_query_filters($listFilters))
    );

    return base_url('/stock_view.php?' . http_build_query($query));
}

function stock_file_url(int $id): string
{
    return base_url('/stock_file.php?id=' . $id);
}

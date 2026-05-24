<?php

declare(strict_types=1);

/**
 * @return array{q: string, status: string}
 */
function courier_list_filters_from_request(): array
{
    $status = strtolower(trim((string) ($_GET['status'] ?? $_POST['status'] ?? '')));
    if (!in_array($status, [Courier::STATUS_PENDING, Courier::STATUS_SENT, Courier::STATUS_CANCELED], true)) {
        $status = '';
    }

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'status' => $status,
    ];
}

/**
 * @param array<string, mixed> $listFilters
 */
function courier_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'status'] as $key) {
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
function courier_list_query_filters(array $listFilters): array
{
    return array_filter(
        [
            'q' => (string) ($listFilters['q'] ?? ''),
            'status' => (string) ($listFilters['status'] ?? ''),
        ],
        static fn (string $value): bool => $value !== ''
    );
}

/**
 * @param array<string, scalar|null> $filters
 */
function courier_list_url(string $sort, string $dir, array $filters = []): string
{
    return base_url('/courier.php?' . http_build_query(patient_build_list_query($sort, $dir, $filters)));
}

function courier_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    array $filters = []
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    unset($filters['sort'], $filters['dir']);

    return courier_list_url($column, $dir, $filters);
}

function courier_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    return patient_sort_th_attr($column, $currentSort, $currentDir);
}

/**
 * @param array<string, mixed> $listFilters
 */
function courier_redirect_url(string $sort, string $dir, array $listFilters = []): string
{
    return courier_list_url($sort, $dir, courier_list_query_filters($listFilters));
}

function courier_settings_return_url(): string
{
    $return = trim((string) ($_GET['return'] ?? $_POST['return'] ?? ''));
    $safe = safe_return_path($return, [
        '/courier.php',
        '/courier_settings.php',
        '/courier_view.php',
        '/courier_label.php',
    ]);

    return base_url($safe ?? '/courier.php');
}

/**
 * @param array<string, scalar|null> $filters
 */
function courier_settings_url(array $filters = []): string
{
    $path = '/courier.php';
    $query = http_build_query($filters);
    $return = $query !== '' ? $path . '?' . $query : $path;

    return base_url('/courier_settings.php?return=' . rawurlencode($return));
}

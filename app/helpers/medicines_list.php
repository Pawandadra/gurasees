<?php

declare(strict_types=1);

/**
 * @return array{q: string, kind: string}
 */
function medicine_list_filters_from_request(): array
{
    $kind = strtolower(trim((string) ($_GET['kind'] ?? $_POST['kind'] ?? '')));
    if (!in_array($kind, [Medicine::KIND_UNIT, Medicine::KIND_BULK], true)) {
        $kind = '';
    }

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'kind' => $kind,
    ];
}

/**
 * @param array<string, mixed> $listFilters
 */
function medicine_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'kind'] as $key) {
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
function medicine_list_query_filters(array $listFilters): array
{
    return array_filter(
        [
            'q' => (string) ($listFilters['q'] ?? ''),
            'kind' => (string) ($listFilters['kind'] ?? ''),
        ],
        static fn (string $value): bool => $value !== ''
    );
}

/**
 * @param array<string, scalar|null> $filters
 */
function medicine_list_url(string $sort, string $dir, array $filters = []): string
{
    return base_url('/medicines.php?' . http_build_query(patient_build_list_query($sort, $dir, $filters)));
}

function medicine_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    array $filters = []
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    unset($filters['sort'], $filters['dir']);

    return medicine_list_url($column, $dir, $filters);
}

function medicine_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    return patient_sort_th_attr($column, $currentSort, $currentDir);
}

/**
 * @param array<string, mixed> $listFilters
 */
function medicine_redirect_url(string $sort, string $dir, array $listFilters = []): string
{
    return medicine_list_url($sort, $dir, medicine_list_query_filters($listFilters));
}

<?php

declare(strict_types=1);

/**
 * @return array{q: string, visit_date: string, medicine_id: string, page: int}
 */
function visit_list_filters_from_request(): array
{
    $medicineId = filter_var($_GET['medicine_id'] ?? $_POST['medicine_id'] ?? '', FILTER_VALIDATE_INT);
    if ($medicineId === false || $medicineId < 1) {
        $medicineId = '';
    } else {
        $medicineId = (string) $medicineId;
    }

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'visit_date' => patient_normalize_filter_date($_GET['visit_date'] ?? $_POST['visit_date'] ?? null),
        'medicine_id' => $medicineId,
        'page' => max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1)),
    ];
}

/**
 * @param array<string, mixed> $listFilters
 */
function visit_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'visit_date', 'medicine_id'] as $key) {
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
function visit_list_query_filters(array $listFilters): array
{
    $page = (int) ($listFilters['page'] ?? 1);

    return [
        'return' => 'visits',
        'q' => (string) ($listFilters['q'] ?? ''),
        'visit_date' => (string) ($listFilters['visit_date'] ?? ''),
        'medicine_id' => (string) ($listFilters['medicine_id'] ?? ''),
        'page' => $page > 1 ? $page : null,
    ];
}

/**
 * @param array<string, scalar|null> $filters
 */
function visit_list_url(string $sort, string $dir, array $filters = []): string
{
    return base_url('/visits.php?' . http_build_query(patient_build_list_query($sort, $dir, $filters)));
}

function visit_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    array $filters = []
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    unset($filters['sort'], $filters['dir'], $filters['page']);

    return visit_list_url($column, $dir, $filters);
}

function visit_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    return patient_sort_th_attr($column, $currentSort, $currentDir);
}

/**
 * @param array<string, mixed> $listFilters
 */
function visit_return_url(string $sort, string $dir, array $listFilters = []): string
{
    return visit_list_url($sort, $dir, array_filter(visit_list_query_filters($listFilters)));
}

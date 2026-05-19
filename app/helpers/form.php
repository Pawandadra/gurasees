<?php

declare(strict_types=1);

/** Default empty patient form values. */
function patient_form_defaults(): array
{
    $defaults = [
        'name' => '',
        'age' => '',
        'gender' => '',
        'phone_iso' => 'IN',
        'phone_local' => '',
        'address' => '',
        'delivery_address' => '',
        'delivery_same_as_address' => '',
        'symptoms' => [],
    ];

    if (!class_exists('PaymentSettings', false)) {
        load_model('PaymentSettings');
    }

    if (PaymentSettings::isEnabled()) {
        $defaults = array_merge($defaults, PaymentSettings::registrationDefaults());
    }

    return $defaults;
}

function field_invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function show_field_error(array $errors, string $field): void
{
    if (!isset($errors[$field])) {
        return;
    }
    echo '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>';
}

/** @return 'dashboard'|'patients'|'visits' */
function patient_return_from_request(): string
{
    $return = strtolower((string) ($_GET['return'] ?? $_POST['return'] ?? 'dashboard'));

    return match ($return) {
        'patients' => 'patients',
        'visits' => 'visits',
        default => 'dashboard',
    };
}

function patient_list_path(string $return): string
{
    return $return === 'patients' ? '/patients.php' : '/dashboard.php';
}

/**
 * @return array{
 *     q: string,
 *     gender: string,
 *     age_min: string,
 *     age_max: string,
 *     date_from: string,
 *     date_to: string,
 *     page: int
 * }
 */
function patient_list_filters_from_request(): array
{
    $gender = strtolower(trim((string) ($_GET['gender'] ?? $_POST['gender'] ?? '')));
    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        $gender = '';
    }

    $ageMin = patient_normalize_filter_age($_GET['age_min'] ?? $_POST['age_min'] ?? null);
    $ageMax = patient_normalize_filter_age($_GET['age_max'] ?? $_POST['age_max'] ?? null);
    if ($ageMin !== '' && $ageMax !== '' && (int) $ageMin > (int) $ageMax) {
        [$ageMin, $ageMax] = [$ageMax, $ageMin];
    }

    $dateFrom = patient_normalize_filter_date($_GET['date_from'] ?? $_POST['date_from'] ?? null);
    $dateTo = patient_normalize_filter_date($_GET['date_to'] ?? $_POST['date_to'] ?? null);
    if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
        [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    }

    return [
        'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')),
        'gender' => $gender,
        'age_min' => $ageMin,
        'age_max' => $ageMax,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'page' => max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1)),
    ];
}

function patient_normalize_filter_age(mixed $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $age = filter_var($value, FILTER_VALIDATE_INT);
    if ($age === false || $age < 1 || $age > 120) {
        return '';
    }

    return (string) $age;
}

function patient_normalize_filter_date(mixed $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $value = trim((string) $value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($dt === false || $dt->format('Y-m-d') !== $value) {
        return '';
    }

    return $value;
}

/**
 * @param array<string, mixed> $listFilters
 */
function patient_list_has_active_filters(array $listFilters): bool
{
    foreach (['q', 'gender', 'age_min', 'age_max', 'date_from', 'date_to'] as $key) {
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
function patient_list_query_filters(array $listFilters, bool $includeReturn = true): array
{
    $page = (int) ($listFilters['page'] ?? 1);
    $filters = [
        'q' => (string) ($listFilters['q'] ?? ''),
        'gender' => (string) ($listFilters['gender'] ?? ''),
        'age_min' => (string) ($listFilters['age_min'] ?? ''),
        'age_max' => (string) ($listFilters['age_max'] ?? ''),
        'date_from' => (string) ($listFilters['date_from'] ?? ''),
        'date_to' => (string) ($listFilters['date_to'] ?? ''),
        'page' => $page > 1 ? $page : null,
    ];

    if ($includeReturn) {
        $filters = array_merge(['return' => 'patients'], $filters);
    }

    return $filters;
}

/**
 * @param array<string, scalar|null> $extra
 * @return array<string, scalar>
 */
function patient_build_list_query(string $sort, string $dir, array $extra = []): array
{
    $query = array_merge(['sort' => $sort, 'dir' => $dir], $extra);

    return array_filter(
        $query,
        static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== false && $value !== 0
    );
}

/**
 * @param array<string, scalar|null> $filters
 */
function patient_list_url(string $path, string $sort, string $dir, array $filters = []): string
{
    return base_url($path . '?' . http_build_query(patient_build_list_query($sort, $dir, $filters)));
}

function patient_sort_url(
    string $column,
    string $currentSort,
    string $currentDir,
    string $path,
    array $filters = []
): string {
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    unset($filters['sort'], $filters['dir'], $filters['page']);

    return patient_list_url($path, $column, $dir, $filters);
}

function patient_sort_th_attr(string $column, string $currentSort, string $currentDir): string
{
    if ($currentSort !== $column) {
        return '';
    }

    return ' aria-sort="' . ($currentDir === 'asc' ? 'ascending' : 'descending') . '"';
}

function patient_dashboard_url(string $sort = 'date', string $dir = 'desc'): string
{
    return patient_list_url('/dashboard.php', $sort, $dir);
}

/**
 * @param array<string, scalar|null> $listFilters
 */
function patient_return_url(string $return, string $sort, string $dir, array $listFilters = []): string
{
    if ($return === 'visits') {
        return visit_return_url($sort, $dir, $listFilters);
    }

    $path = patient_list_path($return);
    if ($return !== 'patients') {
        $queryFilters = [];
    } else {
        $queryFilters = patient_list_query_filters($listFilters);
    }

    return patient_list_url($path, $sort, $dir, $queryFilters);
}

/**
 * @param array<string, scalar|null> $extra
 */
function patient_action_query(string $sort, string $dir, array $extra = []): string
{
    return http_build_query(patient_build_list_query($sort, $dir, $extra));
}

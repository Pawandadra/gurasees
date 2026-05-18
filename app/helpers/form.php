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

function patient_sort_url(string $column, string $currentSort, string $currentDir): string
{
    $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    return base_url('/dashboard.php?' . http_build_query(['sort' => $column, 'dir' => $dir]));
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
    return base_url('/dashboard.php?' . http_build_query(['sort' => $sort, 'dir' => $dir]));
}

function patient_action_query(string $sort, string $dir): string
{
    return http_build_query(['sort' => $sort, 'dir' => $dir]);
}

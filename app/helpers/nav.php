<?php

declare(strict_types=1);

/**
 * @return list<array{id: string, url: string, label: string, icon: string}>
 */
function nav_items(string $role): array
{
    $items = [
        [
            'id' => 'dashboard',
            'url' => '/dashboard.php',
            'label' => __('nav.dashboard'),
            'icon' => 'home',
            'roles' => ['receptionist', 'manager', 'admin'],
        ],
        [
            'id' => 'patients',
            'url' => '/patients.php',
            'label' => __('nav.patients'),
            'icon' => 'patients',
            'roles' => ['receptionist', 'manager', 'admin'],
        ],
        [
            'id' => 'visits',
            'url' => '/visits.php',
            'label' => __('nav.visits'),
            'icon' => 'visits',
            'roles' => ['receptionist', 'manager', 'admin'],
        ],
        [
            'id' => 'courier',
            'url' => '/courier.php',
            'label' => __('nav.courier'),
            'icon' => 'courier',
            'roles' => ['manager', 'admin'],
        ],
        [
            'id' => 'symptoms',
            'url' => '/symptoms.php',
            'label' => __('nav.symptoms'),
            'icon' => 'symptoms',
            'roles' => ['manager', 'admin'],
        ],
        [
            'id' => 'medicines',
            'url' => '/medicines.php',
            'label' => __('nav.medicines'),
            'icon' => 'medicine',
            'roles' => ['manager', 'admin'],
        ],
        [
            'id' => 'stock',
            'url' => '/stock.php',
            'label' => __('nav.stock'),
            'icon' => 'stock',
            'roles' => ['receptionist', 'manager', 'admin'],
        ],
        [
            'id' => 'payments',
            'url' => '/payments.php',
            'label' => __('nav.payment_settings'),
            'icon' => 'payment',
            'roles' => ['manager', 'admin'],
        ],
        [
            'id' => 'reports',
            'url' => '/reports.php',
            'label' => __('nav.reports'),
            'icon' => 'reports',
            'roles' => ['manager', 'admin'],
        ],
        [
            'id' => 'users',
            'url' => '/users.php',
            'label' => __('nav.users'),
            'icon' => 'users',
            'roles' => ['admin'],
        ],
    ];

    return array_values(array_filter(
        $items,
        static fn(array $item): bool => in_array($role, $item['roles'], true)
    ));
}

function nav_active_id(): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php', '.php');

    return match ($script) {
        'patients' => 'patients',
        'visits' => 'visits',
        'courier', 'courier_view', 'courier_label' => 'courier',
        'symptoms' => 'symptoms',
        'medicines' => 'medicines',
        'stock', 'stock_view', 'stock_file', 'stock_delete' => 'stock',
        'profile' => 'profile',
        'payments', 'payment_settings' => 'payments',
        'reports' => 'reports',
        'users' => 'users',
        default => 'dashboard',
    };
}

function nav_is_active(string $id, ?string $active = null): bool
{
    return $id === ($active ?? nav_active_id());
}

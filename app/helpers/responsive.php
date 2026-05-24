<?php

declare(strict_types=1);

/**
 * Optional extra CSS classes per table column (visibility is unchanged on mobile; tables scroll horizontally).
 *
 * @return array<string, array<string, string>>
 */
function responsive_table_column_map(): array
{
    return [
        'visits' => [],
        'payments' => [],
        'patients' => [],
        'patient_visits' => [],
    ];
}

function responsive_table_col_class(string $table, string $column): string
{
    return responsive_table_column_map()[$table][$column] ?? '';
}

/**
 * @param list<string> $extra
 */
function responsive_col_attr(string $table, string $column, array $extra = []): string
{
    $classes = array_filter(array_merge(
        [responsive_table_col_class($table, $column)],
        $extra
    ));

    return $classes === [] ? '' : ' class="' . e(implode(' ', $classes)) . '"';
}

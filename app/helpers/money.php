<?php

declare(strict_types=1);

/**
 * Format a money amount without a currency symbol.
 * Whole amounts omit decimals (100 not 100.00); fractional amounts keep needed decimals.
 */
function money_format(float $amount): string
{
    return money_format_number($amount, '');
}

/**
 * Format a money amount for display (thousands separators, no currency symbol).
 */
function money_format_display(float $amount): string
{
    return money_format_number($amount, ',');
}

function money_format_number(float $amount, string $thousandsSep): string
{
    $amount = round($amount, 2);
    $formatted = number_format($amount, 2, '.', $thousandsSep);

    if (str_ends_with($formatted, '.00')) {
        return substr($formatted, 0, -3);
    }

    if (str_contains($formatted, '.')) {
        return rtrim(rtrim($formatted, '0'), '.');
    }

    return $formatted;
}

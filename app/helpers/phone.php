<?php

declare(strict_types=1);

/**
 * @return list<array{iso: string, name: string, dial: string}>
 */
function phone_countries(): array
{
    static $list = null;
    if ($list === null) {
        $list = require APP_PATH . '/config/phone_countries.php';
        foreach ($list as &$country) {
            $country['dial'] = (string) $country['dial'];
            $country['iso'] = (string) $country['iso'];
        }
        unset($country);
        usort($list, static function (array $a, array $b): int {
            if ($a['iso'] === 'IN') {
                return -1;
            }
            if ($b['iso'] === 'IN') {
                return 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });
    }

    return $list;
}

/**
 * @return array{iso: string, name: string, dial: string}|null
 */
function phone_country(string $iso): ?array
{
    static $byIso = null;
    if ($byIso === null) {
        $byIso = [];
        foreach (phone_countries() as $country) {
            $byIso[$country['iso']] = $country;
        }
    }

    return $byIso[strtoupper($iso)] ?? null;
}

function phone_flag(string $iso): string
{
    $iso = strtoupper($iso);
    if (!preg_match('/^[A-Z]{2}$/', $iso)) {
        return '';
    }

    $flag = '';
    foreach (str_split($iso) as $char) {
        $flag .= mb_chr(0x1F1E6 + ord($char) - ord('A'), 'UTF-8');
    }

    return $flag;
}

function phone_compact_label(array $country): string
{
    return phone_flag($country['iso']) . ' +' . $country['dial'];
}

function phone_search_text(array $country): string
{
    return strtolower($country['name'] . ' ' . $country['iso'] . ' ' . $country['dial']);
}

function phone_sanitize_iso(string $raw): string
{
    $iso = strtoupper(preg_replace('/[^A-Za-z]/', '', $raw));
    if (phone_country($iso) !== null) {
        return $iso;
    }

    return 'IN';
}

/**
 * @return list<string> dial codes longest first (for parsing stored numbers)
 */
function phone_dial_codes_sorted(): array
{
    static $sorted = null;
    if ($sorted !== null) {
        return $sorted;
    }

    $dials = [];
    foreach (phone_countries() as $country) {
        $dials[$country['dial']] = true;
    }
    $sorted = array_keys($dials);
    usort($sorted, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

    return $sorted;
}

function phone_build(string $iso, string $local): string
{
    $country = phone_country($iso);
    if ($country === null || $local === '') {
        return '';
    }

    return $country['dial'] . $local;
}

function phone_validate_local(string $iso, string $local): bool
{
    if ($local === '' || !ctype_digit($local)) {
        return false;
    }

    $country = phone_country($iso);
    if ($country === null) {
        return false;
    }

    $len = strlen($local);
    if ($len < 4 || $len > 14 || strlen($country['dial'] . $local) > 15) {
        return false;
    }

    if ($iso === 'IN') {
        return $len === 10 && $local[0] >= '6' && $local[0] <= '9';
    }

    return true;
}

/**
 * @return array{iso: string, local: string}
 */
function phone_parse_stored(string $phone): array
{
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return ['iso' => 'IN', 'local' => ''];
    }

    foreach (phone_dial_codes_sorted() as $dial) {
        $dial = (string) $dial;
        if (!str_starts_with($digits, $dial)) {
            continue;
        }
        $local = substr($digits, strlen($dial));
        foreach (phone_countries() as $country) {
            if ($country['dial'] === $dial) {
                return ['iso' => $country['iso'], 'local' => $local];
            }
        }
    }

    return ['iso' => 'IN', 'local' => $digits];
}

function phone_format_display(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }

    foreach (phone_dial_codes_sorted() as $dial) {
        $dial = (string) $dial;
        if (str_starts_with($digits, $dial)) {
            return '+' . $dial . ' ' . substr($digits, strlen($dial));
        }
    }

    return '+' . $digits;
}

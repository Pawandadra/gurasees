<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';

final class VisitSettings
{
    private const KEY_DEFAULT_CHARGE = 'visit.default_charge';

    public static function defaultCharge(): float
    {
        $raw = ClinicSettings::get(self::KEY_DEFAULT_CHARGE, '0');
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return max(0.0, round((float) $raw, 2));
    }

    public static function formatCharge(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function save(array $raw): array
    {
        $amountRaw = trim((string) ($raw['visit_default_charge'] ?? '0'));
        if ($amountRaw === '') {
            $amountRaw = '0';
        }
        if (!is_numeric($amountRaw)) {
            return ['ok' => false, 'errors' => ['visit_default_charge' => __('visit.error.charge')]];
        }

        $amount = max(0.0, round((float) $amountRaw, 2));
        ClinicSettings::set(self::KEY_DEFAULT_CHARGE, self::formatCharge($amount));

        return ['ok' => true];
    }
}

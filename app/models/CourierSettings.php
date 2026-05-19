<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';
require_once APP_PATH . '/models/GstSettings.php';

final class CourierSettings
{
    private const KEY_DEFAULT_CHARGE = 'courier.default_charge';

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
     * @param list<array{courier_quantity?: int}> $lines
     */
    public static function appliesToLines(array $lines): bool
    {
        foreach ($lines as $line) {
            if ((int) ($line['courier_quantity'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{charge: float, gst: float}
     */
    public static function billingForLines(array $lines): array
    {
        if (!self::appliesToLines($lines)) {
            return ['charge' => 0.0, 'gst' => 0.0];
        }

        $charge = self::defaultCharge();
        $gst = GstSettings::amountOnBase($charge, GstSettings::courierPercent());

        return ['charge' => $charge, 'gst' => $gst];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function save(array $raw): array
    {
        $amountRaw = trim((string) ($raw['courier_default_charge'] ?? '0'));
        if ($amountRaw === '') {
            $amountRaw = '0';
        }
        if (!is_numeric($amountRaw)) {
            return ['ok' => false, 'errors' => ['courier_default_charge' => __('courier.error.charge')]];
        }

        $amount = max(0.0, round((float) $amountRaw, 2));
        ClinicSettings::set(self::KEY_DEFAULT_CHARGE, self::formatCharge($amount));

        return ['ok' => true];
    }
}

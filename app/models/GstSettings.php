<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';

final class GstSettings
{
    private const KEY_REGISTRATION = 'gst.registration_percent';
    private const KEY_VISIT_CHARGE = 'gst.visit_charge_percent';
    private const KEY_MEDICINE = 'gst.medicine_percent';
    private const KEY_COURIER = 'gst.courier_percent';

    private const DEFAULT_PERCENT = 5.0;

    public static function registrationPercent(): float
    {
        return self::readPercent(self::KEY_REGISTRATION);
    }

    public static function visitChargePercent(): float
    {
        return self::readPercent(self::KEY_VISIT_CHARGE);
    }

    public static function medicinePercent(): float
    {
        return self::readPercent(self::KEY_MEDICINE);
    }

    public static function courierPercent(): float
    {
        return self::readPercent(self::KEY_COURIER);
    }

    public static function amountOnBase(float $base, float $percent): float
    {
        if ($base <= 0 || $percent <= 0) {
            return 0.0;
        }

        return round($base * $percent / 100, 2);
    }

    public static function formatPercent(float $percent): string
    {
        return number_format($percent, 2, '.', '');
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function save(array $raw): array
    {
        $fields = [
            'gst_registration_percent' => self::KEY_REGISTRATION,
            'gst_visit_percent' => self::KEY_VISIT_CHARGE,
            'gst_medicine_percent' => self::KEY_MEDICINE,
            'gst_courier_percent' => self::KEY_COURIER,
        ];

        $errors = [];
        foreach ($fields as $postKey => $settingKey) {
            $value = trim((string) ($raw[$postKey] ?? ''));
            if ($value === '' || !is_numeric($value)) {
                $errors[$postKey] = __('gst.error.percent');

                continue;
            }

            $percent = max(0.0, min(100.0, round((float) $value, 2)));
            ClinicSettings::set($settingKey, self::formatPercent($percent));
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        return ['ok' => true];
    }

    /**
     * @return array{gst_registration_percent: string, gst_visit_percent: string, gst_medicine_percent: string, gst_courier_percent: string}
     */
    public static function formDefaults(): array
    {
        return [
            'gst_registration_percent' => self::formatPercent(self::registrationPercent()),
            'gst_visit_percent' => self::formatPercent(self::visitChargePercent()),
            'gst_medicine_percent' => self::formatPercent(self::medicinePercent()),
            'gst_courier_percent' => self::formatPercent(self::courierPercent()),
        ];
    }

    private static function readPercent(string $key): float
    {
        $raw = ClinicSettings::get($key, (string) self::DEFAULT_PERCENT);
        if (!is_numeric($raw)) {
            return self::DEFAULT_PERCENT;
        }

        return max(0.0, min(100.0, round((float) $raw, 2)));
    }
}

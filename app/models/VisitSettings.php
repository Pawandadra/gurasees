<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';
require_once APP_PATH . '/models/PaymentSettings.php';

final class VisitSettings
{
    private const KEY_DEFAULT_CHARGE = 'visit.default_charge';
    private const KEY_DEFAULT_PAYMENT_METHOD = 'visit.default_payment_method';
    private const KEY_DEFAULT_PAYMENT_STATUS = 'visit.default_payment_status';

    public static function defaultCharge(): float
    {
        $raw = ClinicSettings::get(self::KEY_DEFAULT_CHARGE, '0');
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return max(0.0, round((float) $raw, 2));
    }

    public static function defaultPaymentMethod(): string
    {
        $method = ClinicSettings::get(self::KEY_DEFAULT_PAYMENT_METHOD, PaymentSettings::defaultMethod());
        if (!in_array($method, PaymentSettings::METHODS, true)) {
            return PaymentSettings::defaultMethod();
        }

        return $method;
    }

    public static function defaultPaymentStatus(): string
    {
        $status = ClinicSettings::get(self::KEY_DEFAULT_PAYMENT_STATUS, PaymentSettings::defaultStatus());
        if (!in_array($status, PaymentSettings::STATUSES, true)) {
            return PaymentSettings::defaultStatus();
        }

        return $status;
    }

    public static function formatCharge(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @return array<string, string>
     */
    public static function formDefaults(): array
    {
        return [
            'visit_default_charge' => self::formatCharge(self::defaultCharge()),
            'visit_default_method' => self::defaultPaymentMethod(),
            'visit_default_status' => self::defaultPaymentStatus(),
        ];
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function save(array $raw): array
    {
        $errors = [];

        $amountRaw = trim((string) ($raw['visit_default_charge'] ?? '0'));
        if ($amountRaw === '') {
            $amountRaw = '0';
        }
        if (!is_numeric($amountRaw)) {
            $errors['visit_default_charge'] = __('visit.error.charge');
        }

        $method = input_string($raw['visit_default_method'] ?? 'cash', 10);
        $status = input_string($raw['visit_default_status'] ?? 'paid', 10);

        if (!in_array($method, PaymentSettings::METHODS, true)) {
            $errors['visit_default_method'] = __('payment.error.method');
        }
        if (!in_array($status, PaymentSettings::STATUSES, true)) {
            $errors['visit_default_status'] = __('payment.error.status');
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $amount = max(0.0, round((float) $amountRaw, 2));
        ClinicSettings::set(self::KEY_DEFAULT_CHARGE, self::formatCharge($amount));
        ClinicSettings::set(self::KEY_DEFAULT_PAYMENT_METHOD, $method);
        ClinicSettings::set(self::KEY_DEFAULT_PAYMENT_STATUS, $status);

        return ['ok' => true];
    }
}

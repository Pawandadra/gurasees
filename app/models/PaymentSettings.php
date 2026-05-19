<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';
require_once APP_PATH . '/models/GstSettings.php';

final class PaymentSettings
{
    private const KEY_AMOUNT = 'payment.default_amount';
    private const KEY_METHOD = 'payment.default_method';
    private const KEY_STATUS = 'payment.default_status';

    /** @var list<string> */
    public const METHODS = ['cash', 'upi', 'card', 'bank', 'other'];

    /** @var list<string> */
    public const STATUSES = ['paid', 'pending', 'partial'];

    public static function defaultAmount(): float
    {
        $raw = ClinicSettings::get(self::KEY_AMOUNT, '0');
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return max(0.0, round((float) $raw, 2));
    }

    public static function isEnabled(): bool
    {
        return self::defaultAmount() > 0;
    }

    public static function defaultMethod(): string
    {
        $method = ClinicSettings::get(self::KEY_METHOD, 'cash');

        return in_array($method, self::METHODS, true) ? $method : 'cash';
    }

    public static function defaultStatus(): string
    {
        $status = ClinicSettings::get(self::KEY_STATUS, 'paid');

        return in_array($status, self::STATUSES, true) ? $status : 'paid';
    }

    /**
     * @return array{payment_amount: string, payment_method: string, payment_status: string, payment_paid_amount: string}
     */
    public static function registrationDefaults(): array
    {
        return [
            'payment_amount' => self::formatAmount(self::defaultAmount()),
            'payment_method' => self::defaultMethod(),
            'payment_status' => self::defaultStatus(),
            'payment_paid_amount' => '',
        ];
    }

    /**
     * @return array{payment_method: string, payment_status: string, payment_paid_amount: string}
     */
    public static function visitDefaults(): array
    {
        return [
            'payment_method' => self::defaultMethod(),
            'payment_status' => self::defaultStatus(),
            'payment_paid_amount' => '',
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{payment_method: string|null, payment_status: string|null, payment_paid_amount: float|null}
     */
    public static function sanitizeVisitPayment(array $raw, float $grandTotal): array
    {
        $grandTotal = max(0.0, round($grandTotal, 2));
        if ($grandTotal <= 0) {
            return [
                'payment_method' => null,
                'payment_status' => 'paid',
                'payment_paid_amount' => 0.0,
            ];
        }

        $method = input_string($raw['payment_method'] ?? '', 10);
        $status = input_string($raw['payment_status'] ?? '', 10);
        $paidRaw = trim((string) ($raw['payment_paid_amount'] ?? ''));
        $paidAmount = is_numeric($paidRaw) ? max(0.0, round((float) $paidRaw, 2)) : 0.0;

        if (!in_array($method, self::METHODS, true)) {
            $method = '';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        if ($status === 'paid') {
            $paidAmount = $grandTotal;
        } elseif ($status === 'pending') {
            $paidAmount = 0.0;
        }

        return [
            'payment_method' => $method !== '' ? $method : null,
            'payment_status' => $status !== '' ? $status : null,
            'payment_paid_amount' => $status === 'partial' ? $paidAmount : ($status === 'paid' ? $grandTotal : 0.0),
        ];
    }

    /**
     * @param array{payment_method: string|null, payment_status: string|null, payment_paid_amount: float|null} $payment
     * @return array<string, string>
     */
    public static function validateVisitPayment(array $payment, float $grandTotal): array
    {
        $grandTotal = max(0.0, round($grandTotal, 2));
        if ($grandTotal <= 0) {
            return [];
        }

        $errors = [];

        if ($payment['payment_method'] === null) {
            $errors['payment_method'] = __('payment.error.method');
        }

        if ($payment['payment_status'] === null) {
            $errors['payment_status'] = __('payment.error.status');
        }

        if ($payment['payment_status'] === 'partial') {
            $paid = (float) ($payment['payment_paid_amount'] ?? 0);
            if ($paid <= 0) {
                $errors['payment_paid_amount'] = __('payment.error.paid_amount');
            } elseif ($paid >= $grandTotal) {
                $errors['payment_paid_amount'] = __('payment.error.paid_amount_less');
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $visit
     */
    public static function formatVisitPaymentSummary(array $visit): string
    {
        $status = (string) ($visit['payment_status'] ?? '');
        if ($status === '') {
            return '';
        }

        $parts = [];
        $method = (string) ($visit['payment_method'] ?? '');
        if ($method !== '') {
            $parts[] = self::methodLabel($method);
        }
        $parts[] = self::statusLabel($status);

        if ($status === 'partial') {
            $paid = (float) ($visit['payment_paid_amount'] ?? 0);
            $grandTotal = (float) ($visit['grand_total'] ?? 0);
            $parts[] = __('payment.summary.paid_of', [
                'paid' => self::formatAmount($paid),
                'total' => self::formatAmount($grandTotal),
            ]);
        }

        return implode(' · ', $parts);
    }

    /**
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function saveDefaults(array $raw): array
    {
        $amountRaw = trim((string) ($raw['default_amount'] ?? '0'));
        if ($amountRaw === '') {
            $amountRaw = '0';
        }
        if (!is_numeric($amountRaw)) {
            return ['ok' => false, 'errors' => ['default_amount' => __('payment.error.amount')]];
        }

        $amount = max(0.0, round((float) $amountRaw, 2));
        $method = input_string($raw['default_method'] ?? 'cash', 10);
        $status = input_string($raw['default_status'] ?? 'paid', 10);

        if ($amount > 0) {
            if (!in_array($method, self::METHODS, true)) {
                return ['ok' => false, 'errors' => ['default_method' => __('payment.error.method')]];
            }
            if (!in_array($status, self::STATUSES, true)) {
                return ['ok' => false, 'errors' => ['default_status' => __('payment.error.status')]];
            }
        }

        ClinicSettings::set(self::KEY_AMOUNT, self::formatAmount($amount));
        ClinicSettings::set(self::KEY_METHOD, $method);
        ClinicSettings::set(self::KEY_STATUS, $status);

        return ['ok' => true];
    }

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'cash', 'upi', 'card', 'bank', 'other' => __('payment.method.' . $method),
            default => $method,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'paid', 'pending', 'partial' => __('payment.status.' . $status),
            default => $status,
        };
    }

    public static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    public static function formatAmountDisplay(float $amount): string
    {
        return '₹' . number_format($amount, 2);
    }

    public static function registrationTotal(float $baseAmount, float $gstAmount): float
    {
        return round($baseAmount + $gstAmount, 2);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{payment_amount: float, payment_method: string|null, payment_status: string|null, payment_paid_amount: float|null}|null
     */
    public static function sanitizeRegistration(array $raw): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        $amountRaw = trim((string) ($raw['payment_amount'] ?? ''));
        $amount = is_numeric($amountRaw) ? max(0.0, round((float) $amountRaw, 2)) : 0.0;

        if ($amount <= 0) {
            return null;
        }

        $method = input_string($raw['payment_method'] ?? '', 10);
        $status = input_string($raw['payment_status'] ?? '', 10);
        $paidRaw = trim((string) ($raw['payment_paid_amount'] ?? ''));
        $paidAmount = is_numeric($paidRaw) ? max(0.0, round((float) $paidRaw, 2)) : 0.0;

        if (!in_array($method, self::METHODS, true)) {
            $method = '';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        if ($status === 'paid') {
            $paidAmount = $amount;
        } elseif ($status === 'pending') {
            $paidAmount = 0.0;
        }

        $gstAmount = GstSettings::amountOnBase($amount, GstSettings::registrationPercent());
        $totalDue = self::registrationTotal($amount, $gstAmount);

        if ($status === 'paid') {
            $paidAmount = $totalDue;
        } elseif ($status === 'pending') {
            $paidAmount = 0.0;
        }

        return [
            'payment_amount' => $amount,
            'payment_gst_amount' => $gstAmount,
            'payment_method' => $method !== '' ? $method : null,
            'payment_status' => $status !== '' ? $status : null,
            'payment_paid_amount' => $status === 'partial' ? $paidAmount : ($status === 'paid' ? $totalDue : 0.0),
        ];
    }

    /**
     * @param array{payment_amount: float, payment_gst_amount: float, payment_method: string|null, payment_status: string|null, payment_paid_amount: float|null} $payment
     * @return array<string, string>
     */
    public static function validateRegistration(array $payment): array
    {
        $errors = [];

        if ($payment['payment_amount'] <= 0) {
            $errors['payment_amount'] = __('payment.error.amount');
        }

        if ($payment['payment_method'] === null) {
            $errors['payment_method'] = __('payment.error.method');
        }

        if ($payment['payment_status'] === null) {
            $errors['payment_status'] = __('payment.error.status');
        }

        if ($payment['payment_status'] === 'partial') {
            $paid = (float) ($payment['payment_paid_amount'] ?? 0);
            $totalDue = self::registrationTotal(
                (float) $payment['payment_amount'],
                (float) ($payment['payment_gst_amount'] ?? 0)
            );
            if ($paid <= 0) {
                $errors['payment_paid_amount'] = __('payment.error.paid_amount');
            } elseif ($paid >= $totalDue) {
                $errors['payment_paid_amount'] = __('payment.error.paid_amount_less');
            }
        }

        return $errors;
    }
}

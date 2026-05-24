<?php

declare(strict_types=1);

require_once APP_PATH . '/models/ClinicSettings.php';

final class CourierSettings
{
    private const KEY_SENDER_NAME = 'courier.sender_name';
    private const KEY_SENDER_PHONE = 'courier.sender_phone';
    private const KEY_SENDER_ADDRESS = 'courier.sender_address';

    /**
     * @return array{name: string, phone: string, address: string}
     */
    public static function sender(): array
    {
        return [
            'name' => self::senderName(),
            'phone' => self::senderPhone(),
            'address' => self::senderAddress(),
        ];
    }

    public static function senderName(): string
    {
        $name = trim(ClinicSettings::get(self::KEY_SENDER_NAME, ''));

        return $name !== '' ? $name : __('app.name');
    }

    public static function senderPhone(): string
    {
        return trim(ClinicSettings::get(self::KEY_SENDER_PHONE, ''));
    }

    public static function senderAddress(): string
    {
        return trim(ClinicSettings::get(self::KEY_SENDER_ADDRESS, ''));
    }

    public static function formatLabelDate(): string
    {
        return (new DateTimeImmutable('now'))->format('d M Y');
    }

    /**
     * @return array<string, string>
     */
    public static function formDefaults(): array
    {
        return [
            'courier_sender_name' => trim(ClinicSettings::get(self::KEY_SENDER_NAME, '')),
            'courier_sender_phone' => self::senderPhone(),
            'courier_sender_address' => self::senderAddress(),
        ];
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
     * @return array{ok: true}|array{ok: false, errors: array<string, string>}
     */
    public static function save(array $raw): array
    {
        $name = mb_substr(trim((string) ($raw['courier_sender_name'] ?? '')), 0, 120);
        $phone = mb_substr(trim((string) ($raw['courier_sender_phone'] ?? '')), 0, 30);
        $address = mb_substr(trim((string) ($raw['courier_sender_address'] ?? '')), 0, 500);

        ClinicSettings::set(self::KEY_SENDER_NAME, $name);
        ClinicSettings::set(self::KEY_SENDER_PHONE, $phone);
        ClinicSettings::set(self::KEY_SENDER_ADDRESS, $address);

        return ['ok' => true];
    }
}

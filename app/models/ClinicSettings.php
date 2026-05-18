<?php

declare(strict_types=1);

final class ClinicSettings
{
    public static function get(string $key, string $default = ''): string
    {
        $stmt = db()->prepare('SELECT setting_value FROM clinic_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = db()->prepare(
            'INSERT INTO clinic_settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}

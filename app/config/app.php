<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Gur Asees Ayurveda',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? '', '/'),
    'default_locale' => 'pa',
    'supported_locales' => ['en', 'pa'],
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
];

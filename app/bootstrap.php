<?php

declare(strict_types=1);

/**
 * Application bootstrap — load once at the start of every request.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load environment variables from .env (simple parser, no Composer required).
$envFile = BASE_PATH . '/.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if ($key !== '' && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

$appConfig = require APP_PATH . '/config/app.php';

if (!$appConfig['debug']) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('html_errors', '0');
    ini_set('expose_php', '0');
    error_reporting(E_ALL);
    require APP_PATH . '/helpers/errors.php';
    bootstrap_production_errors();
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/helpers/form.php';
require APP_PATH . '/helpers/visits_list.php';
require APP_PATH . '/helpers/medicines_list.php';
require APP_PATH . '/helpers/courier_list.php';
require APP_PATH . '/helpers/stock_list.php';
require APP_PATH . '/helpers/payment_list.php';
require APP_PATH . '/helpers/reports.php';
require APP_PATH . '/helpers/responsive.php';
require APP_PATH . '/helpers/phone.php';
require APP_PATH . '/helpers/security.php';
require APP_PATH . '/helpers/lang.php';
require APP_PATH . '/helpers/database.php';
require APP_PATH . '/helpers/uploads.php';
require APP_PATH . '/helpers/auth.php';
require APP_PATH . '/helpers/nav.php';
require APP_PATH . '/helpers/captcha.php';
require APP_PATH . '/helpers/models.php';

session_bootstrap($appConfig);

// Security headers (sent before any output).
security_send_headers();

// Load English UI strings.
lang_init($appConfig);

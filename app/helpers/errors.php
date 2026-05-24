<?php

declare(strict_types=1);

function production_error_message(): string
{
    if (function_exists('__')) {
        $translated = __('error.server');

        return $translated !== 'error.server' ? $translated : 'Something went wrong. Please try again later.';
    }

    return 'Something went wrong. Please try again later.';
}

/**
 * Log an exception or message to the configured error log (never to the response).
 */
function app_log_error(Throwable|string $error, string $context = ''): void
{
    if ($error instanceof Throwable) {
        $message = sprintf(
            '%sUncaught %s: %s in %s:%d%s%s',
            $context !== '' ? "[{$context}] " : '',
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            PHP_EOL,
            $error->getTraceAsString()
        );
    } else {
        $message = ($context !== '' ? "[{$context}] " : '') . $error;
    }

    error_log($message);
}

/**
 * Send a safe error response and stop (no stack traces or internal details).
 */
function app_respond_error(int $status = 500, ?string $message = null): never
{
    $message = $message ?? production_error_message();

    if (!headers_sent()) {
        http_response_code($status);
    }

    $wantsJson = function_exists('request_wants_json') && request_wants_json();
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') === 0) {
            $wantsJson = true;
            break;
        }
    }

    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo app_error_html($status, $message);
    exit(1);
}

function app_error_html(int $status, string $message): string
{
    $title = $status === 404 ? 'Not found' : 'Error';
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; color: #333; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { margin: 0; color: #555; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$safeMessage}</p>
</body>
</html>
HTML;
}

/**
 * Production error logging and safe user-facing failures.
 */
function bootstrap_production_errors(): void
{
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $logFile = trim((string) ($_ENV['LOG_PATH'] ?? ''));
    if ($logFile === '') {
        $logFile = $logDir . '/php.log';
    } elseif (!str_starts_with($logFile, '/')) {
        $logFile = BASE_PATH . '/' . ltrim($logFile, '/');
    }

    ini_set('log_errors', '1');
    ini_set('error_log', $logFile);
    ini_set('html_errors', '0');
    ini_set('expose_php', '0');

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        app_log_error(sprintf('PHP error [%d]: %s in %s:%d', $severity, $message, $file, $line));

        return true;
    });

    set_exception_handler(static function (Throwable $e): void {
        app_log_error($e);
        app_respond_error(500);
    });

    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        app_log_error(sprintf(
            'Fatal error [%d]: %s in %s:%d',
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        ));

        if (!headers_sent()) {
            app_respond_error(500);
        }
    });
}

<?php

declare(strict_types=1);

function load_model(string $name): void
{
    static $loaded = [];
    if (isset($loaded[$name])) {
        return;
    }

    $file = APP_PATH . '/models/' . $name . '.php';
    if (!is_readable($file)) {
        throw new RuntimeException('Model not found: ' . $name);
    }

    require_once $file;
    $loaded[$name] = true;
}

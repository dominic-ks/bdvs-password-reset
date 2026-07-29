<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Load environment from .env.test if present (for local development)
$envFile = dirname(__DIR__, 2) . '/.env.test';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        if (!isset($_ENV[trim($key)])) {
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

<?php
// Central configuration loader for Ardave Apparel.
// Supports .env values plus server environment overrides.
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
        if (!isset($_SERVER[$key])) {
            $_SERVER[$key] = $value;
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}

function get_config(string $name, $default = null) {
    $value = getenv($name);
    if ($value === false || $value === null || $value === '') {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? $default;
    }
    return $value;
}

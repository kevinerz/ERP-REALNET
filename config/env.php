<?php
/**
 * config/env.php
 * Loader .env sederhana (tanpa dependency composer).
 * Membaca file .env di root project dan menaruh nilainya ke getenv()/$_ENV,
 * supaya kredensial tidak lagi ditulis langsung di kode (hardcoded).
 */

declare(strict_types=1);

if (!function_exists('load_env')) {
    function load_env(string $path): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        if (!is_file($path)) {
            // .env tidak ada -- biarkan getenv() fallback ke environment variable
            // asli server (mis. diset lewat panel hosting), jangan fatal error.
            $loaded = true;
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (strlen($value) >= 2 && (
                ($value[0] === '"' && $value[-1] === '"') ||
                ($value[0] === "'" && $value[-1] === "'")
            )) {
                $value = substr($value, 1, -1);
            }
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
        $loaded = true;
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Muat otomatis saat file ini di-require
load_env(dirname(__DIR__) . '/.env');

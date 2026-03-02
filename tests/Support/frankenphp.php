<?php

declare(strict_types=1);

if (!function_exists('frankenphp_handle_request')) {
    function frankenphp_handle_request(callable $callback): bool
    {
        return $callback(); // Return false to break the runner loop early
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array|false
    {
        return $_SERVER['MOCK_GETALLHEADERS'] ?? false;
    }
}

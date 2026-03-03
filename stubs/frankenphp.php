<?php

declare(strict_types=1);

/**
 * Stub file for FrankenPHP functions
 *
 * @see https://frankenphp.dev/
 */

if (!function_exists('frankenphp_handle_request')) {
    /**
     * Execute a PHP script in a worker mode.
     *
     * @param callable $handler A callable that handles the request.
     * @return bool Returns true if the worker should continue handling requests, false otherwise.
     */
    function frankenphp_handle_request(callable $handler): bool {}
}

if (!function_exists('frankenphp_finish_request')) {
    /**
     * Finishes the current request.
     *
     * @return bool Returns true on success, false otherwise.
     */
    function frankenphp_finish_request(): bool {}
}

if (!function_exists('frankenphp_get_request_headers')) {
    /**
     * Get the request headers.
     *
     * @return array An associative array of headers.
     */
    function frankenphp_get_request_headers(): array {}
}

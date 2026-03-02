<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\FrankenPHP;

if (!function_exists('Yiisoft\Yii\Runner\FrankenPHP\frankenphp_handle_request')) {
    function frankenphp_handle_request(callable $callback): bool
    {
        return $callback();
    }
}

namespace Yiisoft\Yii\Runner\FrankenPHP\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/Support/frankenphp.php';
        parent::setUpBeforeClass();
    }
}

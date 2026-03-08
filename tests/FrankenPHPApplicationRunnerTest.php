<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\FrankenPHP\Tests;

use Psr\Http\Message\ResponseInterface;
use HttpSoft\Message\ServerRequest;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\Yii\Runner\FrankenPHP\FrankenPHPApplicationRunner;

final class FrankenPHPApplicationRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset the hook before each test to ensure isolation
        FrankenPHPApplicationRunner::$handleRequestHook = null;
    }

    protected function tearDown(): void
    {
        // Reset the hook after each test to ensure isolation
        FrankenPHPApplicationRunner::$handleRequestHook = null;
        parent::tearDown();
    }

    public function testInstantiation(): void
    {
        $runner = new FrankenPHPApplicationRunner(__DIR__, false);
        $this->assertInstanceOf(FrankenPHPApplicationRunner::class, $runner);
    }

    public function testRunBreaksWhenHookReturnsFalse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // Remove MAX_REQUESTS to allow the hook to break the loop
        unset($_SERVER['MAX_REQUESTS']);

        FrankenPHPApplicationRunner::$handleRequestHook = static function(callable $callback): bool {
            $callback();
            return false; // Return false to explicitly break the loop
        };

        $runner = new FrankenPHPApplicationRunner(__DIR__ . '/Support', true);
        
        $runner->run();
        
        $this->assertTrue(true);
    }

    public function testRunCallsNativeFunctionWhenHookIsNull(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'MAX_REQUESTS' => '1', // Rely on MAX_REQUESTS to break the loop
        ];

        FrankenPHPApplicationRunner::$handleRequestHook = null;

        $runner = new FrankenPHPApplicationRunner(__DIR__ . '/Support', true);
        
        $runner->run();
        
        $this->assertTrue(true);
    }

    public function testRunAndGetResponse(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'MAX_REQUESTS' => '1',
        ];

        FrankenPHPApplicationRunner::$handleRequestHook = static function(callable $callback): bool {
            return $callback();
        };

        $runner = new FrankenPHPApplicationRunner(__DIR__ . '/Support', true);
        $request = new ServerRequest([], [], [], [], [], 'GET', '/');

        $response = $runner->runAndGetResponse($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testWithTemporaryErrorHandler(): void
    {
        $runner = new FrankenPHPApplicationRunner(__DIR__ . '/Support');
        
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $errorHandler = new ErrorHandler($logger, new \Yiisoft\ErrorHandler\Renderer\PlainTextRenderer());

        $newRunner = $runner->withTemporaryErrorHandler($errorHandler);

        $this->assertNotSame($runner, $newRunner);
        $this->assertInstanceOf(FrankenPHPApplicationRunner::class, $newRunner);
    }

    public function testDebugErrorHandler(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'MAX_REQUESTS' => '1',
        ];

        FrankenPHPApplicationRunner::$handleRequestHook = static function(callable $callback): bool {
            return $callback();
        };

        // Enable debug mode to cover $registered->debug() in registerErrorHandler
        $runner = new FrankenPHPApplicationRunner(__DIR__ . '/Support', true);
        
        $runner->run();
        
        $this->assertTrue(true);
    }

    public function testExceptionDuringRun(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'EXCEPTION_TEST',
            'MAX_REQUESTS' => '1',
        ];

        FrankenPHPApplicationRunner::$handleRequestHook = static function(callable $callback): bool {
            return $callback();
        };

        $runner = new FrankenPHPApplicationRunner(
            rootPath: __DIR__ . '/Support',
            debug: true,
            checkEvents: false
        );

        // The error catcher should handle the exception internally.
        // If an exception escapes, the test will fail.
        $this->expectNotToPerformAssertions();
        $runner->run();
    }
}

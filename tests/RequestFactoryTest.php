<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\FrankenPHP\Tests;

use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\UriFactory;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Yiisoft\Yii\Runner\FrankenPHP\RequestFactory;

final class RequestFactoryTest extends TestCase
{
    private RequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new RequestFactory(
            new ServerRequestFactory(),
            new UriFactory(),
            new UploadedFileFactory(),
            new StreamFactory()
        );
    }

    public function testCreateWithMissingRequestMethod(): void
    {
        $_SERVER = [];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine HTTP request method.');
        $this->requestFactory->create();
    }

    public function testCreateBasicRequest(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path?query=1',
            'QUERY_STRING' => 'query=1',
        ];
        
        $request = $this->requestFactory->create();
        
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('1.0', $request->getProtocolVersion());
        $this->assertSame('example.com', $request->getUri()->getHost());
        $this->assertSame('/path', $request->getUri()->getPath());
        $this->assertSame('query=1', $request->getUri()->getQuery());
    }

    public function testCreateWithHeaders(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_X_CUSTOM_HEADER' => 'Value',
            'CONTENT_TYPE' => 'application/json',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer token',
        ];

        $request = $this->requestFactory->create();

        $this->assertSame('Value', $request->getHeaderLine('X-Custom-Header'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    public function testGetHeadersUsesGetAllHeaders(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_SERVER['MOCK_GETALLHEADERS'] = ['X-Mocked' => 'Value'];

        $request = $this->requestFactory->create();

        $this->assertTrue($request->hasHeader('X-Mocked'));
        $this->assertEquals('Value', $request->getHeaderLine('X-Mocked'));
    }

    public function testCreateWithRedirectHeaderOverlap(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer old',
            'HTTP_AUTHORIZATION' => 'Bearer new',
        ];

        $request = $this->requestFactory->create();

        // The RequestFactory should skip REDIRECT_HTTP_AUTHORIZATION because HTTP_AUTHORIZATION exists.
        $this->assertSame('Bearer new', $request->getHeaderLine('Authorization'));
    }

    public function testCreateWithParsedBody(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $_POST = ['key' => 'value'];

        $request = $this->requestFactory->create();
        
        $this->assertSame($_POST, $request->getParsedBody());
    }

    public function testCreateWithQueryParamsAndCookies(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = ['page' => '1'];
        $_COOKIE = ['session' => '123'];

        $request = $this->requestFactory->create();

        $this->assertSame($_GET, $request->getQueryParams());
        $this->assertSame($_COOKIE, $request->getCookieParams());
    }

    public function testCreateWithUploadedFiles(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $tempFile = sys_get_temp_dir() . '/test.txt';
        file_put_contents($tempFile, 'test');
        
        try {
            $_FILES = [
                'file1' => [
                    'name' => 'test.txt',
                    'type' => 'text/plain',
                    'tmp_name' => $tempFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => 4,
                ],
                'nested' => [
                    'name' => ['file2' => 'test2.txt'],
                    'type' => ['file2' => 'text/plain'],
                    'tmp_name' => ['file2' => $tempFile],
                    'error' => ['file2' => UPLOAD_ERR_OK],
                    'size' => ['file2' => 4],
                ]
            ];

            $request = $this->requestFactory->create();
            $files = $request->getUploadedFiles();

            $this->assertArrayHasKey('file1', $files);
            $this->assertSame('test.txt', $files['file1']->getClientFilename());
            $this->assertSame(4, $files['file1']->getSize());

            $this->assertArrayHasKey('nested', $files);
            $this->assertArrayHasKey('file2', $files['nested']);
            $this->assertSame('test2.txt', $files['nested']['file2']->getClientFilename());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testCreateUriWithHttpsAndPort(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'on',
            'SERVER_PORT' => '8080',
            'SERVER_NAME' => 'test.com',
        ];

        $request = $this->requestFactory->create();
        $uri = $request->getUri();

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('test.com', $uri->getHost());
    }

    public function testCreateUriWithHttpHostPort(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'off',
            'HTTP_HOST' => 'example.org:9000',
        ];

        $request = $this->requestFactory->create();
        $uri = $request->getUri();

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('example.org', $uri->getHost());
        $this->assertSame(9000, $uri->getPort());
    }

    public function testCreateWithInvalidFilesArray(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $_FILES = [
            'invalid_file' => [
                'name' => 'bad.txt',
                'type' => 'text/plain',
                'tmp_name' => '/does/not/exist/file.txt',
                'error' => UPLOAD_ERR_CANT_WRITE,
                'size' => 0,
            ]
        ];

        $request = $this->requestFactory->create();
        $files = $request->getUploadedFiles();

        $this->assertArrayHasKey('invalid_file', $files);
    }
}

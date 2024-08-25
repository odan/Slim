<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Handlers;

use DOMDocument;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Formatting\JsonErrorFormatter;
use Slim\Formatting\XmlErrorFormatter;
use Slim\Handlers\ExceptionHandler;
use Slim\Interfaces\ExceptionHandlerInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class ExceptionHandlerTest extends TestCase
{
    use AppTestTrait;

    #[DataProvider('textHmlHeaderProvider')]
    public function testWithTextHtml(string $header, string $headerValue): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $exceptionHandler = $app->getContainer()->get(ExceptionHandlerInterface::class);
        $exceptionHandler = $exceptionHandler->withDisplayErrorDetails(true);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader($header, $headerValue);

        $response = $exceptionHandler($request, new RuntimeException('Test Error message'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', (string)$response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Test Error message', (string)$response->getBody());
    }

    public static function textHmlHeaderProvider(): array
    {
        return [
            ['Accept', 'text/html'],
            ['Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8'],
            ['Content-Type', 'text/html'],
            ['Content-Type', 'text/html; charset=utf-8'],
        ];
    }

    // todo: Add test for other media types

    public function testWithAcceptJson(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $exceptionHandler = $app->getContainer()->get(ExceptionHandlerInterface::class);

        $response = $exceptionHandler($request, new RuntimeException('Test exception'));

        $this->assertSame(500, $response->getStatusCode());
        $expected = [
            'type' => 'urn:ietf:rfc:7807',
            'title' => 'Application Error',
            'status' => 500,
        ];
        $this->assertJsonResponse($expected, $response);
    }

    public function testInvokeWithDefaultHtmlRenderer(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new Exception('Test Error message');
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', (string)$response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('Test Error message', (string)$response->getBody());
        $this->assertStringContainsString('<h1>Application Error</h1>', (string)$response->getBody());
    }

    public static function xmlHeaderProvider(): array
    {
        return [
            ['Accept', 'application/xml'],
            ['Accept', 'application/xml, application/json'],
            ['Content-Type', 'application/xml'],
            ['Content-Type', 'application/xml; charset=utf-8'],
        ];
    }

    #[DataProvider('xmlHeaderProvider')]
    public function testWithAcceptXml(string $header, string $headerValue): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader($header, $headerValue);

        /** @var ExceptionHandler $exceptionHandler */
        $exceptionHandler = $app->getContainer()->get(ExceptionHandlerInterface::class);
        $exceptionHandler->withDisplayErrorDetails(false);
        $exceptionHandler
            ->withoutHandlers()
            ->withHandler('application/json', JsonErrorFormatter::class)
            ->withHandler('application/xml', XmlErrorFormatter::class);

        $response = $exceptionHandler($request, new RuntimeException('Test exception'));

        $this->assertSame(500, $response->getStatusCode());
        $expected = '<?xml version="1.0" encoding="UTF-8"?>
                    <problem xmlns="urn:ietf:rfc:7807">
                      <title>Application Error</title>
                      <status>500</status>
                    </problem>';

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($expected);
        $expected = $dom->saveXML();

        $dom2 = new DOMDocument();
        $dom2->preserveWhiteSpace = false;
        $dom2->formatOutput = true;
        $dom2->loadXML((string)$response->getBody());
        $actual = $dom2->saveXML();

        $this->assertSame($expected, $actual);
    }
}

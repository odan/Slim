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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Formatting\ContentNegotiator;
use Slim\Formatting\HtmlMediaTypeFormatter;
use Slim\Formatting\JsonMediaTypeFormatter;
use Slim\Formatting\PlainTextMediaTypeFormatter;
use Slim\Formatting\XmlMediaTypeFormatter;
use Slim\Handlers\ExceptionHandler;
use Slim\Interfaces\ContentNegotiatorInterface;
use Slim\Interfaces\MediaTypeFormatterInterface;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;
use Throwable;

final class ExceptionHandlerTest extends TestCase
{
    use AppTestTrait;

    public function testRegisterRenderer(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $handler = $app->getContainer()->get(ExceptionHandler::class);

        $customRenderer = new class implements MediaTypeFormatterInterface {
            public function __invoke(
                ServerRequestInterface $request,
                ResponseInterface $response,
                ?Throwable $exception = null,
                bool $displayErrorDetails = false
            ): ResponseInterface {
                $response->getBody()->write('Error: ' . $exception->getMessage());

                return $response->withStatus(400);
            }
        };

        /** @var ContentNegotiator $negotiator */
        $negotiator = $app->getContainer()->get(ContentNegotiatorInterface::class);
        $negotiator
            ->clearFormatters()
            ->setHandler('text/html', $customRenderer);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/html');

        $response = $handler($request, new RuntimeException('Error message'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Error: Error message', (string)$response->getBody());
    }

    public function testWithAcceptJson(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $exceptionHandler = $app->getContainer()->get(ExceptionHandler::class);

        $response = $exceptionHandler($request, new RuntimeException('Test exception'));

        $this->assertSame(500, $response->getStatusCode());
        $expected = [
            'type' => 'urn:ietf:rfc:7807',
            'title' => 'Slim Application Error',
            'status' => 500,
        ];
        $this->assertJsonResponse($expected, $response);
    }

    public function testInvokeWithDefaultRenderer(): void
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
            throw new Exception('Test exception');
        });

        $response = $app->handle($request);
        $this->assertSame(500, $response->getStatusCode());
        $expected = [
            'type' => 'urn:ietf:rfc:7807',
            'title' => 'Slim Application Error',
            'status' => 500,
        ];
        $this->assertJsonResponse($expected, $response);
    }

    public static function xmlHeaderProvider(): array
    {
        return [
            ['Accept', 'application/xml'],
            ['Accept', 'application/xml, application/json'],
            ['Accept', 'application/json, application/xml'],
            ['Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8'],
            ['Content-Type', 'application/xml'],
            ['Content-Type', 'application/xml; charset=utf-8'],
            ['Content-Type', 'text/custom; charset=utf-8'],
            ['Content-Type', 'multipart/form-data; boundary=ExampleBoundaryString'],
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

        $exceptionHandler = $app->getContainer()->get(ExceptionHandler::class);
        $exceptionHandler->setDisplayErrorDetails(false);

        /** @var ContentNegotiator $negotiator */
        $negotiator = $app->getContainer()->get(ContentNegotiatorInterface::class);
        // The order is considered
        $negotiator
            ->clearFormatters()
            ->setHandler('application/xml', XmlMediaTypeFormatter::class)
            ->setHandler('application/xhtml+xml', HtmlMediaTypeFormatter::class)
            ->setHandler('application/json', JsonMediaTypeFormatter::class)
            ->setHandler('text/html', HtmlMediaTypeFormatter::class)
            ->setHandler('text/plain', PlainTextMediaTypeFormatter::class);

        $response = $exceptionHandler($request, new RuntimeException('Test exception'));

        $this->assertSame(500, $response->getStatusCode());
        $expected = '<?xml version="1.0" encoding="UTF-8"?>
                    <problem xmlns="urn:ietf:rfc:7807">
                      <title>Slim Application Error</title>
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

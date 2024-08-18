<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Formatting\ContentNegotiator;
use UnexpectedValueException;

class ContentNegotiatorTest extends TestCase
{
    public function testNegotiateThrowsExceptionWhenNoHandlers()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $negotiator = $app->getContainer()->get(ContentNegotiator::class);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('There is no content negotiation handler defined');

        // Trigger the exception
        $negotiator->negotiate($request);
    }

    public function testNegotiateReturnsCorrectHandler()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $negotiator = $app->getContainer()->get(ContentNegotiator::class);
        $negotiator->clearHandlers();

        // Define a handler and associate it with a media type
        $handler = function () {
            return 'handled';
        };
        $negotiator->setHandler('application/json', $handler);

        $result = $negotiator->negotiate($request);

        // Verify the results
        $this->assertEquals('application/json', $result->getMediaType());
        $this->assertEquals('handled', ($result->getHandler())());
    }

    public function testNegotiateWithMultipleHandlers()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xml');

        $negotiator = $app->getContainer()->get(ContentNegotiator::class);

        // Define multiple handlers
        $jsonHandler = function () {
            return 'json handler';
        };
        $xmlHandler = function () {
            return 'xml handler';
        };

        $negotiator->setHandler('application/json', $jsonHandler);
        $negotiator->setHandler('application/xml', $xmlHandler);

        // Perform the negotiation
        $result = $negotiator->negotiate($request);

        // Verify the results
        $this->assertEquals('application/xml', $result->getMediaType());
        $this->assertEquals('xml handler', ($result->getHandler())());
    }

    #[DataProvider('acceptProvider')]
    public function testInvokeWithDifferentContentTypes(
        string $acceptHeader,
        string $expectedMediaType,
        string $expectedHandler
    ) {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', $acceptHeader);

        $negotiator = $app->getContainer()->get(ContentNegotiator::class);

        // First: Default handler
        $negotiator->setHandler('application/json', function () {
            return 'json_handler';
        });
        $negotiator->setHandler('application/xml', function () {
            return 'xml_handler';
        });
        $negotiator->setHandler('text/html', function () {
            return 'html_handler';
        });
        $negotiator->setHandler('text/plain', function () {
            return 'plain_handler';
        });

        $result = $negotiator->negotiate($request);

        $this->assertEquals($expectedMediaType, $result->getMediaType());
        $this->assertEquals($expectedHandler, ($result->getHandler())());
    }

    public static function acceptProvider(): array
    {
        return [
            ['application/json', 'application/json', 'json_handler'],
            ['application/vnd.api+json', 'application/json', 'json_handler'],
            ['text/html; charset=utf-8', 'text/html', 'html_handler'],
            ['application/xml', 'application/xml', 'xml_handler'],
            ['text/html', 'text/html', 'html_handler'],
            ['application/xhtml+xml', 'application/xml', 'xml_handler'],
            ['text/plain', 'text/plain', 'plain_handler'],
            ['*/*', 'application/json', 'json_handler'],
            [
                'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8',
                'application/xml',
                'xml_handler',
            ],
            ['application/json', 'application/json', 'json_handler'],
            ['multipart/form-data; boundary=ExampleBoundaryString', 'application/json', 'json_handler'],
            ['application/x-www-form-urlencoded', 'application/json', 'json_handler'],
        ];
    }
}

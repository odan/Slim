<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Handlers\Formatting\XmlErrorFormatter;

class XmlMediaTypeFormatterTest extends TestCase
{
    public function testInvokeWithExceptionAndWithErrorDetails()
    {
        $app = (new AppBuilder())->build();

        // Create a request and response
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        // Instantiate the formatter and invoke it
        $formatter = $app->getContainer()->get(XmlErrorFormatter::class);
        $result = $formatter($request, $response, $exception, true);

        // Assertions
        $this->assertEquals('application/xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<message>Application Error</message>', $xml);
        $this->assertStringContainsString('<exception>', $xml);
        $this->assertStringContainsString('<type>Exception</type>', $xml);
        $this->assertStringContainsString('<message>Test exception message</message>', $xml);
    }

    public function testInvokeWithExceptionAndWithoutErrorDetails()
    {
        $app = (new AppBuilder())->build();

        // Create a request and response
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        // Instantiate the formatter and invoke it
        $formatter = $app->getContainer()->get(XmlErrorFormatter::class);
        $result = $formatter($request, $response, $exception, false);

        // Assertions
        $this->assertEquals('application/xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<message>Application Error</message>', $xml);
        $this->assertStringNotContainsString('<exception>', $xml);
        $this->assertStringNotContainsString('<type>Exception</type>', $xml);
    }

    public function testInvokeWithNestedExceptionsAndWithErrorDetails()
    {
        $app = (new AppBuilder())->build();

        // Create a request and response
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $innerException = new Exception('Inner exception message');
        $outerException = new Exception('Outer exception message', 0, $innerException);

        // Instantiate the formatter and invoke it
        $formatter = $app->getContainer()->get(XmlErrorFormatter::class);
        $result = $formatter($request, $response, $outerException, true);

        // Assertions
        $this->assertEquals('application/xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<message>Application Error</message>', $xml);
        $this->assertStringContainsString('<exception>', $xml);
        $this->assertStringContainsString('<message>Outer exception message</message>', $xml);
        $this->assertStringContainsString('<message>Inner exception message</message>', $xml);
    }
}

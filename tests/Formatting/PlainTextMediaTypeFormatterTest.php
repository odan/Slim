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
use Slim\Formatting\PlainTextMediaTypeFormatter;

class PlainTextMediaTypeFormatterTest extends TestCase
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
        $formatter = new PlainTextMediaTypeFormatter();
        $result = $formatter($request, $response, $exception, true);

        // Assertions
        $this->assertEquals('text/plain', $result->getHeaderLine('Content-Type'));

        $text = (string)$result->getBody();
        $this->assertStringContainsString('Application Error', $text);
        $this->assertStringContainsString('Test exception message', $text);
        $this->assertStringContainsString('Type: Exception', $text);
        $this->assertStringContainsString('Message: Test exception message', $text);
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
        $formatter = new PlainTextMediaTypeFormatter();
        $result = $formatter($request, $response, $exception, false);

        // Assertions
        $this->assertEquals('text/plain', $result->getHeaderLine('Content-Type'));

        $text = (string)$result->getBody();
        $this->assertStringContainsString('Application Error', $text);
        $this->assertStringNotContainsString('Test exception message', $text);
        $this->assertStringNotContainsString('Type: Exception', $text);
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
        $formatter = new PlainTextMediaTypeFormatter();
        $result = $formatter($request, $response, $outerException, true);

        // Assertions
        $this->assertEquals('text/plain', $result->getHeaderLine('Content-Type'));

        $text = (string)$result->getBody();
        $this->assertStringContainsString('Application Error', $text);
        $this->assertStringContainsString('Outer exception message', $text);
        $this->assertStringContainsString('Inner exception message', $text);
        $this->assertStringContainsString('Previous Exception:', $text);
    }
}

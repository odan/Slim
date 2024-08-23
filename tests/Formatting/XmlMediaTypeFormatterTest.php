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
use Slim\Formatting\XmlErrorFormatter;

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
        $formatter = new XmlErrorFormatter();
        $result = $formatter($request, $response, $exception, true);

        // Assertions
        $this->assertEquals('application/problem+xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<problem xmlns="urn:ietf:rfc:7807">', $xml);
        $this->assertStringContainsString('<title>Application Error</title>', $xml);
        $this->assertStringContainsString('<status>200</status>', $xml);
        $this->assertStringContainsString('<exceptions>', $xml);
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
        $formatter = new XmlErrorFormatter();
        $result = $formatter($request, $response, $exception, false);

        // Assertions
        $this->assertEquals('application/problem+xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<problem xmlns="urn:ietf:rfc:7807">', $xml);
        $this->assertStringContainsString('<title>Application Error</title>', $xml);
        $this->assertStringContainsString('<status>200</status>', $xml);
        $this->assertStringNotContainsString('<exceptions>', $xml);
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
        $formatter = new XmlErrorFormatter();
        $result = $formatter($request, $response, $outerException, true);

        // Assertions
        $this->assertEquals('application/problem+xml', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<problem xmlns="urn:ietf:rfc:7807">', $xml);
        $this->assertStringContainsString('<title>Application Error</title>', $xml);
        $this->assertStringContainsString('<status>200</status>', $xml);
        $this->assertStringContainsString('<exceptions>', $xml);
        $this->assertStringContainsString('<message>Outer exception message</message>', $xml);
        $this->assertStringContainsString('<message>Inner exception message</message>', $xml);
    }

    public function testSetContentType()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        // Instantiate the formatter, set a custom content type, and invoke it
        $formatter = new XmlErrorFormatter();
        $formatter->setContentType('application/vnd.api+json');
        $result = $formatter($request, $response, $exception, false);

        $this->assertEquals('application/vnd.api+json', $result->getHeaderLine('Content-Type'));

        $xml = (string)$result->getBody();
        $this->assertStringContainsString('<problem xmlns="urn:ietf:rfc:7807">', $xml);
        $this->assertStringContainsString('<title>Application Error</title>', $xml);
    }
}

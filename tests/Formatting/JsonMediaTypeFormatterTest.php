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
use Slim\Exception\HttpNotFoundException;
use Slim\Formatting\JsonErrorFormatter;

class JsonMediaTypeFormatterTest extends TestCase
{
    public function testInvokeWithExceptionAndWithErrorDetails()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        // Instantiate the formatter with JsonRenderer and invoke it
        $formatter = $app->getContainer()->get(JsonErrorFormatter::class);
        $result = $formatter($request, $response, $exception, true);

        $this->assertEquals('application/problem+json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('urn:ietf:rfc:7807', $data['type']);
        $this->assertEquals('Application Error', $data['title']);
        $this->assertEquals(200, $data['status']);
        $this->assertArrayHasKey('exceptions', $data);
        $this->assertCount(1, $data['exceptions']);
        $this->assertEquals('Test exception message', $data['exceptions'][0]['message']);
    }

    public function testInvokeWithExceptionAndWithoutErrorDetails()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $exception = new Exception('Test exception message');

        $formatter = $app->getContainer()->get(JsonErrorFormatter::class);
        $result = $formatter($request, $response, $exception, false);

        $this->assertEquals('application/problem+json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('urn:ietf:rfc:7807', $data['type']);
        $this->assertEquals('Application Error', $data['title']);
        $this->assertEquals(200, $data['status']);
        $this->assertArrayNotHasKey('exceptions', $data);
    }

    public function testInvokeWithHttpExceptionAndWithoutErrorDetails()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse()
            ->withStatus(404);

        $exception = new HttpNotFoundException($request, 'Test exception message');

        $formatter = $app->getContainer()->get(JsonErrorFormatter::class);
        $result = $formatter($request, $response, $exception, true);

        $this->assertEquals('application/problem+json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('urn:ietf:rfc:7807', $data['type']);
        $this->assertEquals('404 Not Found', $data['title']);
        $this->assertEquals(
            'The requested resource could not be found. Please verify the URI and try again.',
            $data['detail']
        );
        $this->assertEquals(404, $data['status']);
        $this->assertArrayHasKey('exceptions', $data);
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

        $formatter = $app->getContainer()->get(JsonErrorFormatter::class);
        $formatter->setContentType('application/vnd.api+json');

        $result = $formatter($request, $response, $exception, false);

        $this->assertEquals('application/vnd.api+json', $result->getHeaderLine('Content-Type'));
    }
}

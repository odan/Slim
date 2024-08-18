<?php

declare(strict_types=1);

namespace Slim\Tests\Formatting;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Formatting\JsonMediaTypeFormatter;

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
        $formatter = $app->getContainer()->get(JsonMediaTypeFormatter::class);
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

        $formatter = $app->getContainer()->get(JsonMediaTypeFormatter::class);
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

        $formatter = $app->getContainer()->get(JsonMediaTypeFormatter::class);
        $formatter->setContentType('application/vnd.api+json');

        $result = $formatter($request, $response, $exception, false);

        $this->assertEquals('application/vnd.api+json', $result->getHeaderLine('Content-Type'));
    }
}

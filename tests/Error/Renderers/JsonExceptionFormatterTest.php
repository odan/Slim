<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Error\Renderers;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Error\Renderers\JsonExceptionRenderer;
use Slim\Exception\HttpNotFoundException;

class JsonExceptionFormatterTest extends TestCase
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
        $formatter = $app->getContainer()->get(JsonExceptionRenderer::class);
        $result = $formatter($request, $response, $exception, true);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('Application Error', $data['message']);
        $this->assertArrayHasKey('exception', $data);
        $this->assertCount(1, $data['exception']);
        $this->assertEquals('Test exception message', $data['exception'][0]['message']);
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

        $formatter = $app->getContainer()->get(JsonExceptionRenderer::class);
        $result = $formatter($request, $response, $exception, false);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('Application Error', $data['message']);
        $this->assertArrayNotHasKey('exception', $data);
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

        $formatter = $app->getContainer()->get(JsonExceptionRenderer::class);
        $result = $formatter($request, $response, $exception, true);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $json = (string)$result->getBody();
        $data = json_decode($json, true);

        // Assertions
        $this->assertEquals('404 Not Found', $data['message']);
        $this->assertArrayHasKey('exception', $data);
    }
}

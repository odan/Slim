<?php

declare(strict_types=1);

namespace Slim\Tests\RequestHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\RequestHandler\Runner;
use stdClass;

final class RunnerTest extends TestCase
{
    public function testHandleWithMiddlewareInterface()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('X-Test', 'Modified');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);

                return $response->withHeader('X-Middleware', 'Processed');
            }
        };

        $runner = new Runner([
            $middleware,
            function () use ($response) {
                return $response->withHeader('X-Result', 'Success');
            },
        ]);

        $result = $runner->handle($request);

        $this->assertSame('Processed', $result->getHeaderLine('X-Middleware'));
        $this->assertSame('Success', $result->getHeaderLine('X-Result'));
    }

    public function testHandleWithRequestHandlerInterface()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response->withHeader('X-Handler', 'Handled');
            }
        };

        $runner = new Runner([$handler]);

        $result = $runner->handle($request);

        $this->assertSame('Handled', $result->getHeaderLine('X-Handler'));
    }

    public function testHandleWithCallableMiddleware()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $runner = new Runner([
            function (ServerRequestInterface $req, RequestHandlerInterface $handler) use ($response) {
                return $response->withHeader('X-Callable', 'Called');
            },
        ]);

        $result = $runner->handle($request);

        $this->assertSame('Called', $result->getHeaderLine('X-Callable'));
    }

    public function testHandleWithEmptyQueueThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No middleware found. Add a response factory middleware.');

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new Runner([]);
        $runner->handle($request);
    }

    public function testHandleWithInvalidObjectMiddlewareThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid middleware queue entry "object"');

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new Runner([new stdClass()]);
        $runner->handle($request);
    }

    public function testHandleWithInvalidMiddlewareStringThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid middleware queue entry "foo"');

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new Runner(['foo']);
        $runner->handle($request);
    }
}

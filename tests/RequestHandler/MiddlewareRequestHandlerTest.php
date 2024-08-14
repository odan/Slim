<?php

declare(strict_types=1);

namespace Slim\Tests\RequestHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Middleware\ResponseFactoryMiddleware;
use Slim\RequestHandler\MiddlewareRequestHandler;

final class MiddlewareRequestHandlerTest extends TestCase
{
    public function testHandleWithFunctionMiddlewareStack()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $middleware = [
            function ($req, $handler) {
                $response = $handler->handle($req);
                return $response->withHeader('X-Middleware-1', 'Processed-1');
            },
            function ($req, $handler) {
                $response = $handler->handle($req);
                return $response->withHeader('X-Middleware-2', 'Processed-2');
            },
            ResponseFactoryMiddleware::class,
        ];

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $middleware);

        $handler = $app->getContainer()
            ->get(MiddlewareRequestHandler::class);

        $response = $handler->handle($request);

        $this->assertSame('Processed-1', $response->getHeaderLine('X-Middleware-1'));
        $this->assertSame('Processed-2', $response->getHeaderLine('X-Middleware-2'));
        $this->assertSame('Final', $response->getHeaderLine('X-Result'));
    }

    public function testHandleWithoutMiddlewareStack()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No middleware found. Add a response factory middleware.');

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, []);

        $handler = $app->getContainer()
            ->get(MiddlewareRequestHandler::class);

        $response = $handler->handle($request);

        $this->assertSame('Final', $response->getHeaderLine('X-Result'));
    }

    public function testHandleWithClassMiddlewareStack()
    {
        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $middleware = [];
        $middleware[] = new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Middleware-1', 'Processed-1');
            }
        };

        $middleware[] = ResponseFactoryMiddleware::class;

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $middleware);

        $handler = $app->getContainer()
            ->get(MiddlewareRequestHandler::class);

        $response = $handler->handle($request);

        $this->assertSame('Processed-1', $response->getHeaderLine('X-Middleware-1'));
    }

    public function testHandleWithNoMiddlewareAttribute()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No middleware found. Add a response factory middleware.');

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $middleware = [];
        $middleware[] = new class() implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Middleware-1', 'Processed-1');
            }
        };

        $middleware[] = ResponseFactoryMiddleware::class;

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $middleware);

        $handler = $app->getContainer()
            ->get(MiddlewareRequestHandler::class);

        $response = $handler->handle($request);

        $this->assertSame('Final', $response->getHeaderLine('X-Result'));
    }

    public function testHandleWithInvalidMiddleware()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object or callable that implements "MiddlewareInterface".'
        );

        $app = (new AppBuilder())->build();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $middleware = [];
        $middleware[] = [];
        $middleware[] = ResponseFactoryMiddleware::class;

        $request = $request->withAttribute(MiddlewareRequestHandler::MIDDLEWARE, $middleware);

        $handler = $app->getContainer()
            ->get(MiddlewareRequestHandler::class);

        $response = $handler->handle($request);

        $this->assertSame('Final', $response->getHeaderLine('X-Result'));
    }
}

<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Container\MiddlewareResolver;
use Slim\Enums\MiddlewareOrder;
use Slim\Interfaces\ContainerResolverInterface;

class MiddlewareResolverTest extends TestCase
{
    public function testResolveStackWithFifoOrder()
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $container = $app->getContainer();
        $containerResolver = $container->get(ContainerResolverInterface::class);

        $middlewareResolver = new MiddlewareResolver(
            $container,
            $containerResolver,
            MiddlewareOrder::FIFO
        );

        $middleware1 = $this->createCallableMiddleware();
        $middleware2 = $this->createMiddleware();

        $queue = [$middleware1, $middleware2];

        $resolvedStack = $middlewareResolver->resolveStack($queue);

        $this->assertCount(2, $resolvedStack);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedStack[0]);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedStack[1]);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $resolvedStack[0]->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $response = $resolvedStack[1]->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testResolveStackWithLifoOrder()
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $container = $app->getContainer();
        $containerResolver = $container->get(ContainerResolverInterface::class);

        $middlewareResolver = new MiddlewareResolver(
            $container,
            $containerResolver,
            MiddlewareOrder::LIFO
        );

        $middleware1 = $this->createCallableMiddleware();
        $middleware2 = $this->createMiddleware();

        $queue = [$middleware1, $middleware2];

        $resolvedStack = $middlewareResolver->resolveStack($queue);

        $this->assertCount(2, $resolvedStack);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedStack[0]);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedStack[1]);
    }

    public function testResolveMiddlewareWithValidMiddleware()
    {
        $builder = new AppBuilder();
        $app = $builder->build();
        $container = $app->getContainer();
        $containerResolver = $container->get(ContainerResolverInterface::class);

        $middlewareResolver = new MiddlewareResolver(
            $container,
            $containerResolver
        );

        $middleware = $this->createMiddleware();

        $resolvedMiddleware = $middlewareResolver->resolveStack([$middleware]);

        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedMiddleware[0]);
    }

    public function testResolveStackWithException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object or callable that implements "MiddlewareInterface".'
        );

        $builder = new AppBuilder();
        $app = $builder->build();
        $container = $app->getContainer();
        $containerResolver = $container->get(ContainerResolverInterface::class);

        $middlewareResolver = new MiddlewareResolver(
            $container,
            $containerResolver
        );

        $middlewareResolver->resolveStack([[null]]);
    }

    private function createCallableMiddleware(): callable
    {
        $response = $this->createMock(ResponseInterface::class);

        return function () use ($response): ResponseInterface {
            return $response;
        };
    }

    private function createMiddleware(): MiddlewareInterface
    {
        $response = $this->createMock(ResponseInterface::class);

        return new class ($response) implements MiddlewareInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->response;
            }
        };
    }
}

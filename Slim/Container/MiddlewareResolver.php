<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Enums\MiddlewareOrder;
use Slim\Interfaces\ContainerResolverInterface;

final class MiddlewareResolver
{
    private ContainerInterface $container;

    private ContainerResolverInterface $containerResolver;

    private MiddlewareOrder $middlewareOrder;

    public function __construct(
        ContainerInterface $container,
        ContainerResolverInterface $containerResolver,
        MiddlewareOrder $middlewareOrder = MiddlewareOrder::FIFO
    ) {
        $this->container = $container;
        $this->containerResolver = $containerResolver;
        $this->middlewareOrder = $middlewareOrder;
    }

    /**
     * Resolve the middleware stack.
     *
     * @param array<int,mixed> $queue
     *
     * @return array<int,mixed>
     */
    public function resolveStack(array $queue): array
    {
        if ($this->middlewareOrder === MiddlewareOrder::LIFO) {
            $queue = array_reverse($queue);
        }

        foreach ($queue as $key => $value) {
            $queue[$key] = $this->resolveMiddleware($value);
        }

        return $queue;
    }

    /**
     * Add a new middleware to the stack.
     */
    private function resolveMiddleware(MiddlewareInterface|callable|string|array $middleware): MiddlewareInterface
    {
        $middleware = $this->containerResolver->resolve($middleware);

        if (is_callable($middleware)) {
            return $this->addCallable($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new RuntimeException('A middleware must be an object or callable that implements "MiddlewareInterface".');
    }

    /**
     * Add a (non-standard) callable middleware to the stack
     */
    private function addCallable(callable $middleware): MiddlewareInterface
    {
        if ($middleware instanceof Closure) {
            /** @var Closure $middleware */
            $middleware = $middleware->bindTo($this->container) ?? throw new RuntimeException(
                'Unable to bind middleware to DI container.'
            );
        }

        return new class ($middleware) implements MiddlewareInterface {
            /**
             * @var callable
             */
            private $middleware;

            public function __construct(callable $middleware)
            {
                $this->middleware = $middleware;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return ($this->middleware)($request, $handler);
            }
        };
    }
}

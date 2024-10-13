<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Container\MiddlewareResolver;

/**
 * Middleware (PSR-15) request handler.
 */
final class MiddlewareRequestHandler implements RequestHandlerInterface
{
    public const MIDDLEWARE = '__RequestHandler__Middleware__';

    protected MiddlewareResolver $resolver;

    public function __construct(MiddlewareResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handles the current entry in the middleware queue and advances.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array $middlewares */
        $middlewares = $request->getAttribute(self::MIDDLEWARE) ?: [];
        $queue = $this->resolver->resolveStack($middlewares);

        reset($queue);
        $runner = new Runner($queue);

        $request = $request->withoutAttribute(self::MIDDLEWARE);

        return $runner->handle($request);
    }
}

<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\ExceptionHandlerInterface;
use Throwable;

/**
 * This middleware handles exceptions that occur during the processing of an HTTP request.
 * It catches any `Throwable` thrown by the subsequent middleware or request handler and delegates
 * the handling of the exception to a configured `ExceptionHandlerInterface` implementation.
 *
 * This middleware ensures that the application can gracefully handle errors and return an appropriate
 * response to the client.
 */
final class ExceptionHandlingMiddleware implements MiddlewareInterface
{
    private ExceptionHandlerInterface $exceptionHandler;

    public function __construct(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return ($this->exceptionHandler)($request, $exception);
        }
    }
}

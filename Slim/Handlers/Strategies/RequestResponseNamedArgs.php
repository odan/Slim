<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * Route callback strategy with route parameters as individual arguments.
 *
 * @api
 */
class RequestResponseNamedArgs implements InvocationStrategyInterface
{
    public function __construct()
    {
        if (PHP_VERSION_ID < 80000) {
            throw new RuntimeException('Named arguments are only available for PHP >= 8.0.0');
        }
    }

    /**
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param array<string, string> $routeArguments
     * @param callable $callable
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        return $callable($request, $response, ...$routeArguments);
    }
}

<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

class RequestHandlerInvocationStrategyTester implements RequestHandlerInvocationStrategyInterface
{
    public static $LastCalledFor = null;

    /**
     * Invoke a route callable.
     *
     * @param callable $callable the callable to invoke using the strategy
     * @param ServerRequestInterface $request the request object
     * @param ResponseInterface $response the response object
     * @param array $routeArguments The route's placeholder arguments
     *
     * @return ResponseInterface the response from the callable
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        static::$LastCalledFor = $callable;

        return $response;
    }
}

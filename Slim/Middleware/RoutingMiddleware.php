<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use FastRoute\Dispatcher\GroupCountBased;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Routing\RouteContext;
use Slim\Routing\Router;
use Slim\Routing\RoutingResults;

/**
 * Middleware for resolving routes.
 *
 * This middleware handles the routing process by dispatching the request to the appropriate route
 * based on the HTTP method and URI. It then stores the routing results in the request attributes.
 */
final class RoutingMiddleware implements MiddlewareInterface
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Dispatch
        $dispatcher = new GroupCountBased($this->router->getRouteCollector()->getData());

        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        $routeStatus = (int)$routeInfo[0];
        $routingResults = null;

        if ($routeStatus === RoutingResults::FOUND) {
            $routingResults = new RoutingResults(
                $routeStatus,
                $routeInfo[1],
                $request->getMethod(),
                $uri,
                $routeInfo[2]
            );
        }

        if ($routeStatus === RoutingResults::METHOD_NOT_ALLOWED) {
            $routingResults = new RoutingResults(
                $routeStatus,
                null,
                $request->getMethod(),
                $uri,
                $routeInfo[1],
            );
        }

        if ($routeStatus === RoutingResults::NOT_FOUND) {
            $routingResults = new RoutingResults($routeStatus, null, $request->getMethod(), $uri);
        }

        if (!$routingResults) {
            throw new RuntimeException('An unexpected error occurred while performing routing.');
        }

        $request = $request->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        return $handler->handle($request);
    }
}
